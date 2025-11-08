<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

use App\Models\Session;
use App\Models\Site;
use App\Models\Player;
use App\Models\Hand;
use App\Models\HandAction;
use App\Models\HandCard;
use App\Models\Card;
use App\Models\HandPlayer;
use App\Models\User;
use DateTime;
use Throwable;

class ParseHandHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;
    protected User $user;
    protected string $gameType = 'cash';
    protected $session;
    protected $heroPlayer;
    protected $site;

    public function __construct(string $path, User $user)
    {
        $this->path = $path;
        $this->user = $user;
    }

    public function handle(): void
    {
        try {
            $this->handlePokerstarsHistory();
        } catch (Throwable $e) {
            Log::error('Hand history parsing failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function handlePokerstarsHistory(): void
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', Storage::get($this->path));
        $hands = array_filter(preg_split('/(?=PokerStars Hand #\d+)/', $content));

        $this->site = Site::firstOrCreate(['name' => 'PokerStars']);
    
        foreach($hands as $handText) 
        {
            $this->parseHand($handText);
        }
    }

    public function parseHand(string $text): Hand
    {
        $lines = explode("\n", $text);

        $heroName = $this->extractHeroName($text);
        $this->heroPlayer = Player::firstOrCreate([
            'name' => $heroName,
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
        ]);

        // If the session doesn't exist yet, create it
        if(!$this->session) {
            $firstLine = collect($lines)->first(fn($line) => trim($line) !== '');
            // Extract stakes from first line
            preg_match('/\((\$[\d\.]+\/\$[\d\.]+) USD\)/', $firstLine, $stakesMatch);
            $stakes = $stakesMatch[1] ?? null;

            // Extract table name from second line
            $secondLine = $lines[1] ?? '';
            preg_match("/Table '([^']+)'/", $secondLine, $tableMatch);
            $tableName = $tableMatch[1] ?? null;

            // Combine to form session ID
            $sessionId = $tableName && $stakes ? "{$tableName} ({$stakes})" : null;

            preg_match('/\[(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $firstLine, $timeMatch);
            $startTime = isset($timeMatch[1])
                ? Carbon::createFromFormat('Y/m/d H:i:s', $timeMatch[1], 'America/New_York')->setTimezone('UTC')
                : null;
                
            $this->session = Session::firstOrCreate([
                'session_id' => $sessionId,
                'type' => $this->gameType,
                'stakes' => $stakes,
                'start_time' => $startTime,
                'player_id' => $this->heroPlayer->id,
                'site_id' => $this->site->id
            ]);
        }

        
        $hand = Hand::firstOrCreate([
            'game_session_id' => $this->session->id,
            'hand_number' => $this->extractHandNumber($lines),
            'timestamp' => $this->extractTimestamp($lines),
            'raw_text' => $text
        ]);

        $playerContributions = [];
        $playerCollections = [];
        $uncalledReturns = [];
        $rake = 0;

        $street = 0; // 0 = preflop
        $actionOrder = 0;
        foreach ($lines as $line) {
            if (preg_match('/^Seat \d+: (\w+)/', $line, $m)) {
                $seatedPlayers[] = $m[1];
            }

            $this->parseLine($line, $hand, $playerContributions, $playerCollections, $uncalledReturns, $rake, $street, $actionOrder);
        }

        $this->applyCardsToHand($hand, $lines);

        $hand->rake = $rake;
        $hand->pot_size = array_sum($playerCollections);
        $hand->save();

        foreach ($seatedPlayers as $name) {
            $player = ($this->heroPlayer->name === $name)
                ? $this->heroPlayer
                : Player::firstOrCreate([
                    'name' => $name,
                    'site_id' => $this->session->site_id,
                ]);

            $contributed = $playerContributions[$name] ?? 0;
            $collected = $playerCollections[$name] ?? 0;
            $returned = $uncalledReturns[$name] ?? 0;

            HandPlayer::updateOrCreate([
                'hand_id' => $hand->id,
                'player_id' => $player->id,
            ], [
                'result' => $collected - $contributed + $returned,
            ]);
        }

        return $hand;
    }

    protected function applyCardsToHand(Hand $hand, $lines)
    {
        foreach ($lines as $line) {
            // Hero hole cards
            if (preg_match('/Dealt to (\w+) \[([2-9TJQKA][hdcs]) ([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $playerName = $m[1];
                $cards = [$m[2], $m[3]];

                $playerName = $m[1];
                $cards = [$m[2], $m[3]];
                $this->storePlayerCards($hand, $playerName, $cards, 'hole');
            }
            // Showdown cards
            if (preg_match('/^(\w+): shows \[([2-9TJQKA][hdcs]) ([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $playerName = $m[1];
                $cards = [$m[2], $m[3]];
                $this->storePlayerCards($hand, $playerName, $cards, 'hole');
            }

            // Mucked cards
            if (preg_match('/^(\w+): mucks \[([2-9TJQKA][hdcs]) ([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $playerName = $m[1];
                $cards = [$m[2], $m[3]];
                $this->storePlayerCards($hand, $playerName, $cards, 'hole');
            }

            // Board cards
            if (preg_match('/\*\*\* FLOP \*\*\* \[([2-9TJQKA][hdcs]) ([2-9TJQKA][hdcs]) ([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $cards = [$m[1], $m[2], $m[3]];
                foreach ($cards as $card) {
                    $this->storeBoardCard($hand, $card, 'flop');
                }
            }

            // TURN
            if (preg_match('/\*\*\* TURN \*\*\* \[[^\]]+\] \[([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $this->storeBoardCard($hand, $m[1], 'turn');
            }

            // RIVER
            if (preg_match('/\*\*\* RIVER \*\*\* \[[^\]]+\] \[([2-9TJQKA][hdcs])\]/', $line, $m)) {
                $this->storeBoardCard($hand, $m[1], 'river');
            }
        }
    }

    protected function storePlayerCards(Hand $hand, string $playerName, array $cards, string $type): void
    {
        $player = Player::where('name', $playerName)->where('site_id', $this->site->id)->first();
        if (!$player) return;

        foreach ($cards as $card) {
            $cardModel = Card::firstOrCreate([
                'rank' => substr($card, 0, -1),
                'suit' => substr($card, -1),
            ]);

            HandCard::firstOrCreate([
                'hand_id' => $hand->id,
                'player_id' => $player->id,
                'card_id' => $cardModel->id,
                'context' => $type,
            ]);
        }
    }

    protected function storeBoardCard(Hand $hand, string $card, string $type): void
    {
        $cardModel = Card::firstOrCreate([
            'rank' => substr($card, 0, -1),
            'suit' => substr($card, -1),
        ]);

        HandCard::firstOrCreate([
            'hand_id' => $hand->id,
            'player_id' => null,
            'card_id' => $cardModel->id,
            'context' => $type,
        ]);
    }

    protected function extractHandNumber(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/PokerStars Hand #(\d+):/', $line, $m)) {
                return $m[1];
            }
        }
        return null;
    }


    protected function extractTimestamp(array $lines): ?Carbon
    {
        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) ET\]/', $line, $m)) {
                return Carbon::createFromFormat('Y/m/d H:i:s', $m[1], 'America/New_York')->setTimezone('UTC');
            }
        }
        return null;
    }

    protected function parseLine(string $line, Hand $hand, &$contributions, &$collections, &$returns, &$rake, &$street, &$actionOrder): void  
    {
        if (str_contains($line, '*** FLOP ***')) {
            $street = 1;
        }
        if (str_contains($line, '*** TURN ***')) {
            $street = 2;
        }
        if (str_contains($line, '*** RIVER ***')) {
            $street = 3;
        }

        $playerName = explode(':', $line)[0] ?? null;

        $patterns = [
            'raises' => '/raises \$([\d\.]+) to \$([\d\.]+)/',
            'bets'   => '/bets \$([\d\.]+)/',
            'calls'  => '/calls \$([\d\.]+)/',
            'posts'  => '/posts (?:small|big) blind \$([\d\.]+)/',
            'folds'  => '/folds/',
            'checks' => '/checks/',
        ];

        foreach ($patterns as $action => $regex) {
            if (str_contains($line, $action)) {
                if (preg_match($regex, $line, $m)) {
                    $amount = isset($m[1]) ? (float)$m[1] : null;

                    if ($action === 'raises' && isset($m[2])) {
                        $amount = (float)$m[2]; // use the final raise amount
                    }
                } else {
                    $amount = null;
                }

                if($this->heroPlayer->name !== $playerName) 
                {
                    $playerModel = Player::firstOrCreate([
                        'name' => $playerName,
                        'site_id' => $this->session->site_id,
                    ]);
                } else {
                    $playerModel = $this->heroPlayer;
                }

                HandAction::create([
                    'hand_id' => $hand->id,
                    'player_id' => $playerModel->id,
                    'action' => $action,
                    'amount' => $amount,
                    'street' => $street,
                    'action_order' => $actionOrder++,
                ]);

                break;
            }
        }
        if (preg_match('/posts (small|big) blind \$(\d+\.\d+)/', $line, $m)) {
            $contributions[$playerName] = ($contributions[$playerName] ?? 0) + (float)$m[2];
        }

        if (preg_match('/(calls|bets|raises).*\$(\d+\.\d+)/', $line, $m)) {
            $contributions[$playerName] = ($contributions[$playerName] ?? 0) + (float)$m[2];
        }

        if (preg_match('/Uncalled bet \(\$(\d+\.\d+)\) returned to (\w+)/', $line, $m)) {
            $returns[$m[2]] = ($returns[$m[2]] ?? 0) + (float)$m[1];
        }

        if (preg_match('/^(\w+): collected \(\$(\d+\.\d+)\)/', $line, $m)) {
            $collector = $m[1];
            $amount = (float) $m[2];

            $collections[$collector] = ($collections[$collector] ?? 0) + $amount;
        } elseif (preg_match('/^(\w+)\s+collected\s+\$(\d+\.\d+)/', $line, $m)) {
            $collector = $m[1];
            $amount = (float) $m[2];

            $collections[$collector] = ($collections[$collector] ?? 0) + $amount;
        }
        if (preg_match('/Rake \$(\d+\.\d+)/', $line, $m)) {
            $rake = (float)$m[1];
        }
    }

    protected function extractHeroName(string $text): ?string
    {
        if (preg_match('/Dealt to (\w+) \[.*\]/', $text, $match)) {
            return $match[1];
        }
        return null;
    }
}
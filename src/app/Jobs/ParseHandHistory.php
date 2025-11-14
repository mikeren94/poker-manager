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

        Log::info('🟢 ParseHandHistory job booted', [
            'path' => $this->path,
            'user' => $this->user->id,
        ]);
    }

    public function handle(): void
    {
        try {
            $this->handlePokerstarsHistory();
        } catch (Throwable $e) {
            Log::info('Hand history parsing failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function handlePokerstarsHistory(): void
    {
        Log::info('📂 Checking file existence', [
            'resolved_path' => storage_path("app/{$this->path}"),
            'exists' => Storage::exists($this->path),
        ]);

        if (!Storage::exists($this->path)) {
            Log::error('❌ Hand history file not found', ['path' => $this->path]);
            return;
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', Storage::get($this->path));
        Log::info('📄 Loaded hand history content', [
            'length' => strlen($content),
            'preview' => substr($content, 0, 200),
        ]);

        $hands = array_filter(preg_split('/(?=PokerStars Hand #\d+)/', $content));
        Log::info('🧩 Split into individual hands', ['count' => count($hands)]);

        $this->site = Site::firstOrCreate(['name' => 'PokerStars']);


        foreach($hands as $handText) 
        {
            Log::info('📝 Parsing individual hand', [
                'preview' => substr($handText, 0, 100),
            ]);
            $this->parseHand($handText);
        }

        # Calculate the total session profit
        $result = HandPlayer::whereHas('hand', function ($query) {
            $query->where('game_session_id', $this->session->id); // assuming you're inside a Session model
        })
        ->whereIn('player_id', $this->user->playerIds)
        ->where('result', '!=', 0)
        ->sum('result');

        if (!$this->session) {
            Log::error('❌ Session is null before profit calculation', ['path' => $this->path]);
        }
        $this->session->net_profit = $result;
        $this->session->save();
    }

    public function parseHand(string $text): Hand
    {
        Log::info('🃏 parseHand started', [
            'line_count' => count(explode("\n", $text)),
        ]);


        $lines = explode("\n", $text);

        $heroName = $this->extractHeroName($text);
        $this->heroPlayer = Player::firstOrCreate([
            'name' => $heroName,
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
        ]);

        Log::info('🧍 Hero name extracted', [
            'hero' => $this->heroPlayer->name ?? 'null',
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

            preg_match('/\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}/', $firstLine, $match);

            $rawTimestamp = $match[0] ?? null;

            $startTime = $rawTimestamp
                ? Carbon::createFromFormat('Y/m/d H:i:s', $rawTimestamp, 'America/New_York')->setTimezone('UTC')
                : null;

            Log::info('📊 Creating new session', [
                'stakes' => $stakes ?? 'null',
                'table' => $tableName ?? 'null',
                'session_id' => $sessionId ?? 'null',
                'start_time' => $startTime?->toDateTimeString() ?? 'null',
            ]);

            $this->session = Session::firstOrCreate([
                'session_id' => $sessionId,
                'type' => $this->gameType,
                'stakes' => $stakes,
                'start_time' => $startTime,
                'player_id' => $this->heroPlayer->id,
                'site_id' => $this->site->id
            ]);
        }
        $bbSize = $this->extractBbSize($text);
        Log::info('✅ Session created or reused', [
            'session_id' => $this->session->session_id ?? 'null',
            'session_db_id' => $this->session->id ?? 'null',
        ]);

        Log::info('🎯 Creating hand record', [
            'hand_number' => $this->extractHandNumber($lines),
        ]);

        $hand = Hand::firstOrCreate(
            [
                'game_session_id' => $this->session->id,
                'hand_number' => $this->extractHandNumber($lines),
            ],
            [
                'timestamp' => $this->extractTimestamp($lines),
                'raw_text' => $text,
                'bb_size' => $bbSize,
            ]
        );
        
        $playerContributions = [];
        $playerCollections = [];
        $uncalledReturns = [];
        $showdownPlayers = [];

        $rake = 0;

        $street = 0; // 0 = preflop
        $actionOrder = 0;
        foreach ($lines as $line) {
            if (preg_match('/^Seat \d+: (\w+)/', $line, $m)) {
                $seatedPlayers[] = $m[1];
            }

            $this->parseLine($line, $hand, $playerContributions, $playerCollections, $uncalledReturns, $rake, $street, $actionOrder, $showdownPlayers);
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
            
            Log::info('💰 Storing HandPlayer result', [
                'player' => $name,
                'contributed' => $contributed,
                'collected' => $collected,
                'returned' => $returned,
                'net_result' => $collected - $contributed + $returned,
            ]);
            HandPlayer::updateOrCreate([
                'hand_id' => $hand->id,
                'player_id' => $player->id,
            ], [
                'result' => $collected - $contributed + $returned,
                'showdown' => in_array($name, $showdownPlayers)
            ]);
        }

        return $hand;
    }

    protected function extractBbSize(string $text): float
    {
        // Example line: "Hold'em No Limit ($0.01/$0.02 USD)"
        preg_match('/\((\$?\d+(\.\d+)?\/\$?\d+(\.\d+)?)\s*USD\)/', $text, $matches);

        if (isset($matches[1])) {
            $parts = explode('/', $matches[1]);
            return isset($parts[1]) ? (float) str_replace('$', '', $parts[1]) : 0.02;
        }

        return 0.02; // fallback default
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

            if (preg_match('/(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) WET/', $line, $m)) {
                return Carbon::createFromFormat('Y/m/d H:i:s', $m[1], 'Europe/Lisbon')->setTimezone('UTC');
            }
        }

        return null;
    }

    protected function parseLine(string $line, Hand $hand, &$contributions, &$collections, &$returns, &$rake, &$street, &$actionOrder, &$showdownPlayers): void    {
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

        if (preg_match('/^(\w+): shows \[.*?\]/', $line, $m)) {
            $showdownPlayers[] = $m[1];
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
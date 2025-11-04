<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Models\Session;
use App\Models\Site;
use App\Models\Player;
use App\Models\Hand;
use App\Models\HandAction;
use App\Models\HandCard;
use App\Models\Card;
use App\Models\HandPlayer;
use App\Models\User;

class ParseHandHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;
    protected User $user;
    protected string $gameType = 'tournament';
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
        Log::info("ParseHandHistory job started", ['path' => $this->path, 'user_id' => $this->user->id]);
        $this->handlePokerstarsHistory();
    }

    protected function handlePokerstarsHistory(): void
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', Storage::get($this->path));
        $hands = array_filter(preg_split('/(?=PokerStars Hand #\d+)/', $content));

        $this->site = Site::firstOrCreate(['name' => 'PokerStars']);

        foreach ($hands as $handText) {
            try {
                $handText = str_replace("\r\n", "\n", $handText);
                if (trim($handText) === '') continue;

                $this->parseHand($handText);
            } catch (\Throwable $e) {
                Log::error("Failed to parse hand", [
                    'handText' => substr($handText, 0, 500),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function parseHand(string $handText): void
    {
        $handId = $this->extractHandId($handText);
        $heroName = $this->extractHeroName($handText);
        $timestamp = $this->extractTimestamp($handText);

        if (is_null($this->heroPlayer)) {

            $this->heroPlayer = Player::firstOrCreate([
                'name' => $heroName,
                'site_id' => $this->site->id,
                'user_id' => $this->user->id
            ]);
        }

        if (!$this->session) {
            $this->initializeSession($handText);
        }

        [$potSize, $rake] = $this->extractPotAndRake($handText);
        $bbSize = $this->extractBbSize($handText);

        $hand = Hand::firstOrCreate(
            ['hand_number' => $handId],
            [
                'game_session_id' => $this->session->id,
                'timestamp' => $timestamp,
                'pot_size' => $potSize,
                'rake' => $rake,
                'bb_size' => $bbSize,
                'raw_text' => $handText
            ]
        );

        $this->parseActionsAndBoard($handText, $hand);
        $this->storeHoleCards($handText, $hand);
        $this->storeHandPlayers($handText, $hand);
    }

    protected function extractHandId(string $text): ?string
    {
        preg_match('/PokerStars Hand #(\d+)/', $text, $match);
        return $match[1] ?? null;
    }

    protected function extractHeroName(string $text): ?string
    {
        preg_match('/Dealt to\s+([^\[]+)\s+\[/', $text, $match);
        return isset($match[1]) ? trim($match[1]) : null;
    }

    protected function extractTimestamp(string $text): ?string
    {
        preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $text, $match);
        return $match[1] ?? null;
    }

    protected function extractPotAndRake(string $text): array
    {
        if ($this->gameType !== 'tournament') {
            preg_match('/Total pot \$(\d+\.\d{2})/', $text, $potMatch);
            preg_match('/Rake \$(\d+\.\d{2})/', $text, $rakeMatch);
            return [(float) ($potMatch[1] ?? 0), (float) ($rakeMatch[1] ?? 0)];
        }

        preg_match('/Total pot (\d+)/', $text, $match);
        return [(float) ($match[1] ?? 0), null];
    }

    protected function normalizeAction(string $action): string
    {
        $action = strtolower(trim($action));

        return match (true) {
            str_contains($action, 'doesn\'t show') => 'muck',
            str_contains($action, 'shows') => 'show',
            str_contains($action, 'mucked') => 'muck',
            str_contains($action, 'folds') => 'fold',
            str_contains($action, 'calls') => 'call',
            str_contains($action, 'bets') => 'bet',
            str_contains($action, 'raises') => 'raise',
            str_contains($action, 'checks') => 'check',
            str_contains($action, 'posts') => 'post',
            default => $action,
        };
    }

    protected function getCardFromDb(array $split): Card
    {
        return Card::firstOrCreate([
            'rank' => $split[0],
            'suit' => $split[1],
        ]);
    }

    protected function parseActionsAndBoard(string $handText, Hand $hand): void
    {
        $streets = [
            'preflop' => '/\*\*\* HOLE CARDS \*\*\*(.*?)\*\*\*/s',
            'flop'    => '/\*\*\* FLOP \*\*\*.*?\n(.*?)\*\*\*/s',
            'turn'    => '/\*\*\* TURN \*\*\*.*?\n(.*?)\*\*\*/s',
            'river'   => '/\*\*\* RIVER \*\*\*.*?\n(.*?)\*\*\*/s',
        ];

        foreach ($streets as $streetName => $pattern) {
            preg_match($pattern, $handText, $streetActions);

            if (in_array($streetName, ['flop', 'turn', 'river'])) {
                if (preg_match('/\*\*\* ' . strtoupper($streetName) . ' \*\*\*.*?(\[[^\]]+\])$/m', $handText, $boardMatch)) {
                    $cards = explode(' ', trim(trim($boardMatch[1], '[]')));
                    foreach ($cards as $matchedCard) {
                        $split = str_split($matchedCard);
                        $card = $this->getCardFromDb($split);

                        HandCard::firstOrCreate([
                            'context' => $streetName,
                            'hand_id' => $hand->id,
                            'card_id' => $card->id,
                        ]);
                    }
                }
            }

            if (!empty($streetActions[1])) {
                $lines = explode("\n", trim($streetActions[1]));
                foreach ($lines as $i => $line) {
                    if (preg_match('/^(.+?): ([^\$]+?)(?: \$?([\d\.]+))?$/', $line, $match)) {
                        $playerName = $match[1];
                        $action = $this->normalizeAction($match[2]);
                        $amount = isset($match[3]) ? (float) $match[3] : null;

                        $player = Player::firstOrCreate([
                            'name' => $playerName,
                            'site_id' => $this->site->id,
                        ]);

                        HandAction::create([
                            'hand_id' => $hand->id,
                            'player_id' => $player->id,
                            'action' => $action,
                            'amount' => $amount,
                            'street' => array_search($streetName, array_keys($streets)),
                            'action_order' => $i,
                        ]);
                    }
                }
            }
        }
    }

    protected function storeHoleCards(string $handText, Hand $hand): void
    {
        // Hero hole cards
        preg_match('/Dealt to (\w+) \[([^\]]+)\]/', $handText, $match);
        $cards = explode(' ', $match[2] ?? '');

        foreach ($cards as $foundCard) {
            $split = str_split($foundCard);
            $card = $this->getCardFromDb($split);

            HandCard::firstOrCreate([
                'context' => 'hole',
                'hand_id' => $hand->id,
                'card_id' => $card->id,
                'player_id' => $this->heroPlayer->id
            ]);
        }

        // Other players' shown or mucked cards
        preg_match_all('/(\w+): (?:shows|mucked) \[([^\]]+)\]/', $handText, $matches1, PREG_SET_ORDER);
        preg_match_all('/Seat \d+: (\w+).*?mucked \[([^\]]+)\]/', $handText, $matches2, PREG_SET_ORDER);

        $rawMatches = array_merge($matches1, $matches2);
        $filteredMatches = array_filter($rawMatches, fn($m) => count($m) >= 3);

        foreach ($filteredMatches as $match) {
            try {
                $playerName = $match[1];
                $cardString = $match[2];

                if ($playerName === $this->heroPlayer->name) continue;

                $player = Player::firstOrCreate([
                    'name' => $playerName,
                    'site_id' => $this->site->id,
                ]);

                foreach (explode(' ', $cardString) as $foundCard) {
                    $split = str_split($foundCard);
                    $card = $this->getCardFromDb($split);

                    HandCard::firstOrCreate([
                        'context' => 'hole',
                        'hand_id' => $hand->id,
                        'card_id' => $card->id,
                        'player_id' => $player->id
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to store hole cards', [
                    'match' => $match,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function storeHandPlayers(string $handText, Hand $hand): void
    {
        preg_match('/Dealt to (\w+) \[/', $handText, $heroMatch);
        $heroName = $heroMatch[1] ?? null;

        preg_match('/(\w+) collected \$([\d\.]+)/', $handText, $winnerMatch);
        $winnerName = $winnerMatch[1] ?? null;
        $winnerAmount = isset($winnerMatch[2]) ? (float) $winnerMatch[2] : 0;

        preg_match_all('/Seat (\d+): (.+?) \((\$[\d\.]+) in chips\)(?: is sitting out)?/', $handText, $seats, PREG_SET_ORDER);
        $seatMap = collect($seats)->mapWithKeys(fn($s) => [(int) $s[1] => $s[2]]);
        $orderedSeats = collect($seatMap)->sortKeys()->values()->all();

        $positions = $this->generatePositionLabels(count($orderedSeats));
        $buttonSeat = preg_match("/Seat #(\d+) is the button/", $handText, $btnMatch) ? (int) $btnMatch[1] : null;
        $buttonIndex = array_search($seatMap[$buttonSeat], $orderedSeats);
        $rotated = array_merge(array_slice($orderedSeats, $buttonIndex), array_slice($orderedSeats, 0, $buttonIndex));
        $positionMap = array_combine($rotated, $positions);

        $playerContributions = HandAction::where('hand_id', $hand->id)
            ->whereIn('action', ['call', 'bet', 'raise', 'post'])
            ->get()
            ->groupBy('player_id')
            ->map(fn($actions) => $actions->sum('amount'));

        // Detect players who reached showdown
        $showdownPlayers = collect();

        // Match lines like: "PlayerX: shows [Ah Kh]"
        preg_match_all('/^(\w+): shows \[.*?\]/m', $handText, $showMatches, PREG_SET_ORDER);
        foreach ($showMatches as $match) {
            $showdownPlayers->push($match[1]);
        }

        foreach ($seats as $seat) {
            [$_, $seatNum, $playerName, $chipStr] = $seat;

            $player = Player::firstOrCreate([
                'name' => $playerName,
                'site_id' => $this->site->id,
            ]);

            $contributed = $playerContributions[$player->id] ?? 0.00;
            $won = ($playerName === $winnerName) ? $winnerAmount : 0.00;
            $netResult = $won - $contributed;

            HandPlayer::firstOrCreate([
                'hand_id' => $hand->id,
                'player_id' => $player->id,
            ], [
                'position' => $positionMap[$playerName] ?? null,
                'is_hero' => $playerName === $heroName,
                'is_winner' => $playerName === $winnerName,
                'result' => $netResult,
                'action' => null,
                'showdown' => $showdownPlayers->contains($playerName)
            ]);
        }
    }

    protected function generatePositionLabels(int $count): array
    {
        return match ($count) {
            2 => ['SB', 'BB'],
            3 => ['BTN', 'SB', 'BB'],
            4 => ['CO', 'BTN', 'SB', 'BB'],
            5 => ['MP', 'CO', 'BTN', 'SB', 'BB'],
            6 => ['UTG', 'MP', 'CO', 'BTN', 'SB', 'BB'],
            7 => ['UTG', 'UTG+1', 'MP', 'CO', 'BTN', 'SB', 'BB'],
            8 => ['UTG', 'UTG+1', 'UTG+2', 'MP', 'CO', 'BTN', 'SB', 'BB'],
            9 => ['UTG', 'UTG+1', 'UTG+2', 'MP', 'MP+1', 'CO', 'BTN', 'SB', 'BB'],
            default => array_pad([], $count, null),
        };
    }

    protected function initializeSession(string $handText): void
    {
        $isTournament = preg_match('/Tournament #\(\d+\)/', $handText);

        if (!$isTournament) {
            preg_match("/Table '(.+?)'/", $handText, $tableMatch);
            $tableName = $tableMatch[1] ?? 'UnknownTable';

            preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $handText, $timeMatch);
            $timestamp = $timeMatch[1] ?? now()->format('Y-m-d H:i:s');

            $sessionId = "$tableName $timestamp";
            $this->gameType = 'cash';

            preg_match('/\((\$[\d\.]+)\/(\$[\d\.]+) USD\)/', $handText, $match);
            $smallBlind = $match[1] ?? '$0.01';
            $bigBlind = $match[2] ?? '$0.02';
            $stakes = "($smallBlind / $bigBlind)";

            preg_match("/Seat \d+: {$this->heroPlayer->name} \(\$([\d\.]+) in chips\)/", $handText, $match);
            $initialBuyIn = isset($match[1]) ? (float) $match[1] : null;
        } else {
            preg_match('/Tournament #\d+, \$(\d+\.\d+)\+\$(\d+\.\d+) USD.*\((\d+)\/(\d+)\)/', $handText, $match);
            $buyIn = isset($match[1]) ? (float) $match[1] : 0;
            $fee = isset($match[2]) ? (float) $match[2] : 0;
            $stakes = $buyIn + $fee;
            $initialBuyIn = $stakes;

            preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $handText, $match);
            $timestamp = $match[1] ?? now()->format('Y-m-d H:i:s');
            $sessionId = "Tournament $timestamp";
        }

        $this->session = Session::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'player_id' => $this->heroPlayer->id,
                'site_id' => $this->site->id,
                'type' => $this->gameType,
                'stakes' => $stakes,
                'start_time' => $timestamp,
                'end_time' => null,
                'buy_in' => $initialBuyIn,
                'cash_out' => null,
                'net_profit' => null,
            ]
        );
    }

    protected function extractBbSize(string $text): float
    {
        preg_match('/No Limit \(\$?(\d+(?:\.\d+)?)\/\$?(\d+(?:\.\d+)?)\s*USD\)/', $text, $matches);
        return isset($matches[2]) ? (float) $matches[2] : 2.00;
    }
}
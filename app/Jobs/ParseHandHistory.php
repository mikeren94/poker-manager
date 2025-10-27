<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

use App\Models\Session;
use App\Models\Site;
use App\Models\Player;
use App\Models\Hand;

class ParseHandHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;
    protected string $gameType = 'tournemant';
    protected $session;
    protected $heroPlayer;
    protected $site;
    /**
     * Create a new job instance.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->handlePokerstarsHistory();
    }

    public function handlePokerstarsHistory()
    {
        $content = Storage::get($this->path);

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Now split into hands
        $rawHands = preg_split('/(?=PokerStars Hand #\d+)/', $content);
        $hands = array_filter($rawHands);
        // As we are in the pokerstars function we know that the site is going to be pokerstars,
        // We are going to add it to the DB if it hasn't already been and use the ID for site_id
        $this->site = Site::firstOrCreate(
            ['name' => 'PokerStars'],
        );

        foreach($hands as $handText) {
            $handText = str_replace("\r\n", "\n", $handText);
            // skip empty entries
            if (trim($handText) === '') continue;

            // Get the hand ID
            preg_match('/PokerStars Hand #(\d+)/', $handText, $id);
            $handId = $id[1] ?? null;

            // Get the player ID
            preg_match('/Dealt to\s+([^\[]+)\s+\[/', $handText, $match);
            $username = isset($match[1]) ? trim($match[1]) : null;

            // If the user doesn't exist, fetch/create from the database
            if (!$this->heroPlayer) {
                $this->heroPlayer = Player::firstOrCreate([
                    'name' => $username,
                    'site_id' => $this->site->id
                ]);
            }
            // If we haven't created the session yet we need to determine if this is a tournment or cash game
            if (!$this->session) {
                $this->initializeSession($handText);
            }

            preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $handText, $match);
            $timestamp = $match[1] ?? null;
                

            // If this is not a tournament we need to figure out the cash value won
            if($this->gameType !== 'tournament') {
                preg_match('/Total pot \$(\d+\.\d{2})/', $handText, $potMatch);
                $potSize = isset($potMatch[1]) ? (float) $potMatch[1] : null;

                preg_match('/Rake \$(\d+\.\d{2})/', $handText, $rakeMatch);
                $rake = isset($rakeMatch[1]) ? (float) $rakeMatch[1] : 0;
            } else {
                preg_match('/Total pot (\d+)/', $handText, $match);
                $potSize = isset($match[1]) ? (float) $match[1] : null;
                $rake = null;
            }

            // Determine if the hand made it to showdown
            $showdown = preg_match('/shows \[.*?\]/', $handText);

            // We are now going to add the hand history for the session
            $hand = Hand::firstOrCreate(
                ['hand_number' => $handId],
                [
                    'game_session_id' => $this->session->id,
                    'timestamp' => $timestamp,
                    'pot_size' => $potSize,
                    'rake' => $rake,
                    'showdown' => $showdown,
                ]
            );
        }
    }

    private function initializeSession($handText) {
        // Get the session ID if this is a tournament
        if (!preg_match('/Tournament #\(\d+\)/', $handText, $sessionId)) {
            // We didn't find a match for the tournemant so this is a cash game, we need to get the 
            // session for that
            // Get the table name
            preg_match("/Table '(.+?)'/", $handText, $tableMatch);
            $tableName = $tableMatch[1] ?? null;
            // get the date of the session
            preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $handText, $timeMatch);
            $timestamp = $timeMatch[1] ?? null;
            $sessionId = "$tableName $timestamp";
            // Change type to cash
            $this->gameType = 'cash';

            // Get the stakes from the notepad file
            preg_match('/\((\$[\d\.]+)\/(\$[\d\.]+) USD\)/', $handText, $match);
            $smallBlind = $match[1] ?? null;
            $bigBlind = $match[2] ?? null;
            $stakes = "($smallBlind / $bigBlind)";
            // The first time we see the player's username, the chip ammount is the amount they brought in for
            preg_match("/Seat \d+: {$this->heroPlayer->name} \(\$([\d\.]+) in chips\)/", $handText, $match);
            $initialBuyIn = isset($match[1]) ? (float) $match[1] : null;
        } else {
            // Get the tournament stakes
            preg_match('/Tournament #\d+, \$(\d+\.\d+)\+\$(\d+\.\d+) USD.*\((\d+)\/(\d+)\)/', $handText, $match);
            $buyIn = $match[1] ?? null;
            $fee = $match[2] ?? null;
            $stakes = $buyIn + $fee;
            // The buy in and fee is the total amount the player paid
            $initialBuyIn = $stakes;
        }

        preg_match('/- (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $handText, $match);
        $startTime = $match[1] ?? null;

        // Create the session
        $this->session = Session::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'player_id' => $this->heroPlayer->id,
                'site_id' => $this->site->id,
                'type' => $this->gameType,
                'stakes' => $stakes,
                'start_time' => $startTime,
                'end_time' => null,
                'buy_in' => $initialBuyIn,
                'cash_out' => null,
                'net_profit' => null,
            ]
        );

    }

    private function parsePokerstarsHand($hand)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $contents = Storage::get($this->path);
    }
}

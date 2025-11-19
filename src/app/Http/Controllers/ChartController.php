<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HandPlayer;
use App\Models\Hand;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChartController extends Controller
{

    public function profitChartData(Request $request)
    {
        $profitData = $this->profitOverTime($request);
        $showdownData  = $this->cumulativeWonOverTime($request, true);
        $nonShowdownData  = $this->cumulativeWonOverTime($request, false);

        // Index showdown data by date
        $showdownMap = collect($showdownData)->keyBy('date');
        $nonShowdownMap = collect($nonShowdownData)->keyBy('date');

        $lastShowdown = 0;
        $lastNonShowdown = 0;

        $merged = collect($profitData)->map(function ($entry) use ($showdownMap, $nonShowdownMap, &$lastShowdown, &$lastNonShowdown) {
            if (isset($showdownMap[$entry['date']])) {
                $lastShowdown = $showdownMap[$entry['date']]['won_at_showdown'];
            }
            if (isset($nonShowdownMap[$entry['date']])) {
                $lastNonShowdown = $nonShowdownMap[$entry['date']]['won_without_showdown'];
            }

            return [
                'date' => $entry['date'],
                'profit' => $entry['profit'],
                'won_at_showdown' => $lastShowdown,
                'won_without_showdown' => $lastNonShowdown,
            ];
        });

        return response()->json($merged->values());
    }


    public function profitOverTime(Request $request)
    {
        $user = $request->user();

        $playerIds = $user->playerIds;

        $raw = HandPlayer::whereIn('player_id', $playerIds)
            ->where('result', '!=', '0')
            ->join('hands', 'hands.id', '=', 'hand_players.hand_id')
            ->orderBy('hands.timestamp')
            ->select('hand_players.*', 'hands.timestamp') // include timestamp for mapping
            ->get()
            ->map(fn($hp) => [
                'date' => Carbon::parse($hp->timestamp)->format('Y-m-d H:i:s'),
                'result' => $hp->result ?? 0,
            ]);
        $cumulative = [];
        $total = 0;

        foreach ($raw as $entry) {
            $total += $entry['result'];
            $cumulative[] = [
                'date' => $entry['date'],
                'profit' => round($total, 2),
            ];
        }
        return $cumulative;
    }

    private function cumulativeWonOverTime(Request $request, bool $showdown)
    {
        $user = $request->user();
        $playerIds = $user->playerIds;

        $raw = HandPlayer::whereIn('player_id', $playerIds)
            ->where('result', '!=', 0)
            ->where('showdown', $showdown)
            ->join('hands', 'hands.id', '=', 'hand_players.hand_id')
            ->orderBy('hands.timestamp')
            ->select('hand_players.*', 'hands.timestamp')
            ->get()
            ->map(fn($hp) => [
                'date' => Carbon::parse($hp->timestamp)->format('Y-m-d H:i:s'),
                'result' => $hp->result ?? 0,
            ]);

        $cumulative = [];
        $total = 0;

        foreach ($raw as $entry) {
            $total += $entry['result'];
            $cumulative[] = [
                'date' => $entry['date'],
                $showdown ? 'won_at_showdown' : 'won_without_showdown' => round($total, 2),
            ];
        }

        return $cumulative;
    }
}

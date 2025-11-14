<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HandPlayer;
use App\Models\Hand;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChartController extends Controller
{
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
        return response()->json($cumulative);
    }
}

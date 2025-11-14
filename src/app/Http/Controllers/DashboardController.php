<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\HandPlayer;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $playerIds = $user->players()->pluck('id');

        $profit = $user->totalProfit;
        $vpip = $user->vpip;
        $rakePaid = $user->rakePaid;
        $winRate = $user->winRate;
        $handsPlayed = HandPlayer::whereIn('player_id', $playerIds)->where('result', '!=', 0)->count();
        $totalHands = HandPlayer::whereIn('player_id', $playerIds)->count();
        $showdownsWonPercent = $user->showdownsWonPercent;
        return Inertia::render('Dashboard', [
            'summary' => [
                'profit' => $profit,
                'vpip' => $vpip,
                'rake_paid' => $rakePaid,
                'hands_played' => $handsPlayed,
                'total_hands' => $totalHands,
                'win_rate' => $winRate,
                'showdowns_won_percent' => $showdownsWonPercent
            ]
        ]);
    }
}

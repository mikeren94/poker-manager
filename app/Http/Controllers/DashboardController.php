<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\HandPlayer;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $playerIds = $user->players()->pluck('id');

        $profit = HandPlayer::whereIn('player_id', $playerIds)->sum('result');
        $vpip = 0;
        $rakePaid = 0;
        $handsPlayed = 0;

        return Inertia::render('Dashboard', [
            'summary' => [
                'profit' => $profit,
                'vpip' => $vpip,
                'rake_paid' => $rakePaid,
                'hands_played' => $handsPlayed,
            ]
        ]);
    }
}

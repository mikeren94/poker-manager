<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $sessions = Session::whereIn('player_id', $user->playerIds)
            ->orderByDesc('start_time')
            ->paginate(env('DEFAULT_PAGINATION'));

        return response()->json($sessions);
    }

    public function list(Session $session)
    {
        $user = Auth::user();
        $hands = $session
            ->hands()
            ->whereHas('hand_players', function ($query) use ($user) {
                $query->whereHas('player', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('result', '!=', 0); // Filter hands by result
            })
            ->with([
                'hand_cards' => function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('player', function ($sub) use ($user) {
                            $sub->where('user_id', $user->id);
                        })
                        ->orWhere(function ($sub) {
                            $sub->whereNull('player_id')
                                ->whereIn('context', ['flop', 'turn', 'river']);
                        });
                    })->with('card');
                },
                'hand_players' => function ($query) use ($user) {
                    $query->whereHas('player', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->where('result', '!=', 0); // Only load relevant hand_players
                },
                'session.player',
                'session.site',
            ])
            ->paginate(env('DEFAULT_PAGINATION'));

         return response()->json($hands);  
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSessionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Session $session)
    {
        return Inertia::render('Session', [
            'session' => $session
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Session $session)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSessionRequest $request, Session $session)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Session $session)
    {
        //
    }
}

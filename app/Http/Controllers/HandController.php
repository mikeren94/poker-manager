<?php

namespace App\Http\Controllers;

use App\Models\Hand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHandRequest;
use App\Http\Requests\UpdateHandRequest;
use App\Jobs\ParseHandHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
class HandController extends Controller
{
    /**
     * Handle the hand history upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'hand_history' => 'required|array',
            'hand_history.*' => 'file|mimes:txt|max:2048'
        ]);

        $uploadedFiles = $request->file('hand_history');
        $results = [];

        foreach ($uploadedFiles as $file) {
            $text = file_get_contents($file->getRealPath());

            if (!$this->validatePokerStarsCashGame($text)) {
                throw ValidationException::withMessages([
                    'hand_history' => 'Only PokerStars cash game hand histories are supported.',
                ]);
            }

            $filename = uniqid('hand_') . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('hand_histories', $filename);

            ParseHandHistory::dispatch($path, Auth::user());

            $results[] = [
                'filename' => $filename,
                'path' => $path
            ];
        }

        return response()->json([
            'message' => 'Upload successful',
            'files' => $results
        ]);

    }

    protected function validatePokerStarsCashGame(string $text): bool 
    {
        // PokerStars cash games usually start with something like:
        // "PokerStars Hand #1234567890:  Hold'em No Limit ($0.05/$0.10 USD)"
        return preg_match('/^PokerStars Hand #[0-9]+:.*Hold\'em.*(No Limit|Pot Limit)/', $text);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $hands = Hand::whereHas('session.player', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereHas('hand_players', function ($query) use ($user) {
            $query->whereHas('player', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('result', '!=', 0); // Only include hands with non-zero result
        })
        ->with([
            'session.player',
            'session.site',
            'hand_players' => function ($query) use ($user) {
                $query->whereHas('player', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('result', '!=', 0); // Also filter loaded hand_players
            },
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
            }
        ])->paginate(20);
            
        return response()->json([
            'data' => $hands->items(),
            'current_page' => $hands->currentPage(),
            'last_page' => $hands->lastPage(),
            'per_page' => $hands->perPage(),
            'total' => $hands->total(),
        ]);    
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
    public function store(StoreHandRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Hand $hand)
    {
        $hand->load([
            'session.player',
            'session.site',
            'hand_players.player',
            'hand_cards.card',
            'hand_actions'
        ]);

        return Inertia::render('Hand', [
            'hand' => $hand,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hand $hand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHandRequest $request, Hand $hand)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hand $hand)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        $userFeedbackCount = Feedback::where('user_id', $userId)->count();

        return Inertia::render('Feedback', [
            'userId' => $userId,
            'feedbackCount' => $userFeedbackCount,
        ]);
    }

    public function list($userId)
    {
        $feedback = Feedback::where('user_id', $userId)
            ->with('replies')
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($feedback);
    }

    public function reply(StoreFeedbackRequest $request, Feedback $feedback)
    {
        try {
            $reply = $feedback->replies()->create([
                'user_id' => $request->user()->id,
                'message' => $request->input('message'),
            ]);

            return response()->json([
                'message' => 'Reply submitted successfully.',
                'success' => true,
            ]);   
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
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
    public function store(StoreFeedbackRequest $request)
    {
        try {
            $feedback = Feedback::create([
                'user_id' => $request->user()->id,
                'message' => $request->input('feedback'),
            ]);

            return response()->json([
                'message' => 'Feedback submitted successfully.',
                'success' => true,
            ]);   
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user, Feedback $feedback)
    {
        return Inertia::render('FeedbackThread', [
            'userId' => Auth::id(),
            'feedback' => $feedback->with([
                'user:id,name',
                'replies.user:id,name'
            ])->find($feedback->id),        
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        //
    }
}

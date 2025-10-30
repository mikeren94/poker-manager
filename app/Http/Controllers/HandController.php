<?php

namespace App\Http\Controllers;

use App\Models\Hand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHandRequest;
use App\Http\Requests\UpdateHandRequest;
use App\Jobs\ParseHandHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HandController extends Controller
{
    /**
     * Handle the hand history upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'hand_history' => 'required|file|mimes:txt|max:2048'
        ]);

        $file = $request->file('hand_history');
        $filename = uniqid('hand_') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('hand_histories', $filename);

        // Dispatch the job
        ParseHandHistory::dispatch($path, Auth::user());
        
        return response()->json([
            'message' => 'Upload successful',
            'filename' => $filename,
            'path' => $path
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
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
        //
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

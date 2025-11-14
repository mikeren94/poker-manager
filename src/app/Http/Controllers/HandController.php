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
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class HandController extends Controller
{
    /**
     * Handle the hand history upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'hand_history' => 'required|array',
            'hand_history.*' => 'file|mimes:txt,zip|max:10240',
        ]);

        $uploadedFiles = $request->file('hand_history');
        $successful = [];
        $failed = [];

        foreach ($uploadedFiles as $file) {
            try {
                if ($file->getClientOriginalExtension() === 'zip') {
                    $this->extractZipAndProcess($file, $successful, $failed);
                } else {
                    $result = $this->processTextFile($file);
                    $successful[] = $result;
                }
            } catch (ValidationException $ve) {
                Log::warning("Validation failed for {$file->getClientOriginalName()}: " . implode(', ', $ve->errors()['hand_history']));
                $failed[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => implode(', ', $ve->errors()['hand_history'])
                ];
            } catch (\Exception $e) {
                Log::error("Failed to process {$file->getClientOriginalName()}: " . $e->getMessage());
                $failed[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => 'An unexpected error occurred while processing the file.'
                ];
            }
        }

        return response()->json([
            'message' => count($failed) > 0
                ? 'Upload completed with some errors.'
                : 'Upload successful.',
            'successful' => $successful,
            'failed' => $failed
        ]);
    }

    protected function processTextFile($file)
    {
        $text = file_get_contents($file->getRealPath());

        if (!$this->validatePokerStarsCashGame($text)) {
            throw ValidationException::withMessages([
                'hand_history' => 'All files must be PokerStars cash game hand histories.',
            ]);
        }

        $filename = uniqid('hand_') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('/hand_histories', $filename);

        Log::info("Dispatching ParseHandHistory for file: {$filename}");
        ParseHandHistory::dispatch($path, Auth::user());

        return [
            'filename' => $filename,
            'path' => $path
        ];
    }

    protected function validatePokerStarsCashGame(string $text): bool
    {
        // Must contain a cash game header
        $isCashGame = preg_match(
            '/^PokerStars Hand #[0-9]+: +Hold\'em (No Limit|Pot Limit) +\(\$\d+\.\d{2}\/\$\d+\.\d{2} USD\)/m',
            $text
        );

        // Must NOT contain the word "Tournament" in the header
        $isTournament = preg_match('/^PokerStars Hand #[0-9]+: +Tournament/m', $text);

        return $isCashGame && !$isTournament;
    }

    protected function extractZipAndProcess(UploadedFile $zipFile, array &$successful, array &$failed)
    {
        $zip = new \ZipArchive;
        $path = $zipFile->getRealPath();
        $tmpDir = storage_path('app/tmp_zip_' . uniqid());

        mkdir($tmpDir);

        if ($zip->open($path) === true) {
            $zip->extractTo($tmpDir);
            $zip->close();

            foreach (File::allFiles($tmpDir) as $file) {
                if ($file->getExtension() !== 'txt') continue;

                try {
                    // Copy file to hand_histories with a unique name
                    $newFilename = uniqid('hand_') . '.txt';
                    $targetPath = storage_path("app/hand_histories/{$newFilename}");
                    File::copy($file->getRealPath(), $targetPath);

                    // Create a new UploadedFile instance from the copied file
                    $uploaded = new UploadedFile(
                        $targetPath,
                        $newFilename,
                        'text/plain',
                        null,
                        true
                    );

                    $result = $this->processTextFile($uploaded);
                    $successful[] = $result;
                } catch (ValidationException $ve) {
                    Log::warning("Validation failed for {$file->getFilename()}: " . implode(', ', $ve->errors()['hand_history']));
                    $failed[] = [
                        'filename' => $file->getFilename(),
                        'error' => implode(', ', $ve->errors()['hand_history'])
                    ];
                } catch (\Exception $e) {
                    Log::error("Failed to process {$file->getFilename()}: " . $e->getMessage());
                    $failed[] = [
                        'filename' => $file->getFilename(),
                        'error' => 'An unexpected error occurred while processing the file.'
                    ];
                }
            }

            File::deleteDirectory($tmpDir);
        } else {
            throw new \Exception('Failed to open ZIP file');
        }
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

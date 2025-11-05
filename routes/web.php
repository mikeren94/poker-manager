<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChartController;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/upload', function() {
    return Inertia::render('UploadHistory');
})->middleware(['auth', 'verified'])->name('upload');

Route::get('/hands/{hand}', [HandController::class, 'show'])->middleware(['auth', 'verified'])->name('hands.show');
Route::post('/hands/upload', [HandController::class, 'upload'])->middleware('auth');
Route::get('/hands', [HandController::class, 'index'])->middleware('auth');
Route::get('/charts/profit-over-time', [ChartController::class, 'profitOverTime']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

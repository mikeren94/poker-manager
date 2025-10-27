<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HandController;

Route::post('/hands/upload', [HandController::class, 'upload']);
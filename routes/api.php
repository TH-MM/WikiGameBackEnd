<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::get('/round/current', [GameController::class, 'currentRound']);
Route::post('/player/join', [GameController::class, 'join']);
Route::post('/progress/update', [GameController::class, 'updateProgress']);
Route::get('/leaderboard/current', [GameController::class, 'leaderboard']);
Route::get('/leaderboard/stats', [GameController::class, 'leaderboardStats']);

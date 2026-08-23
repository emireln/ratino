<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\WhisperController;
use Illuminate\Support\Facades\Route;

Route::post('/whisper', [WhisperController::class, 'whisper'])->middleware('throttle:120,1');

Route::prefix('data')->middleware('throttle:120,1')->group(function () {
    Route::post('/games', [DataController::class, 'games']);
    Route::post('/currencies', [DataController::class, 'currencies']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');
    Route::middleware('auth:sanctum')->middleware('throttle:60,1')->post('/update', [AuthController::class, 'update']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\UserController;

// Route Public
Route::post('/login', [AuthController::class, 'login']);

// Route Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // CRUD User
    Route::apiResource('users', UserController::class);

    // CRUD Tarif
    Route::apiResource('rates', TarifController::class);
});
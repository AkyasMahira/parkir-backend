<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Route Public
Route::post('/login', [AuthController::class, 'login']);

// Route Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // CRUD User
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    
});
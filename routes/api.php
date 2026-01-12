<?php

use App\Http\Controllers\Api\AreaParkirController;
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

    // CRUD Area Parkir
    Route::apiResource('areas', AreaParkirController::class);

    // Transaksi
    Route::post('/parking/in', [\App\Http\Controllers\Api\TransaksiController::class, 'store']);   
    Route::post('/parking/out', [\App\Http\Controllers\Api\TransaksiController::class, 'update']); 
    Route::get('/parking', [\App\Http\Controllers\Api\TransaksiController::class, 'index']);      
});
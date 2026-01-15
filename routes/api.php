<?php

use App\Http\Controllers\Api\AreaParkirController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LogAktivitasController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\TransaksiController;
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
    Route::post('/parking/in', [TransaksiController::class, 'store']);   
    Route::post('/parking/out', [TransaksiController::class, 'update']); 
    Route::get('/parking', [TransaksiController::class, 'index']);      

    // Member
    Route::get('/member/kendaraan', [MemberController::class, 'index']);
    Route::post('/member/kendaraan', [MemberController::class, 'store']);
    Route::delete('/member/kendaraan/{id}', [MemberController::class, 'destroy']);
    Route::get('/member/history', [MemberController::class, 'history']);

    // Log Aktivitas
    Route::get('/logs', [LogAktivitasController::class, 'index']);
});
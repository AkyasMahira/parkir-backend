<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\AreaParkirController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\LogAktivitasController;
use App\Http\Controllers\Api\WebhookController;

// 1. Route Public
Route::post('/login', [AuthController::class, 'login']);

// Route Webhook Cashi (Harus Public/Tanpa Auth Sanctum)
Route::post('/webhook/cashi', [WebhookController::class, 'handleCashi']);

// 2. Route Protected 
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // CRUD User
    Route::apiResource('users', UserController::class);

    // CRUD Tarif
    Route::apiResource('rates', TarifController::class);

    // CRUD Area Parkir
    Route::apiResource('areas', AreaParkirController::class);

    // Transaksi Parkir
    Route::post('/parking/in', [TransaksiController::class, 'store']);   
    Route::post('/parking/out', [TransaksiController::class, 'update']); 
    Route::get('/parking', [TransaksiController::class, 'index']);     
    
    // Transaksi Tambahan (Payment & Struk)
    Route::get('/transaksi/status/{orderId}', [TransaksiController::class, 'checkStatus']); 
    Route::get('/transaksi/struk/{id}', [TransaksiController::class, 'cetakStruk']); 

    // Member (Owner)
    Route::get('/member/kendaraan', [MemberController::class, 'index']);
    Route::post('/member/kendaraan', [MemberController::class, 'store']);
    Route::delete('/member/kendaraan/{id}', [MemberController::class, 'destroy']);
    Route::get('/member/history', [MemberController::class, 'history']);

    // Log Aktivitas
    Route::get('/logs', [LogAktivitasController::class, 'index']);
});
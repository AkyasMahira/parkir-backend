<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\AreaParkirController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\LogAktivitasController;
use App\Http\Controllers\Api\WebhookController;

// Public
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook/cashi', [WebhookController::class, 'handleCashi']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('rates', TarifController::class);
    Route::apiResource('areas', AreaParkirController::class);

    // Transaksi
    Route::post('/parking/in', [TransaksiController::class, 'store']);
    Route::post('/parking/out', [TransaksiController::class, 'update']);
    Route::get('/parking', [TransaksiController::class, 'index']);
    Route::get('/transaksi/status/{orderId}', [TransaksiController::class, 'checkStatus']);
    Route::get('/transaksi/struk/{id}', [TransaksiController::class, 'cetakStruk']);

    // Owner
    Route::get('/owner/pendapatan', [OwnerController::class, 'pendapatan']);
    Route::get('/owner/laporan', [OwnerController::class, 'laporan']);
    Route::get('/owner/dashboard', [OwnerController::class, 'dashboard']);

    // Log
    Route::get('/logs', [LogAktivitasController::class, 'index']);
});
<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DosenController;
use Illuminate\Support\Facades\Route;

// ─── Protected Dosen (auth:dosen_web) ────────────────────────────────────────
Route::prefix('api')
    ->middleware(['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {
        // Auth
        Route::post('/dosen/logout', [AuthController::class, 'logout']);
        Route::get('/dosen/me', [AuthController::class, 'me_dosen']);

        // Dosen — data diri & list
        Route::get('/dosen', [DosenController::class, 'index']);
        Route::get('/dosen/detail', [DosenController::class, 'show']);          // ?kode_dosen=
        Route::put('/dosen', [DosenController::class, 'update']);               // body: kode_dosen

        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });

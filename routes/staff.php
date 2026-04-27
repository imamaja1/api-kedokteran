<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_Staff\AkademikController;

// ─── Protected Staff (auth:staff_web) ─────────────────────────────────
Route::prefix('api/staff')
    ->middleware(['sanctum.spa', 'auth:staff_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {
        // Auth
        Route::get('me', [AuthController::class, 'me_staff']);
        Route::get('mahasiswa', [AkademikController::class, 'Mahasiswa']);
        Route::get('kurikulum/nama', [AkademikController::class, 'NamaKurikulum']);

        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });

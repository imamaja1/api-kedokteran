<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Auth (public) ───────────────────────────────────────────────────────────
Route::prefix('auth')
    ->middleware(['sanctum.spa', 'throttle:6,1'])
    ->group(function () {
        // CSRF token endpoint untuk Sanctum
        Route::get('/csrf-cookie', function () {
            return response()->json(['message' => 'XSRF-TOKEN cookie set']);
        });
        // mahasiswa login
        Route::post('/mhs/login', [AuthController::class, 'mhs_login']);
        // dosen login
        Route::post('/dosen/login', [AuthController::class, 'dosen_login']);
        // staff login
        Route::post('/staff/login', [AuthController::class, 'login_staff']);

        // logout (semua user)
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

// ─── Fallback — semua route API yang tidak terdaftar ─────────────────────────
Route::fallback(function () {
    return response()->json([
        'status' => false,
        'message' => 'Endpoint tidak ditemukan.',
        'error' => 'NOT_FOUND',
    ], 404);
})->middleware('sanctum.spa');

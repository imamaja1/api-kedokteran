<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\MatakuliahController;
use App\Http\Controllers\ProgramStudiController;
use Illuminate\Support\Facades\Route;

// ─── Auth (public) ───────────────────────────────────────────────────────────
Route::prefix('auth')
    ->middleware(['throttle:6,1'])
    ->group(function () {
        // CSRF token endpoint untuk Sanctum
        Route::get('/csrf-cookie', function () {
            return response()->json(['message' => 'XSRF-TOKEN cookie set']);
        });
        // mahasiswa login
        Route::post('/mhs/login', [AuthController::class, 'mhs_login']);
        // dosen login
        Route::post('/dosen/login', [AuthController::class, 'dosen_login']);
    });



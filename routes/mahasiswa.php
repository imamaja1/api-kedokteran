<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\Api_Mahasiswa\MahasiswaController;
use Illuminate\Support\Facades\Route;


// ─── Protected Mahasiswa (auth:mahasiswa_web) ─────────────────────────────────
Route::prefix('api')
    ->middleware(['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie'])
    ->group(function () {
        // Auth
        Route::post('/mhs/logout', [AuthController::class, 'logout']);
        Route::get('/mhs/me', [MahasiswaController::class, 'index']);

        // Mahasiswa — update data diri sendiri
        Route::put('/mhs/update', [MahasiswaController::class, 'mhs_update']);
    });

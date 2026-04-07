<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\Api_Mahasiswa\MahasiswaController;
use Illuminate\Support\Facades\Route;


// ─── Protected Mahasiswa (auth:mahasiswa_web) ─────────────────────────────────
Route::prefix('api/mhs')
    ->middleware(['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie'])
    ->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [MahasiswaController::class, 'profil']);
        Route::put('/profile/update', [MahasiswaController::class, 'profil_update']);
        // kurikulum
        // krs
        // khs
        // petikan
    });

<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\Api_Mahasiswa\MahasiswaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_Mahasiswa\KrsController as ApiMhsKrsController;
use App\Http\Controllers\Api_Mahasiswa\KhsController as ApiMhsKhsController;
use App\Http\Controllers\Api_Mahasiswa\KurikulumController as ApiMhsKurikulumController;


// ─── Protected Mahasiswa (auth:mahasiswa_web) ─────────────────────────────────
Route::prefix('api/mhs')
    ->middleware(['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie'])
    ->group(function () {
        // Auth
        Route::get('me', [MahasiswaController::class, 'me']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [MahasiswaController::class, 'profil']);
        Route::put('/profile/update', [MahasiswaController::class, 'profil_update']);
        Route::get('menu', [MahasiswaController::class, 'menu']);
        // kurikulum
        Route::get('kurikulum', [ApiMhsKurikulumController::class, 'kurikulum']);
        // krs
        Route::get('krs', [ApiMhsKrsController::class, 'krs']);
        // khs
        Route::get('khs', [ApiMhsKhsController::class, 'khs']);
        // petikan
    });

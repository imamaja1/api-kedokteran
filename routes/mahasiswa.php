<?php

use App\Http\Controllers\Api_Mahasiswa\KhsController as ApiMhsKhsController;
use App\Http\Controllers\Api_Mahasiswa\KrsController as ApiMhsKrsController;
use App\Http\Controllers\Api_Mahasiswa\KurikulumController as ApiMhsKurikulumController;
use App\Http\Controllers\Api_Mahasiswa\MahasiswaController;
use App\Http\Controllers\Api_Mahasiswa\PetikanNIlaiController as ApiMhsPetikanNilaiController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Protected Mahasiswa (auth:mahasiswa_web) ─────────────────────────────────
Route::prefix('api/mhs')
    ->middleware(['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {
        // Auth
        Route::get('me', [MahasiswaController::class, 'me']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [MahasiswaController::class, 'profil']);
        Route::put('/profile/update', [MahasiswaController::class, 'profil_update']);
        Route::put('/profile/foto', [MahasiswaController::class, 'foto_profil_update']);
        Route::get('semester', [MahasiswaController::class, 'semester']);
        // kurikulum
        Route::get('kurikulum', [ApiMhsKurikulumController::class, 'kurikulum']);
        // krs
        Route::get('krs', [ApiMhsKrsController::class, 'krs']);
        Route::post('krs', [ApiMhsKrsController::class, 'create']);
        Route::post('krs/detail', [ApiMhsKrsController::class, 'addDetail']);
        Route::delete('krs/detail', [ApiMhsKrsController::class, 'removeDetail']);
        Route::get('krs/sks-info', [ApiMhsKrsController::class, 'sksInfo']);
        // khs
        Route::get('khs', [ApiMhsKhsController::class, 'khs']);
        // petikan
        Route::get('petikan-nilai', [ApiMhsPetikanNilaiController::class, 'petikan_nilai']);

        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn () => response()->json([
            'status' => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error' => 'NOT_FOUND',
        ], 404));
    });

<?php

use App\Http\Controllers\Api_Dosen\KurikulumController;
use App\Http\Controllers\Api_Dosen\NilaiController;
use App\Http\Controllers\Api_Dosen\PerwalianController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DosenController;
use Illuminate\Support\Facades\Route;

// ─── Protected Dosen (auth:dosen_web) ────────────────────────────────────────
Route::prefix('api')
    ->middleware(['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {

        // ─── Auth ─────────────────────────────────────────────────────────
        Route::post('/dosen/logout', [AuthController::class, 'logout']);

        // ─── Profil (2 endpoints) ─────────────────────────────────────────
        Route::get('/dosen/me', [DosenController::class, 'me']);
        Route::put('/dosen/profile/update', [DosenController::class, 'profileUpdate']);

        // ─── Data Dosen ───────────────────────────────────────────────────
        Route::get('/dosen', [DosenController::class, 'index']);
        Route::get('/dosen/detail', [DosenController::class, 'show']);
        Route::put('/dosen', [DosenController::class, 'update']);

        // ─── Kurikulum (3 endpoints) ──────────────────────────────────────
        Route::get('/dosen/kurikulum', [KurikulumController::class, 'getKurikulum']);
        Route::get('/dosen/kurikulum/kelas', [KurikulumController::class, 'getKelasDosen']);
        Route::get('/dosen/kurikulum/detail', [KurikulumController::class, 'getDetailKelas']);

        // ─── Perwalian (6 endpoints) ──────────────────────────────────────
        Route::get('/dosen/perwalian/jumlah', [PerwalianController::class, 'jumlahPerwalian']);
        Route::get('/dosen/perwalian/daftar', [PerwalianController::class, 'daftarPerwalian']);
        Route::get('/dosen/perwalian/riwayat', [PerwalianController::class, 'riwayatPerwalian']);
        Route::get('/dosen/perwalian/krs', [PerwalianController::class, 'showKrsDetail']);
        Route::post('/dosen/perwalian/validasi', [PerwalianController::class, 'validasiKrs']);
        Route::post('/dosen/perwalian/batal', [PerwalianController::class, 'batalPerwalian']);

        // ─── Penilaian (3 endpoints) ──────────────────────────────────────
        Route::get('/dosen/penilaian', [NilaiController::class, 'getTreePenilaian']);
        Route::get('/dosen/penilaian/mahasiswa', [NilaiController::class, 'getMahasiswa']);
        Route::post('/dosen/penilaian/input', [NilaiController::class, 'inputNilai']);

        // ─── Fallback ─────────────────────────────────────────────────────
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });

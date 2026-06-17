<?php

use App\Http\Controllers\Api_Dosen\KurikulumController;
use App\Http\Controllers\Api_Dosen\PenilaianDosenController;
use App\Http\Controllers\Api_Dosen\PenilaianKaprodiController;
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
        Route::put('/dosen/profile/foto', [DosenController::class, 'fotoProfilUpdate']);

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

        // ─── Penilaian Dosen (6 endpoints) ────────────────────────────────
        Route::prefix('/dosen/penilaian')->group(function () {
            Route::get('/kelas', [PenilaianDosenController::class, 'getKelasPenilaian']);
            Route::get('/mahasiswa', [PenilaianDosenController::class, 'getMahasiswaPenilaian']);
            Route::get('/template', [PenilaianDosenController::class, 'getTemplate']);
            Route::get('/detail', [PenilaianDosenController::class, 'getDetailNilaiMahasiswa']);
            Route::post('/input', [PenilaianDosenController::class, 'inputNilai']);
            Route::put('/update', [PenilaianDosenController::class, 'updateNilai']);
        });

        // ─── Kaprodi (5 endpoints, dengan middleware kaprodi) ──────────────
        Route::prefix('/dosen/kaprodi')
            ->middleware('kaprodi')
            ->group(function () {
                Route::get('/penilaian/kelas', [PenilaianKaprodiController::class, 'getKelasPenilaian']);
                Route::get('/penilaian/mahasiswa', [PenilaianKaprodiController::class, 'getMahasiswaPenilaian']);
                Route::get('/penilaian/detail', [PenilaianKaprodiController::class, 'getDetailNilaiMahasiswa']);
                Route::post('/penilaian/validasi', [PenilaianKaprodiController::class, 'validasi']);
                Route::post('/penilaian/revisi', [PenilaianKaprodiController::class, 'revisi']);
            });

        // ─── Fallback ─────────────────────────────────────────────────────
        Route::fallback(fn () => response()->json([
            'status' => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error' => 'NOT_FOUND',
        ], 404));
    });

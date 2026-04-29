<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_Staff\AkademikController;
use App\Http\Controllers\Api_Staff\MasterDataController;

// ─── Protected Staff (auth:staff_web) ─────────────────────────────────
Route::prefix('api/staff')
    ->middleware(['sanctum.spa', 'auth:staff_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {
        // Auth
        Route::get('me', [AuthController::class, 'me_staff']);
        Route::get('mahasiswa', [AkademikController::class, 'Mahasiswa']);

        // Akademik
        Route::prefix('akademik')->group(function () {
            Route::get('program-studi', [AkademikController::class, 'program_studi']);
            Route::get('tahun-angkatan', [AkademikController::class, 'tahun_angkatan']);
            Route::get('kurikulum/nama', [AkademikController::class, 'NamaKurikulum']);
            Route::get('krs', [AkademikController::class, 'KRS']);
            Route::get('khs', [AkademikController::class, 'KHS']);
            Route::get('petikan-nilai', [AkademikController::class, 'PetikanNilai']);
        });
        // Master Data
        Route::prefix('master-data')->group(function () {
            Route::get('matakuliah', [MasterDataController::class, 'GetMatakuliah']);
            Route::get('matakuliah-show', [MasterDataController::class, 'GetOneMatakuliah']);
            Route::post('matakuliah', [MasterDataController::class, 'StoreMatakuliah']);
            Route::put('matakuliah', [MasterDataController::class, 'UpdateMatakuliah']);
            Route::delete('matakuliah/{code}', [MasterDataController::class, 'DeleteMatakuliah']);

        }); 
        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });

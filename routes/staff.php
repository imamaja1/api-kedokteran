<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_Staff\AkademikController;
use App\Http\Controllers\Api_Staff\MasterDataController;
use App\Http\Controllers\Api_Staff\DefaultController;

// ─── Protected Staff (auth:staff_web) ─────────────────────────────────
Route::prefix('api/staff')
    ->middleware(['sanctum.spa', 'auth:staff_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {
        // Auth
        Route::get('me', [AuthController::class, 'me_staff']);
        Route::get('mahasiswa', [DefaultController::class, 'Mahasiswa']);
        Route::get('tahun-angkatan', [DefaultController::class, 'tahun_angkatan']);

        // Akademik
        Route::prefix('akademik')->group(function () {
            Route::get('program-studi', [AkademikController::class, 'program_studi']);
            Route::get('nama-kurikulum', [AkademikController::class, 'NamaKurikulum']);
            Route::get('kurikulum', [AkademikController::class, 'Kurikulum']);
            Route::get('krs', [AkademikController::class, 'KRS']);
            Route::get('krs-detail', [AkademikController::class, 'KRSDetail']);
            Route::get('khs', [AkademikController::class, 'KHS']);
            Route::get('khs-detail', [AkademikController::class, 'KHSDetail']);
            Route::get('petikan-nilai', [AkademikController::class, 'PetikanNilai']);
        });

        // Master Data
        Route::prefix('master-data')->group(function () {
            Route::get('program-studi', [MasterDataController::class, 'GetProgramStudi']);
            Route::get('program-studi-show', [MasterDataController::class, 'GetOneProgramStudi']);
            Route::post('program-studi', [MasterDataController::class, 'StoreProgramStudi']);
            Route::put('program-studi', [MasterDataController::class, 'UpdateProgramStudi']);
            Route::delete('program-studi/{code}', [MasterDataController::class, 'DeleteProgramStudi']);

            Route::get('matakuliah', [MasterDataController::class, 'GetMatakuliah']);
            Route::get('matakuliah-show', [MasterDataController::class, 'GetOneMatakuliah']);
            Route::post('matakuliah', [MasterDataController::class, 'StoreMatakuliah']);
            Route::put('matakuliah', [MasterDataController::class, 'UpdateMatakuliah']);
            Route::delete('matakuliah/{code}', [MasterDataController::class, 'DeleteMatakuliah']);

            Route::get('dosen', [MasterDataController::class, 'GetDosen']);
        }); 
        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });

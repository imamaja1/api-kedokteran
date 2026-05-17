<?php

use App\Http\Controllers\Auth\AuthController;
use App\Models\Dosen;
use App\Models\Matakuliah;
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
            // Matakuliah
            Route::get('matakuliah', [MasterDataController::class, 'GetMatakuliah']);
            Route::get('matakuliah-show', [MasterDataController::class, 'GetOneMatakuliah']);
            Route::post('matakuliah', [MasterDataController::class, 'StoreMatakuliah']);
            Route::put('matakuliah', [MasterDataController::class, 'UpdateMatakuliah']);
            Route::delete('matakuliah/{code}', [MasterDataController::class, 'DeleteMatakuliah']);

            //program studi
            Route::get('program-studi', [MasterDataController::class, 'GetProgramStudi']);
            Route::get('program-studi-show', [MasterDataController::class, 'GetOneProgramStudi']);
            Route::post('program-studi', [MasterDataController::class, 'StoreProgramStudi']);
            Route::put('program-studi', [MasterDataController::class, 'UpdateProgramStudi']);
            Route::delete('program-studi/{code}', [MasterDataController::class, 'DeleteProgramStudi']);

            // Dosen
            Route::get('dosen', [MasterDataController::class, 'GetDosen']);
            Route::get('dosen-show', [MasterDataController::class, 'GetOneDosen']);
            Route::post('dosen', [MasterDataController::class, 'StoreDosen']);
            Route::put('dosen', [MasterDataController::class, 'UpdateDosen']);
            Route::delete('dosen/{code}', [MasterDataController::class, 'DeleteDosen']);

            //nama kurikulum
            Route::get('nama-kurikulum', [MasterDataController::class, 'GetNamaKurikulum']);
            Route::get('nama-kurikulum-show', [MasterDataController::class, 'GetOneNamaKurikulum']);
            Route::post('nama-kurikulum', [MasterDataController::class, 'StoreNamaKurikulum']);
            Route::put('nama-kurikulum', [MasterDataController::class, 'UpdateNamaKurikulum']);
            Route::delete('nama-kurikulum/{code}', [MasterDataController::class, 'DeleteNamaKurikulum']);

            //tahun ajaran
            Route::get('tahun-akademik', [MasterDataController::class, 'GetTahunAkademik']);
            Route::get('tahun-akademik-show', [MasterDataController::class, 'GetOneTahunAkademik']);
            Route::post('tahun-akademik', [MasterDataController::class, 'StoreTahunAkademik']);
            Route::put('tahun-akademik', [MasterDataController::class, 'UpdateTahunAkademik']);
            Route::delete('tahun-akademik/{code}', [MasterDataController::class, 'DeleteTahunAkademik']);


        });
        // fallback dalam group — return 404 bukan 401
        Route::fallback(fn() => response()->json([
            'status' => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error' => 'NOT_FOUND',
        ], 404));
    });

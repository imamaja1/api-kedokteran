<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\MatakuliahController;
use App\Http\Controllers\ProgramStudiController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// ─── Auth (public) ───────────────────────────────────────────────────────────
Route::prefix('auth')->middleware(['throttle:6,1', EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
    EnsureFrontendRequestsAreStateful::class,
])->group(function () {
    // CSRF token endpoint untuk Sanctum
    Route::get('/csrf-cookie', function () {
        return response()->json(['message' => 'XSRF-TOKEN cookie set']);
    });

    Route::post('/mhs/login', [AuthController::class, 'mhs_login']);
    Route::post('/dosen/login', [AuthController::class, 'dosen_login']);
});

// ─── Protected (auth:sanctum) ────────────────────────────────────────────────
Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    EnsureFrontendRequestsAreStateful::class,
    'auth:sanctum,mahasiswa_web',
    'sanctum.cookie',
])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me_mahasiswa']);

    // Mahasiswa
    Route::get('/mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('/mahasiswa/detail', [MahasiswaController::class, 'show']);             // ?nim=
    Route::post('/mahasiswa', [MahasiswaController::class, 'store']);
    Route::put('/mahasiswa', [MahasiswaController::class, 'update']);                  // body: nim
    Route::delete('/mahasiswa', [MahasiswaController::class, 'destroy']);              // ?nim= (soft delete)
    Route::patch('/mahasiswa/restore', [MahasiswaController::class, 'restore']);       // body: nim
    Route::delete('/mahasiswa/force', [MahasiswaController::class, 'forceDelete']);   // ?nim= (hapus permanen)

    // Matakuliah (read-only untuk user biasa)
    Route::get('/matakuliah', [MatakuliahController::class, 'index']);
    Route::get('/matakuliah/detail', [MatakuliahController::class, 'show']);          // ?id=

    // Program Studi (read-only)
    Route::get('/program-studi', [ProgramStudiController::class, 'index']);
    Route::get('/program-studi/detail', [ProgramStudiController::class, 'show']);    // ?id=

    // KRS
    Route::get('/krs', [KrsController::class, 'index']);
    Route::post('/krs', [KrsController::class, 'store']);
    Route::get('/krs/detail', [KrsController::class, 'showDetail']);                 // ?id=
    Route::post('/krs/detail', [KrsController::class, 'storeDetail']);               // body: kode_krs
    Route::delete('/krs/detail', [KrsController::class, 'destroyDetail']);           // ?kode_krs= &kode_krs_detail=

    // KHS
    Route::get('/khs', [KhsController::class, 'index']);
    Route::post('/khs', [KhsController::class, 'store']);
    Route::get('/khs/detail', [KhsController::class, 'show']);                       // ?id=
});

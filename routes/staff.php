<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api_Mahasiswa\KrsController as ApiMhsKrsController;
use App\Http\Controllers\Api_Mahasiswa\KhsController as ApiMhsKhsController;
use App\Http\Controllers\Api_Mahasiswa\KurikulumController as ApiMhsKurikulumController;
use App\Http\Controllers\Api_Mahasiswa\PetikanNIlaiController as ApiMhsPetikanNilaiController;


// ─── Protected Staff (auth:staff_web) ─────────────────────────────────
Route::prefix('api/staff')
    ->middleware(['sanctum.spa', 'auth:staff_web', 'sanctum.cookie'])
    ->group(function () {
        // Auth
        Route::get('me', [AuthController::class, 'me_staff']);
    });

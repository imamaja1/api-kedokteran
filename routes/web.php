<?php

use App\Http\Controllers\Admin\ApiConnectionController;
use App\Http\Controllers\Admin\ApiEndpointController;
use App\Http\Controllers\Admin\ApiSectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\KrsKhsController;
use App\Http\Controllers\Admin\MatakuliahController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\TahunAkademikController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('welcome');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Admin Panel
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('sections', ApiSectionController::class)->except(['show']);
    Route::resource('endpoints', ApiEndpointController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('connections', ApiConnectionController::class)->except(['show']);

    // Mahasiswa — custom param 'nim' as route key
    Route::resource('mahasiswa', MahasiswaController::class)
        ->except(['show'])
        ->parameters(['mahasiswa' => 'nim']);
    Route::patch('mahasiswa/{nim}/restore', [MahasiswaController::class, 'restore'])->name('mahasiswa.restore');
    Route::delete('mahasiswa/{nim}/force', [MahasiswaController::class, 'forceDelete'])->name('mahasiswa.force-delete');
    Route::post('mahasiswa/sync-siska', [MahasiswaController::class, 'syncWithSiska'])->name('mahasiswa.sync-siska');

    // Tahun Akademik
    Route::resource('tahun-akademik', TahunAkademikController::class)->except(['show']);
    Route::post('tahun-akademik/sync-siska', [TahunAkademikController::class, 'syncWithSiska'])->name('tahun-akademik.sync-siska');

    // Matakuliah
    Route::post('matakuliah/sync-siska', [MatakuliahController::class, 'syncWithSiska'])->name('matakuliah.sync-siska');
    Route::resource('matakuliah', MatakuliahController::class)->except(['show']);

    // Dosen
    Route::resource('dosen', DosenController::class)->except(['show']);
    Route::patch('dosen/{nik}/restore', [DosenController::class, 'restore'])->name('dosen.restore');
    Route::delete('dosen/{nik}/force', [DosenController::class, 'forceDelete'])->name('dosen.force-delete');
    Route::post('dosen/sync-siska', [DosenController::class, 'syncWithSiska'])->name('dosen.sync-siska');

    // KRS & KHS Mahasiswa
    Route::get('krs-khs', [KrsKhsController::class, 'index'])->name('krs-khs.index');
    Route::post('krs-khs/sync-siska', [KrsKhsController::class, 'syncWithSiska'])->name('krs-khs.sync-siska');
    Route::get('krs-khs/{kode_krs}', [KrsKhsController::class, 'show'])->name('krs-khs.show');
    Route::post('krs-khs/{kode_krs}/detail', [KrsKhsController::class, 'storeDetail'])->name('krs-khs.store-detail');
    Route::delete('krs-khs/{kode_krs}/detail/{kode_krs_detail}', [KrsKhsController::class, 'destroyDetail'])->name('krs-khs.destroy-detail');
    Route::put('krs-khs/detail/{kode_krs_detail}/nilai', [KrsKhsController::class, 'updateNilai'])->name('krs-khs.update-nilai');

    Route::get('/docs', [DocsController::class, 'index'])->name('docs');
    Route::get('/tester', [DocsController::class, 'tester'])->name('tester');
});

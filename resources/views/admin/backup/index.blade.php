@extends('admin.layout')
@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore (Database & Dokumentasi)')

@section('content')
<div class="container-fluid px-0">

    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Validasi Gagal:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Header Info / Ringkasan Status --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:12px;background:#e8f0fe;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-database-fill text-primary fs-5"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $tableCount }}</div>
                    <div class="text-muted small">Tabel Database ({{ number_format($rowCount) }} baris)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:12px;background:#e6f9f0;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-collection-fill text-success fs-5"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $docsStats['sections'] }}</div>
                    <div class="text-muted small">Seksi Dokumentasi</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:12px;background:#fff3e0;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-code-slash text-warning fs-5"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $docsStats['endpoints'] }}</div>
                    <div class="text-muted small">API Endpoints ({{ $docsStats['parameters'] }} params)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:12px;background:#f3e8fd;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-diagram-2-fill text-purple fs-5" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $docsStats['workflows'] }}</div>
                    <div class="text-muted small">Alur Bisnis (Workflows)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 1: DATABASE BACKUP (EXPORT & IMPORT) --}}
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded bg-primary text-white">
                    <i class="bi bi-database-gear"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">1. Backup &amp; Restore Database</h5>
                    <small class="text-muted">Export seluruh database SIAKAD ke file <code>.sql</code> atau Import data dari backup SQL.</small>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- EXPORT DATABASE --}}
                <div class="col-lg-6">
                    <div class="p-4 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                                <i class="bi bi-database-down fs-4"></i>
                                <h6 class="fw-bold mb-0">Export Database (.sql)</h6>
                            </div>
                            <p class="text-muted small mb-3">
                                Mengunduh seluruh struktur <strong>{{ $tableCount }} tabel</strong> dan <strong>{{ number_format($rowCount) }} data</strong> ke dalam satu file SQL murni. File ini mencakup DDL <code>CREATE TABLE</code> dan <code>INSERT</code> data siap restore.
                            </p>
                            <div class="p-2.5 rounded bg-white border small text-muted mb-3 font-monospace">
                                <i class="bi bi-info-circle text-primary me-1"></i> Target Database: <strong>{{ $dbName }}</strong>
                            </div>
                        </div>
                        <a href="{{ route('admin.backup.export.db') }}" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 py-2">
                            <i class="bi bi-download"></i>
                            <span>Unduh Backup Database (.sql)</span>
                        </a>
                    </div>
                </div>

                {{-- IMPORT DATABASE --}}
                <div class="col-lg-6">
                    <div class="p-4 rounded-3 border bg-light h-100">
                        <div class="d-flex align-items-center gap-2 mb-2 text-danger">
                            <i class="bi bi-database-up fs-4"></i>
                            <h6 class="fw-bold mb-0">Import Database (.sql)</h6>
                        </div>
                        <p class="text-muted small mb-3">
                            Unggah file <code>.sql</code> untuk memulihkan seluruh struktur tabel dan data.
                        </p>
                        <form action="{{ route('admin.backup.import.db') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('PERINGATAN: Memulihkan database akan mengeksekusi instruksi SQL pada database saat ini. Pastikan Anda sudah mengunduh backup sebelum melanjutkan. Lanjutkan?');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pilih File SQL:</label>
                                <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                                <div class="form-text small text-muted">Format: <code>.sql</code> (Maks. 100 MB).</div>
                            </div>
                            <button type="submit" class="btn btn-danger d-flex align-items-center justify-content-center gap-2 w-100 py-2">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Proses Import Database</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: DOKUMENTASI API BACKUP (EXPORT & IMPORT) --}}
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded bg-success text-white">
                    <i class="bi bi-journal-code"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">2. Backup &amp; Restore Dokumentasi API</h5>
                    <small class="text-muted">Export seluruh seksi, endpoints, parameter, dan workflows ke file <code>.json</code> atau sinkronkan kembali.</small>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- EXPORT DOKUMENTASI --}}
                <div class="col-lg-6">
                    <div class="p-4 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2 text-success">
                                <i class="bi bi-file-earmark-arrow-down fs-4"></i>
                                <h6 class="fw-bold mb-0">Export Dokumentasi API (.json)</h6>
                            </div>
                            <p class="text-muted small mb-3">
                                Mengunduh seluruh konfigurasi dokumentasi API dalam format JSON terstruktur. File mencakup <strong>{{ $docsStats['sections'] }} Seksi</strong>, <strong>{{ $docsStats['endpoints'] }} Endpoints</strong>, <strong>{{ $docsStats['parameters'] }} Parameters</strong>, dan <strong>{{ $docsStats['workflows'] }} Alur Bisnis</strong>.
                            </p>
                            <div class="p-2.5 rounded bg-white border small text-muted mb-3 font-monospace">
                                <i class="bi bi-check-circle text-success me-1"></i> Format: <strong>JSON Portable (Siap dipindahkan antar server)</strong>
                            </div>
                        </div>
                        <a href="{{ route('admin.backup.export.docs') }}" class="btn btn-success d-flex align-items-center justify-content-center gap-2 w-100 py-2">
                            <i class="bi bi-download"></i>
                            <span>Unduh Backup Dokumentasi (.json)</span>
                        </a>
                    </div>
                </div>

                {{-- IMPORT DOKUMENTASI --}}
                <div class="col-lg-6">
                    <div class="p-4 rounded-3 border bg-light h-100">
                        <div class="d-flex align-items-center gap-2 mb-2 text-info">
                            <i class="bi bi-file-earmark-arrow-up fs-4"></i>
                            <h6 class="fw-bold mb-0">Import Dokumentasi API (.json)</h6>
                        </div>
                        <p class="text-muted small mb-3">
                            Unggah file <code>.json</code> untuk memperbarui atau menyinkronkan seluruh dokumentasi API di panel ini.
                        </p>
                        <form action="{{ route('admin.backup.import.docs') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Import dokumentasi akan memperbarui data dokumentasi API. Lanjutkan?');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pilih File JSON:</label>
                                <input type="file" name="json_file" class="form-control" accept=".json" required>
                                <div class="form-text small text-muted">Format: <code>.json</code> (Maks. 20 MB).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Metode Import:</label>
                                <select name="mode" class="form-select form-select-sm">
                                    <option value="replace" selected>Replace Fresh (Bersihkan data lama &amp; timpa baru secara utuh)</option>
                                    <option value="upsert">Merge / Upsert (Perbarui yang cocok, tambahkan yang baru)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info text-white d-flex align-items-center justify-content-center gap-2 w-100 py-2">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                                <span>Proses Import Dokumentasi</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

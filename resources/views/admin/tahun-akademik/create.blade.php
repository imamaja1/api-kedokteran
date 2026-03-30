@extends('admin.layout')
@section('title', 'Tambah Tahun Akademik')
@section('page-title', 'Tambah Tahun Akademik')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header-custom">
                <i class="bi bi-calendar-plus-fill me-2"></i>Tambah Tahun Akademik
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.tahun-akademik.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Tahun Akademik <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="tahun_akademik"
                                class="form-control @error('tahun_akademik') is-invalid @enderror"
                                value="{{ old('tahun_akademik') }}" placeholder="cth. 2024/2025" maxlength="9">
                            <div class="form-text">Format: YYYY/YYYY (contoh: 2024/2025)</div>
                            @error('tahun_akademik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Semester <span class="text-danger">*</span></label>
                            <select name="semester" class="form-select @error('semester') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                <option value="1" {{ old('semester')==='1' ? 'selected' : '' }}>Semester 1 (Ganjil)
                                </option>
                                <option value="2" {{ old('semester')==='2' ? 'selected' : '' }}>Semester 2 (Genap)
                                </option>
                            </select>
                            @error('semester') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai"
                                class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                value="{{ old('tanggal_mulai') }}">
                            @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Berakhir <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_berakhir"
                                class="form-control @error('tanggal_berakhir') is-invalid @enderror"
                                value="{{ old('tanggal_berakhir') }}">
                            @error('tanggal_berakhir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="N" {{ old('status', 'N' )==='N' ? 'selected' : '' }}>Non-Aktif</option>
                                <option value="A" {{ old('status')==='A' ? 'selected' : '' }}>Aktif</option>
                            </select>
                            <div class="form-text">Mengaktifkan akan menonaktifkan semester yang sama lainnya.</div>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status KPAT</label>
                            <select name="status_kpat" class="form-select @error('status_kpat') is-invalid @enderror">
                                <option value="N" {{ old('status_kpat', 'N' )==='N' ? 'selected' : '' }}>Tutup</option>
                                <option value="A" {{ old('status_kpat')==='A' ? 'selected' : '' }}>Buka</option>
                            </select>
                            @error('status_kpat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-2">
                        <a href="{{ route('admin.tahun-akademik.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
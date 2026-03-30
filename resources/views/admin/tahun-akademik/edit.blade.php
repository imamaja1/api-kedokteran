@extends('admin.layout')
@section('title', 'Edit Tahun Akademik')
@section('page-title', 'Edit Tahun Akademik')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-2"></i>Edit Tahun Akademik</span>
                <span class="badge bg-light text-dark border">#{{ $tahunAkademik->kode_tahun_akademik }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.tahun-akademik.update', $tahunAkademik) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Tahun Akademik <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="tahun_akademik"
                                class="form-control @error('tahun_akademik') is-invalid @enderror"
                                value="{{ old('tahun_akademik', $tahunAkademik->tahun_akademik) }}"
                                placeholder="cth. 2024/2025" maxlength="9">
                            <div class="form-text">Format: YYYY/YYYY (contoh: 2024/2025)</div>
                            @error('tahun_akademik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Semester <span class="text-danger">*</span></label>
                            <select name="semester" class="form-select @error('semester') is-invalid @enderror">
                                <option value="1" {{ old('semester', $tahunAkademik->semester) === '1' ? 'selected' : ''
                                    }}>Semester 1 (Ganjil)</option>
                                <option value="2" {{ old('semester', $tahunAkademik->semester) === '2' ? 'selected' : ''
                                    }}>Semester 2 (Genap)</option>
                            </select>
                            @error('semester') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai"
                                class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                value="{{ old('tanggal_mulai', $tahunAkademik->tanggal_mulai?->format('Y-m-d')) }}">
                            @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Berakhir <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_berakhir"
                                class="form-control @error('tanggal_berakhir') is-invalid @enderror"
                                value="{{ old('tanggal_berakhir', $tahunAkademik->tanggal_berakhir?->format('Y-m-d')) }}">
                            @error('tanggal_berakhir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="N" {{ old('status', $tahunAkademik->status) === 'N' ? 'selected' : ''
                                    }}>Non-Aktif</option>
                                <option value="A" {{ old('status', $tahunAkademik->status) === 'A' ? 'selected' : ''
                                    }}>Aktif</option>
                            </select>
                            <div class="form-text">Mengaktifkan akan menonaktifkan semester yang sama lainnya.</div>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status KPAT</label>
                            <select name="status_kpat" class="form-select @error('status_kpat') is-invalid @enderror">
                                <option value="N" {{ old('status_kpat', $tahunAkademik->status_kpat) === 'N' ?
                                    'selected' : '' }}>Tutup</option>
                                <option value="A" {{ old('status_kpat', $tahunAkademik->status_kpat) === 'A' ?
                                    'selected' : '' }}>Buka</option>
                            </select>
                            @error('status_kpat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-2">
                        <a href="{{ route('admin.tahun-akademik.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
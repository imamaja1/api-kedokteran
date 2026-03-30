@extends('admin.layout')
@section('title', 'Edit Dosen')
@section('page-title', 'Edit Dosen')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-2"></i>Edit Dosen</span>
                <span class="badge bg-light text-dark border">#{{ $dosen->kode_dosen }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.dosen.update', $dosen) }}" method="POST">
                    @csrf @method('PUT')

                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-person-badge me-1"></i> Identitas
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Dosen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dosen"
                                class="form-control @error('nama_dosen') is-invalid @enderror"
                                value="{{ old('nama_dosen', $dosen->nama_dosen) }}" maxlength="255">
                            @error('nama_dosen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NIK</label>
                            <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror"
                                value="{{ old('nik', $dosen->nik) }}" maxlength="255">
                            @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Field Studi</label>
                            <input type="text" name="field_studi"
                                class="form-control @error('field_studi') is-invalid @enderror"
                                value="{{ old('field_studi', $dosen->field_studi) }}" maxlength="255">
                            @error('field_studi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Alumni</label>
                            <input type="text" name="alumni" class="form-control @error('alumni') is-invalid @enderror"
                                value="{{ old('alumni', $dosen->alumni) }}" maxlength="255">
                            @error('alumni') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-telephone me-1"></i> Kontak
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="alamat_email"
                                class="form-control @error('alamat_email') is-invalid @enderror"
                                value="{{ old('alamat_email', $dosen->alamat_email) }}" maxlength="100">
                            @error('alamat_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <input type="text" name="no_telp"
                                class="form-control @error('no_telp') is-invalid @enderror"
                                value="{{ old('no_telp', $dosen->no_telp) }}" maxlength="20">
                            @error('no_telp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Chat ID</label>
                            <input type="text" name="chatid" class="form-control @error('chatid') is-invalid @enderror"
                                value="{{ old('chatid', $dosen->chatid) }}" maxlength="20">
                            @error('chatid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-building me-1"></i> Akademik & Status
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Homebase (Program Studi)</label>
                            <select name="homebase" class="form-select @error('homebase') is-invalid @enderror">
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach($programStudis as $ps)
                                <option value="{{ $ps->kode_program_studi }}" {{ old('homebase', $dosen->homebase) ==
                                    $ps->kode_program_studi ? 'selected' : '' }}>
                                    {{ $ps->nama_program_studi }}
                                </option>
                                @endforeach
                            </select>
                            @error('homebase') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status Dosen <span
                                    class="text-danger">*</span></label>
                            <select name="status_dosen" class="form-select @error('status_dosen') is-invalid @enderror">
                                <option value="T" {{ old('status_dosen', $dosen->status_dosen) === 'T' ? 'selected' : ''
                                    }}>Tetap</option>
                                <option value="L" {{ old('status_dosen', $dosen->status_dosen) === 'L' ? 'selected' : ''
                                    }}>Luar Biasa</option>
                            </select>
                            @error('status_dosen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Keaktifan <span class="text-danger">*</span></label>
                            <select name="aktif" class="form-select @error('aktif') is-invalid @enderror">
                                <option value="A" {{ old('aktif', $dosen->aktif) === 'A' ? 'selected' : '' }}>Aktif
                                </option>
                                <option value="N" {{ old('aktif', $dosen->aktif) === 'N' ? 'selected' : '' }}>Tidak
                                    Aktif</option>
                            </select>
                            @error('aktif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Reset Password</label>
                            <input type="password" name="sandi_pengguna"
                                class="form-control @error('sandi_pengguna') is-invalid @enderror"
                                placeholder="Kosongkan jika tidak diubah">
                            @error('sandi_pengguna') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-2">
                        <a href="{{ route('admin.dosen.index') }}" class="btn btn-outline-secondary">
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
@extends('admin.layout')
@section('title', 'Tambah Mahasiswa')
@section('page-title', 'Tambah Mahasiswa')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header-custom">
                <i class="bi bi-person-plus-fill me-2"></i>Tambah Mahasiswa Baru
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.mahasiswa.store') }}" method="POST">
                    @csrf

                    {{-- Identitas Mahasiswa --}}
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-person-badge me-1"></i> Identitas
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NIM <span class="text-danger">*</span></label>
                            <input type="text" name="nim" class="form-control @error('nim') is-invalid @enderror"
                                value="{{ old('nim') }}" maxlength="11" placeholder="cth. 12345678901">
                            @error('nim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NIK <span class="text-danger">*</span></label>
                            <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror"
                                value="{{ old('nik') }}" maxlength="20">
                            @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NPM <span class="text-danger">*</span></label>
                            <input type="text" name="npm" class="form-control @error('npm') is-invalid @enderror"
                                value="{{ old('npm') }}" maxlength="23">
                            @error('npm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Mahasiswa <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nama_mahasiswa"
                                class="form-control @error('nama_mahasiswa') is-invalid @enderror"
                                value="{{ old('nama_mahasiswa') }}" maxlength="125">
                            @error('nama_mahasiswa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin"
                                class="form-select @error('jenis_kelamin') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                <option value="L" {{ old('jenis_kelamin')==='L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('jenis_kelamin')==='P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('jenis_kelamin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir"
                                class="form-control @error('tanggal_lahir') is-invalid @enderror"
                                value="{{ old('tanggal_lahir') }}">
                            @error('tanggal_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir"
                                class="form-control @error('tempat_lahir') is-invalid @enderror"
                                value="{{ old('tempat_lahir') }}" maxlength="50">
                            @error('tempat_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Nomor Pendaftaran --}}
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-file-text me-1"></i> Nomor Pendaftaran
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Pendaftaran <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nomor_pendaftaran"
                                class="form-control @error('nomor_pendaftaran') is-invalid @enderror"
                                value="{{ old('nomor_pendaftaran') }}" maxlength="13">
                            @error('nomor_pendaftaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Pendaftaran Ulang <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nomor_pendaftaran_ulang"
                                class="form-control @error('nomor_pendaftaran_ulang') is-invalid @enderror"
                                value="{{ old('nomor_pendaftaran_ulang') }}" maxlength="13">
                            @error('nomor_pendaftaran_ulang') <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Kontak --}}
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-telephone me-1"></i> Kontak & Alamat
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" maxlength="75">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telepon</label>
                            <input type="text" name="telepon"
                                class="form-control @error('telepon') is-invalid @enderror" value="{{ old('telepon') }}"
                                maxlength="20">
                            @error('telepon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input type="text" name="alamat" class="form-control @error('alamat') is-invalid @enderror"
                                value="{{ old('alamat') }}" maxlength="75">
                            @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kota</label>
                            <input type="text" name="kota" class="form-control @error('kota') is-invalid @enderror"
                                value="{{ old('kota') }}" maxlength="50">
                            @error('kota') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Akademik --}}
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-book me-1"></i> Akademik
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Program Studi</label>
                            <select name="program_studi_kode"
                                class="form-select @error('program_studi_kode') is-invalid @enderror">
                                <option value="">-- Pilih Program Studi --</option>
                                @foreach($programStudis as $ps)
                                <option value="{{ $ps->kode_program_studi }}" {{ old('program_studi_kode')==$ps->
                                    kode_program_studi ? 'selected' : '' }}>
                                    {{ $ps->nama_program_studi }}
                                </option>
                                @endforeach
                            </select>
                            @error('program_studi_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="A" {{ old('status', 'A' )==='A' ? 'selected' : '' }}>Aktif</option>
                                <option value="N" {{ old('status')==='N' ? 'selected' : '' }}>Non-Aktif</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password Awal</label>
                            <input type="password" name="sandi"
                                class="form-control @error('sandi') is-invalid @enderror"
                                placeholder="Kosongkan jika tidak diatur">
                            @error('sandi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Orang Tua --}}
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-people me-1"></i> Data Orang Tua
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Ayah</label>
                            <input type="text" name="nama_ayah"
                                class="form-control @error('nama_ayah') is-invalid @enderror"
                                value="{{ old('nama_ayah') }}" maxlength="50">
                            @error('nama_ayah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Ibu</label>
                            <input type="text" name="nama_ibu"
                                class="form-control @error('nama_ibu') is-invalid @enderror"
                                value="{{ old('nama_ibu') }}" maxlength="50">
                            @error('nama_ibu') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telepon Orang Tua</label>
                            <input type="text" name="telepon_orangtua"
                                class="form-control @error('telepon_orangtua') is-invalid @enderror"
                                value="{{ old('telepon_orangtua') }}" maxlength="20">
                            @error('telepon_orangtua') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-2">
                        <a href="{{ route('admin.mahasiswa.index') }}" class="btn btn-outline-secondary">
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
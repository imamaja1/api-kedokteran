@extends('admin.layout')
@section('title', 'Edit Matakuliah')
@section('page-title', 'Edit Matakuliah')

@section('content')
<div class="card" style="max-width:640px">
    <div class="card-header-custom">
        <i class="bi bi-pencil-square me-2"></i>Edit Matakuliah
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.matakuliah.update', $matakuliah->id_matakuliah) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Kode Matakuliah <span class="text-danger">*</span></label>
                <input type="text" name="kode_matakuliah"
                    class="form-control @error('kode_matakuliah') is-invalid @enderror"
                    value="{{ old('kode_matakuliah', $matakuliah->kode_matakuliah) }}" maxlength="10" required>
                @error('kode_matakuliah')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Matakuliah <span class="text-danger">*</span></label>
                <input type="text" name="nama_matakuliah"
                    class="form-control @error('nama_matakuliah') is-invalid @enderror"
                    value="{{ old('nama_matakuliah', $matakuliah->nama_matakuliah) }}" maxlength="75" required>
                @error('nama_matakuliah')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">SKS Teori <span class="text-danger">*</span></label>
                    <input type="number" name="sks_teori" class="form-control @error('sks_teori') is-invalid @enderror"
                        value="{{ old('sks_teori', $matakuliah->sks_teori) }}" min="0" max="255" required>
                    @error('sks_teori')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">SKS Praktik <span class="text-danger">*</span></label>
                    <input type="number" name="sks_praktik"
                        class="form-control @error('sks_praktik') is-invalid @enderror"
                        value="{{ old('sks_praktik', $matakuliah->sks_praktik) }}" min="0" max="255" required>
                    @error('sks_praktik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Program Studi</label>
                <select name="kode_program_studi" class="form-select @error('kode_program_studi') is-invalid @enderror">
                    <option value="">— Pilih Program Studi —</option>
                    @foreach($programStudis as $ps)
                    <option value="{{ $ps->kode_program_studi }}" {{ old('kode_program_studi', $matakuliah->
                        kode_program_studi) == $ps->kode_program_studi ? 'selected' : '' }}>
                        {{ $ps->nama_program_studi }}
                    </option>
                    @endforeach
                </select>
                @error('kode_program_studi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Jenis</label>
                    <input type="number" name="jenis" class="form-control @error('jenis') is-invalid @enderror"
                        value="{{ old('jenis', $matakuliah->jenis) }}">
                    @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Kode Kompetensi</label>
                    <input type="number" name="kode_kompetensi"
                        class="form-control @error('kode_kompetensi') is-invalid @enderror"
                        value="{{ old('kode_kompetensi', $matakuliah->kode_kompetensi) }}">
                    @error('kode_kompetensi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Block <span class="text-danger">*</span></label>
                <select name="block" class="form-select @error('block') is-invalid @enderror" required>
                    <option value="0" {{ old('block', $matakuliah->block) === '0' ? 'selected' : '' }}>Regular
                        (Non-Block)</option>
                    <option value="1" {{ old('block', $matakuliah->block) === '1' ? 'selected' : '' }}>Block</option>
                </select>
                @error('block')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Perbarui
                </button>
                <a href="{{ route('admin.matakuliah.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
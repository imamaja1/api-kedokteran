@extends('admin.layout')
@section('title', 'Tambah API Connection')
@section('page-title', 'Tambah API Connection')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header-custom"><i class="bi bi-plug-fill me-2"></i>Koneksi API Baru</div>
            <div class="card-body">
                <form action="{{ route('admin.connections.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Koneksi <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Contoh: SISKA API" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Keterangan singkat tentang koneksi ini (opsional)">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Base URL <span class="text-danger">*</span></label>
                        <input type="url" name="base_url"
                            class="form-control font-monospace @error('base_url') is-invalid @enderror"
                            value="{{ old('base_url') }}" placeholder="https://api.contoh.ac.id" required>
                        @error('base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username"
                                class="form-control @error('username') is-invalid @enderror"
                                value="{{ old('username') }}" placeholder="Username untuk login API" autocomplete="off">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Password untuk login API" autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{
                                old('is_active', '1' ) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Aktif</label>
                        </div>
                        <div class="text-muted small mt-1">Koneksi nonaktif tidak akan digunakan oleh sistem.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan
                        </button>
                        <a href="{{ route('admin.connections.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
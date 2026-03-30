@extends('admin.layout')
@section('title', 'Edit API Connection')
@section('page-title', 'Edit API Connection')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header-custom"><i class="bi bi-pencil-fill me-2"></i>Edit Koneksi API</div>
            <div class="card-body">
                <form action="{{ route('admin.connections.update', $connection) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Koneksi <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $connection->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror">{{ old('description', $connection->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Base URL <span class="text-danger">*</span></label>
                        <input type="url" name="base_url"
                            class="form-control font-monospace @error('base_url') is-invalid @enderror"
                            value="{{ old('base_url', $connection->base_url) }}" required>
                        @error('base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username"
                                class="form-control @error('username') is-invalid @enderror"
                                value="{{ old('username', $connection->username) }}" autocomplete="off">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                Password
                                <span class="text-muted small fw-normal">(kosongkan jika tidak diubah)</span>
                            </label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Informasi Cookie --}}
                    @if($connection->cookie)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Cookie Session</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                @if($connection->isCookieValid())
                                <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                <i class="bi bi-exclamation-circle-fill text-warning"></i>
                                @endif
                            </span>
                            <input type="text" class="form-control" readonly
                                value="{{ $connection->isCookieValid() ? 'Cookie aktif' . ($connection->cookie_expires_at ? ' — kadaluarsa ' . $connection->cookie_expires_at->format('d M Y H:i') : '') : 'Cookie kadaluarsa atau kosong' }}">
                        </div>
                        <div class="text-muted small mt-1">Cookie dikelola otomatis oleh sistem saat login ke API.</div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{
                                old('is_active', $connection->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Aktif</label>
                        </div>
                        <div class="text-muted small mt-1">Koneksi nonaktif tidak akan digunakan oleh sistem.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Update
                        </button>
                        <a href="{{ route('admin.connections.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
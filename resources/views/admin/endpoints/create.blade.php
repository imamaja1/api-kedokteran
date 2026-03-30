@extends('admin.layout')
@section('title', 'Tambah Endpoint')
@section('page-title', 'Tambah Endpoint')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header-custom"><i class="bi bi-plus-circle me-2"></i>Endpoint Baru</div>
            <div class="card-body">
                <form action="{{ route('admin.endpoints.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Judul Endpoint <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" required placeholder="contoh: Login Dosen">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                            <select name="api_section_id"
                                class="form-select @error('api_section_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Section --</option>
                                @foreach($sections as $sec)
                                <option value="{{ $sec->id }}" {{ old('api_section_id')==$sec->id ? 'selected' : '' }}>
                                    {{ $sec->title }}
                                </option>
                                @endforeach
                            </select>
                            @error('api_section_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="Penjelasan singkat fungsi endpoint ini">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select name="method" class="form-select" required>
                                @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                                <option value="{{ $m }}" {{ old('method','GET')===$m ? 'selected' : '' }}>{{ $m }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" class="form-control @error('url') is-invalid @enderror"
                                value="{{ old('url') }}" required placeholder="http://127.0.0.1:8000/api/v1/...">
                            @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" name="sort_order" class="form-control"
                                value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Headers <span
                                    class="text-muted small">(opsional)</span></label>
                            <textarea name="headers" class="form-control" rows="3"
                                style="font-family:monospace;font-size:.83rem"
                                placeholder='"Authorization": "Bearer {token}"'>{{ old('headers') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Body Request <span
                                    class="text-muted small">(opsional)</span></label>
                            <textarea name="body" class="form-control" rows="3"
                                style="font-family:monospace;font-size:.83rem"
                                placeholder='"email" : {email}&#10;"password" : {password}'>{{ old('body') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contoh Response <span
                                    class="text-muted small">(opsional)</span></label>
                            <textarea name="response_example" class="form-control" rows="4"
                                style="font-family:monospace;font-size:.83rem"
                                placeholder='{"status": "success", "data": {...}}'>{{ old('response_example') }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                        <a href="{{ route('admin.endpoints.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
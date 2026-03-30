@extends('admin.layout')
@section('title', 'Edit Section')
@section('page-title', 'Edit Section')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header-custom"><i class="bi bi-pencil-fill me-2"></i>Edit Section</div>
            <div class="card-body">
                <form action="{{ route('admin.sections.update', $section) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Section <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title', $section->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Urutan Tampil</label>
                        <input type="number" name="sort_order" class="form-control"
                            value="{{ old('sort_order', $section->sort_order) }}" min="0">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Update
                        </button>
                        <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
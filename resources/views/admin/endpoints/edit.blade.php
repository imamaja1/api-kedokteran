@extends('admin.layout')
@section('title', 'Edit Endpoint')
@section('page-title', 'Edit Endpoint')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header-custom"><i class="bi bi-pencil-fill me-2"></i>Edit Endpoint</div>
            <div class="card-body">
                <form action="{{ route('admin.endpoints.update', $endpoint) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Judul Endpoint <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $endpoint->title) }}" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                            <select name="api_section_id" class="form-select" required>
                                @foreach($sections as $sec)
                                <option value="{{ $sec->id }}" {{ old('api_section_id', $endpoint->api_section_id) ==
                                    $sec->id ? 'selected' : '' }}>
                                    {{ $sec->title }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="description" class="form-control"
                                rows="2">{{ old('description', $endpoint->description) }}</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select name="method" class="form-select" required>
                                @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                                <option value="{{ $m }}" {{ old('method', $endpoint->method) === $m ? 'selected' : ''
                                    }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" class="form-control" value="{{ old('url', $endpoint->url) }}"
                                required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" name="sort_order" class="form-control"
                                value="{{ old('sort_order', $endpoint->sort_order) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Headers</label>
                            <textarea name="headers" class="form-control" rows="3"
                                style="font-family:monospace;font-size:.83rem">{{ old('headers', $endpoint->headers) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Body Request</label>
                            <textarea name="body" class="form-control" rows="3"
                                style="font-family:monospace;font-size:.83rem">{{ old('body', $endpoint->body) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contoh Response</label>
                            <textarea name="response_example" class="form-control" rows="4"
                                style="font-family:monospace;font-size:.83rem">{{ old('response_example', $endpoint->response_example) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
                        <a href="{{ route('admin.endpoints.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
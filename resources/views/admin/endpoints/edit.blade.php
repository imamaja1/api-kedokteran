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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Param / Body Request</label>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.75rem" onclick="formatJsonField('body')">Format JSON</button>
                            </div>
                            <textarea id="body" name="body" class="form-control" rows="5"
                                style="font-family:monospace;font-size:.83rem">{{ old('body', $endpoint->body) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contoh Response</label>
                            <textarea name="response_example" class="form-control" rows="4"
                                style="font-family:monospace;font-size:.83rem">{{ old('response_example', $endpoint->response_example) }}</textarea>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">Parameter &amp; Prerequisite ({{ $endpoint->parameters->count() }})</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addParamRow()">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Parameter
                                </button>
                            </div>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-bordered mb-0 align-middle" id="paramsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Tipe</th>
                                            <th>Lokasi</th>
                                            <th class="text-center">Wajib</th>
                                            <th>Source</th>
                                            <th>Endpoint Sumber</th>
                                            <th>Example</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="paramsTbody">
                                        @foreach($endpoint->parameters as $idx => $param)
                                        <tr>
                                            <td><input type="text" name="parameters[{{ $idx }}][name]" value="{{ $param->name }}" class="form-control form-control-sm"></td>
                                            <td>
                                                <select name="parameters[{{ $idx }}][type]" class="form-select form-select-sm">
                                                    @foreach(['string','integer','number','boolean','array'] as $t)
                                                    <option value="{{ $t }}" @selected($param->type === $t)>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="parameters[{{ $idx }}][location]" class="form-select form-select-sm">
                                                    @foreach(['body','query','path'] as $loc)
                                                    <option value="{{ $loc }}" @selected($param->location === $loc)>{{ $loc }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center"><input type="checkbox" name="parameters[{{ $idx }}][is_required]" value="1" @checked($param->is_required)></td>
                                            <td>
                                                <select name="parameters[{{ $idx }}][source_type]" class="form-select form-select-sm">
                                                    @foreach(['input','endpoint','session'] as $st)
                                                    <option value="{{ $st }}" @selected($param->source_type === $st)>{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="parameters[{{ $idx }}][source_endpoint_id]" class="form-select form-select-sm" style="max-width:200px">
                                                    <option value="">-- Tidak ada --</option>
                                                    @foreach($allEndpoints as $srcEp)
                                                    <option value="{{ $srcEp->id }}" @selected($param->source_endpoint_id == $srcEp->id)>[{{ $srcEp->method }}] {{ $srcEp->url }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="parameters[{{ $idx }}][example]" value="{{ $param->example }}" class="form-control form-control-sm"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
<script>
let paramRowIdx = {{ $endpoint->parameters->count() }};
function formatJsonField(id) {
    const el = document.getElementById(id);
    try {
        const parsed = JSON.parse(el.value);
        el.value = JSON.stringify(parsed, null, 4);
    } catch (e) {
        alert('Format JSON tidak valid: ' + e.message);
    }
}
function addParamRow() {
    const tbody = document.getElementById('paramsTbody');
    const idx = paramRowIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="parameters[${idx}][name]" class="form-control form-control-sm" placeholder="nama_param"></td>
        <td><select name="parameters[${idx}][type]" class="form-select form-select-sm"><option value="string">string</option><option value="integer">integer</option><option value="number">number</option><option value="boolean">boolean</option><option value="array">array</option></select></td>
        <td><select name="parameters[${idx}][location]" class="form-select form-select-sm"><option value="body">body</option><option value="query">query</option><option value="path">path</option></select></td>
        <td class="text-center"><input type="checkbox" name="parameters[${idx}][is_required]" value="1" checked></td>
        <td><select name="parameters[${idx}][source_type]" class="form-select form-select-sm"><option value="input">input</option><option value="endpoint">endpoint</option><option value="session">session</option></select></td>
        <td><select name="parameters[${idx}][source_endpoint_id]" class="form-select form-select-sm" style="max-width:200px"><option value="">-- Tidak ada --</option>@foreach($allEndpoints as $srcEp)<option value="{{ $srcEp->id }}">[{{ $srcEp->method }}] {{ $srcEp->url }}</option>@endforeach</select></td>
        <td><input type="text" name="parameters[${idx}][example]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection
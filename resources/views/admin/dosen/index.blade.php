@extends('admin.layout')
@section('title', 'Dosen')
@section('page-title', 'Manajemen Dosen')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-person-workspace me-2"></i>Daftar Dosen</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.dosen.create') }}" class="btn btn-sm btn-light">
                <i class="bi bi-plus-lg me-1"></i>Tambah Dosen
            </a>
            <form action="{{ route('admin.dosen.sync-siska') }}" method="POST" class="d-inline"
                onsubmit="return confirm('Mulai sinkronisasi data dosen dari SISKA?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
                </button>
            </form>
            {{-- <a href="" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
            </a> --}}
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="{{ route('admin.dosen.index') }}" class="d-flex flex-wrap gap-2 w-100">
            <select name="aktif" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Semua Keaktifan</option>
                <option value="A" {{ request('aktif')==='A' ? 'selected' : '' }}>Aktif</option>
                <option value="N" {{ request('aktif')==='N' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            <div class="input-group input-group-sm flex-grow-1" style="min-width:180px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, email..."
                    value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if(request('search') || request('aktif'))
                <a href="{{ route('admin.dosen.index') }}" class="btn btn-outline-danger">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:60px">#</th>
                    <th>Nama Dosen</th>
                    <th class="d-none d-md-table-cell">Program Studi</th>
                    <th class="d-none d-lg-table-cell">Email</th>
                    <th class="d-none d-lg-table-cell">No. Telp</th>
                    <th class="d-none d-sm-table-cell">Status</th>
                    <th style="width:110px" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dosens as $nomor => $d)
                <tr>
                    <td class="ps-3 text-muted d-none d-sm-table-cell small">{{ $nomor + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $d->nama_dosen }}</div>
                        @if($d->field_studi)
                        <div class="text-muted small">{{ $d->field_studi }}</div>
                        @endif
                        <div class="d-md-none text-muted small">{{ $d->programStudi->nama_program_studi ?? '-' }}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge bg-light text-dark border">{{ $d->programStudi->nama_program_studi ?? '-'
                            }}</span>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">{{ $d->alamat_email ?? '-' }}</td>
                    <td class="d-none d-lg-table-cell text-muted small">{{ $d->no_telp ?? '-' }}</td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge {{ $d->aktif === 'A' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $d->aktif === 'A' ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.dosen.edit', $d) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.dosen.destroy', $d) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus dosen {{ $d->nama_dosen }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data dosen.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dosens->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan <strong>{{ $dosens->firstItem()
                    }}</strong>–<strong>{{ $dosens->lastItem() }}</strong> dari <strong>{{ $dosens->total()
                    }}</strong> dosen
            </small>
            {{ $dosens->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
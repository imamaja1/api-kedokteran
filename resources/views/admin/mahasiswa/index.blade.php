@extends('admin.layout')
@section('title', 'Mahasiswa')
@section('page-title', 'Manajemen Mahasiswa')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-mortarboard-fill me-2"></i>Daftar Mahasiswa</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.mahasiswa.create') }}" class="btn btn-sm btn-light">
                <i class="bi bi-plus-lg me-1"></i>Tambah Mahasiswa
            </a>
            <form action="{{ route('admin.mahasiswa.sync-siska') }}" method="POST" class="d-inline"
                onsubmit="return confirm('Mulai sinkronisasi data mahasiswa dari SISKA?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
                </button>
            </form>
        </div>
    </div>
    {{-- Filter bar --}}
    <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="{{ route('admin.mahasiswa.index') }}" class="d-flex flex-wrap gap-2 w-100">
            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="A" {{ request('status')==='A' ? 'selected' : '' }}>Aktif</option>
                <option value="N" {{ request('status')==='N' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
            <div class="input-group input-group-sm flex-grow-1" style="min-width:180px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari NIM, nama, email..."
                    value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if(request('search') || request('status'))
                <a href="{{ route('admin.mahasiswa.index') }}" class="btn btn-outline-danger">
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
                    <th class="ps-3">NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th class="d-none d-md-table-cell">Program Studi</th>
                    <th class="d-none d-lg-table-cell">Email</th>
                    <th class="d-none d-sm-table-cell">Status</th>
                    <th style="width:130px" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mahasiswas as $m)
                <tr class="{{ $m->trashed() ? 'table-secondary text-muted' : '' }}">
                    <td class="ps-3 font-monospace small fw-semibold">{{ $m->nim }}</td>
                    <td>
                        <div class="fw-semibold">
                            {{ $m->nama_mahasiswa }}
                            @if($m->trashed())
                            <span class="badge bg-secondary ms-1">Dihapus</span>
                            @endif
                        </div>
                        <div class="d-md-none text-muted small">{{ $m->programStudi->nama_program_studi ?? '-' }}</div>
                        <div class="d-lg-none text-muted small">{{ $m->email }}</div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">
                        <div class="fw-semibold">{{ $m->programStudi->nama_program_studi ?? '-' }}</div>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">{{ $m->email ?? '-' }}</td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge {{ $m->status === 'A' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $m->status === 'A' ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($m->trashed())
                        <form action="{{ route('admin.mahasiswa.restore', $m->nim) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-success" title="Pulihkan">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.mahasiswa.force-delete', $m->nim) }}" method="POST"
                            class="d-inline"
                            onsubmit="return confirm('Hapus permanen mahasiswa {{ $m->nama_mahasiswa }}? Tidak dapat dibatalkan!')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" title="Hapus Permanen">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </form>
                        @else
                        <a href="{{ route('admin.mahasiswa.edit', $m->nim) }}"
                            class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.mahasiswa.destroy', $m->nim) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Nonaktifkan mahasiswa {{ $m->nama_mahasiswa }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data mahasiswa.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mahasiswas->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan <strong>{{ $mahasiswas->firstItem()
                    }}</strong>–<strong>{{ $mahasiswas->lastItem() }}</strong> dari <strong>{{ $mahasiswas->total()
                    }}</strong> mahasiswa
            </small>
            {{ $mahasiswas->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
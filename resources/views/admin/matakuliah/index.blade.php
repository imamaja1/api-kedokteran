@extends('admin.layout')
@section('title', 'Matakuliah')
@section('page-title', 'Manajemen Matakuliah')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-journal-bookmark-fill me-2"></i>Daftar Matakuliah</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.matakuliah.create') }}" class="btn btn-sm btn-light">
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </a>
            <form action="{{ route('admin.matakuliah.sync-siska') }}" method="POST" class="d-inline"
                onsubmit="return confirm('Mulai sinkronisasi matakuliah dari SISKA?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-repeat me-1"></i>Sync SISKA
                </button>
            </form>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="px-3 py-2 border-bottom bg-light">
        <form method="GET" action="{{ route('admin.matakuliah.index') }}"
            class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width:280px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama / kode..."
                    value="{{ request('search') }}">
            </div>

            <select name="block" class="form-select form-select-sm" style="max-width:140px">
                <option value="">Semua Block</option>
                <option value="0" {{ request('block')==='0' ? 'selected' : '' }}>Non-Block</option>
                <option value="1" {{ request('block')==='1' ? 'selected' : '' }}>Block</option>
            </select>

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            @if(request()->hasAny(['search','block']))
            <a href="{{ route('admin.matakuliah.index') }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg"></i> Reset
            </a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Kode</th>
                    <th>Nama Matakuliah</th>
                    <th class="text-center d-none d-md-table-cell">SKS Teori</th>
                    <th class="text-center d-none d-md-table-cell">SKS Praktik</th>
                    <th class="text-center d-none d-sm-table-cell">Block</th>
                    <th style="width:100px" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matakuliahs as $mk)
                <tr>
                    <td class="ps-3 font-monospace small fw-semibold text-muted">{{ $mk->kode_matakuliah }}</td>
                    <td>
                        <div class="fw-semibold">{{ $mk->nama_matakuliah }}</div>
                        <div class="d-md-none text-muted small">SKS: {{ $mk->sks_teori }}T / {{ $mk->sks_praktik }}P
                        </div>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <span class="badge bg-primary rounded-pill">{{ $mk->sks_teori }}</span>
                    </td>
                    <td class="text-center d-none d-md-table-cell">
                        <span class="badge bg-info text-dark rounded-pill">{{ $mk->sks_praktik }}</span>
                    </td>
                    <td class="text-center d-none d-sm-table-cell">
                        @if($mk->block === '1')
                        <span class="badge bg-warning text-dark">Block</span>
                        @else
                        <span class="badge bg-light text-dark border">Regular</span>
                        @endif
                    </td>
                    <td class="text-center pe-3">
                        <a href="{{ route('admin.matakuliah.edit', $mk->id_matakuliah) }}"
                            class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.matakuliah.destroy', $mk->id_matakuliah) }}" method="POST"
                            class="d-inline" onsubmit="return confirm('Hapus matakuliah {{ $mk->nama_matakuliah }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>Belum ada data matakuliah.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($matakuliahs->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan
                <strong>{{ $matakuliahs->firstItem() }}</strong>–<strong>{{ $matakuliahs->lastItem() }}</strong>
                dari <strong>{{ $matakuliahs->total() }}</strong> matakuliah
            </small>
            {{ $matakuliahs->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
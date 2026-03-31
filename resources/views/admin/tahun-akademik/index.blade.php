@extends('admin.layout')
@section('title', 'Tahun Akademik')
@section('page-title', 'Tahun Akademik')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-calendar-fill me-2"></i>Daftar Tahun Akademik</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tahun-akademik.create') }}" class="btn btn-sm btn-light">
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </a>
            <form action="{{ route('admin.tahun-akademik.sync-siska') }}" method="POST" class="d-inline"
                onsubmit="return confirm('Mulai sinkronisasi tahun akademik dari SISKA?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
                </button>
            </form>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="{{ route('admin.tahun-akademik.index') }}" class="d-flex flex-wrap gap-2 w-100">
            <select name="semester" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                <option value="1" {{ request('semester')==='1' ? 'selected' : '' }}>Semester 1</option>
                <option value="2" {{ request('semester')==='2' ? 'selected' : '' }}>Semester 2</option>
            </select>
            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="A" {{ request('status')==='A' ? 'selected' : '' }}>Aktif</option>
                <option value="N" {{ request('status')==='N' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
            <div class="input-group input-group-sm flex-grow-1" style="min-width:180px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari tahun akademik..."
                    value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if(request('search') || request('status') || request('semester'))
                <a href="{{ route('admin.tahun-akademik.index') }}" class="btn btn-outline-danger">
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
                    <th class="ps-3">Tahun Akademik</th>
                    <th>Semester</th>
                    <th class="d-none d-md-table-cell">Tanggal Mulai</th>
                    <th class="d-none d-md-table-cell">Tanggal Berakhir</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell">KPAT</th>
                    <th style="width:110px" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tahunAkademiks as $ta)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">{{ $ta->tahun_akademik }}</div>
                        <div class="d-md-none text-muted small">
                            {{ $ta->tanggal_mulai->format('d M Y') }} &ndash; {{ $ta->tanggal_berakhir->format('d M Y')
                            }}
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $ta->semester == '1' ? 'bg-primary' : 'bg-info text-dark' }}">
                            Sem {{ $ta->semester }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $ta->tanggal_mulai->format('d M Y') }}</td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $ta->tanggal_berakhir->format('d M Y') }}
                    </td>
                    <td>
                        <span class="badge {{ $ta->status === 'A' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $ta->status === 'A' ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <span
                            class="badge {{ $ta->status_kpat === 'A' ? 'bg-warning text-dark' : 'bg-light text-muted border' }}">
                            {{ $ta->status_kpat === 'A' ? 'Buka' : 'Tutup' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.tahun-akademik.edit', $ta) }}"
                            class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.tahun-akademik.destroy', $ta) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus tahun akademik {{ $ta->tahun_akademik }} Sem {{ $ta->semester }}?')">
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
                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>Belum ada data tahun akademik.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tahunAkademiks->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan <strong>{{ $tahunAkademiks->firstItem()
                    }}</strong>–<strong>{{ $tahunAkademiks->lastItem() }}</strong> dari <strong>{{
                    $tahunAkademiks->total() }}</strong> data
            </small>
            {{ $tahunAkademiks->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
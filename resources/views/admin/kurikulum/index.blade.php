@extends('admin.layout')
@section('title', 'Kurikulum')
@section('page-title', 'Manajemen Kurikulum')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-journal-richtext me-2"></i>Daftar Nama Kurikulum</span>
        <form action="{{ route('admin.kurikulum.sync-siska') }}" method="POST" class="d-inline"
            onsubmit="return confirm('Mulai sinkronisasi data kurikulum dari SISKA?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
            </button>
        </form>
    </div>

    {{-- Filter --}}
    <div class="px-3 py-2 border-bottom bg-light">
        <form method="GET" action="{{ route('admin.kurikulum.index') }}"
            class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm flex-grow-1" style="min-width:220px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama kurikulum..."
                    value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if(request('search'))
                <a href="{{ route('admin.kurikulum.index') }}" class="btn btn-outline-danger">
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
                    <th class="ps-3" style="width:50px">#</th>
                    <th style="width:80px">Kode</th>
                    <th>Nama Kurikulum</th>
                    <th class="d-none d-md-table-cell">Angkatan</th>
                    <th class="d-none d-md-table-cell">Ekstensi</th>
                    <th class="d-none d-md-table-cell">Paket</th>
                    <th class="text-center" style="width:90px">Jml MK</th>
                    <th class="text-center" style="width:90px">Angkatan</th>
                    <th class="text-center" style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($namaKurikulumList as $i => $nk)
                <tr>
                    <td class="ps-3 text-muted small">{{ $namaKurikulumList->firstItem() + $i }}</td>
                    <td class="text-muted small">{{ $nk->kode_nama_kurikulum }}</td>
                    <td class="fw-semibold">{{ $nk->nama_kurikulum }}</td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $nk->angkatan1 ?? '-' }}</td>
                    <td class="d-none d-md-table-cell">
                        @if($nk->ekstensi1 === 'Y')
                        <span class="badge bg-success">Y</span>
                        @else
                        <span class="badge bg-secondary">N</span>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell">
                        @if($nk->paket1 === 'Y')
                        <span class="badge bg-success">Y</span>
                        @else
                        <span class="badge bg-secondary">N</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info text-dark">{{ $nk->kurikulum_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-warning text-dark">{{ $nk->kurikulum_angkatan_count }}</span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.kurikulum.show', $nk->kode_nama_kurikulum) }}"
                            class="btn btn-sm btn-outline-primary" title="Detail">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data kurikulum. Klik <strong>Sinkronisasi
                            SISKA</strong> untuk mengambil data.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($namaKurikulumList->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan
                <strong>{{ $namaKurikulumList->firstItem() }}</strong>–<strong>{{ $namaKurikulumList->lastItem()
                    }}</strong>
                dari <strong>{{ $namaKurikulumList->total() }}</strong> kurikulum
            </small>
            {{ $namaKurikulumList->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
@extends('admin.layout')
@section('title', 'Tahun Akademik')
@section('page-title', 'Tahun Akademik')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-fill me-2"></i>Daftar Tahun Akademik</span>
        <a href="{{ route('admin.tahun-akademik.create') }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Tambah
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:60px">#</th>
                    <th>Tahun Akademik</th>
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
                    <td class="ps-3 text-muted small d-none d-sm-table-cell">{{ $ta->kode_tahun_akademik }}</td>
                    <td>
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
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data tahun akademik.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
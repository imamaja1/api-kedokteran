@extends('admin.layout')
@section('title', 'KRS & KHS Mahasiswa')
@section('page-title', 'KRS & KHS Mahasiswa')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-journal-text me-2"></i>Daftar KRS Mahasiswa</span>
        <form action="{{ route('admin.krs-khs.sync-siska') }}" method="POST"
            onsubmit="return confirm('Mulai sinkronisasi KRS dari SISKA?')">
            @csrf
            <button type="submit" class="btn btn-sm  btn-outline-light">
                <i class="bi bi-arrow-repeat me-1"></i>Sync SISKA
            </button>
        </form>
    </div>

    {{-- Filter --}}
    <div class="px-3 py-2 border-bottom bg-light">
        <form method="GET" action="{{ route('admin.krs-khs.index') }}"
            class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="nim" class="form-control form-control-sm" style="max-width:160px"
                placeholder="Cari NIM..." value="{{ request('nim') }}">

            <select name="kode_tahun_akademik" class="form-select form-select-sm" style="max-width:200px">
                <option value="">Semua Tahun Akademik</option>
                @foreach($tahunAkademiks as $ta)
                <option value="{{ $ta->kode_tahun_akademik }}" {{ request('kode_tahun_akademik')==$ta->
                    kode_tahun_akademik ? 'selected' : '' }}>
                    {{ $ta->tahun_akademik }} — {{ $ta->semester }}
                </option>
                @endforeach
            </select>

            <select name="semester" class="form-select form-select-sm" style="max-width:130px">
                <option value="">Semua Semester</option>
                @foreach(range(1, 14) as $s)
                <option value="{{ $s }}" {{ request('semester')==$s ? 'selected' : '' }}>Semester {{ $s }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            @if(request()->hasAny(['nim','kode_tahun_akademik','semester']))
            <a href="{{ route('admin.krs-khs.index') }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg"></i> Reset
            </a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Kode KRS</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th class="d-none d-md-table-cell">Tahun Akademik</th>
                    <th class="d-none d-md-table-cell">Semester</th>
                    <th class="text-center">Jml MK</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($krsList as $krs)
                <tr>
                    <td class="ps-3 fw-semibold text-muted">#{{ $krs->kode_krs }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $krs->nim }}</span></td>
                    <td>{{ $krs->mahasiswa->nama_mahasiswa ?? '—' }}</td>
                    <td class="d-none d-md-table-cell">
                        {{ $krs->tahunAkademik->tahun_akademik ?? '—' }}
                        <span class="text-muted small">({{ $krs->tahunAkademik->semester ?? '' }})</span>
                    </td>
                    <td class="d-none d-md-table-cell">{{ $krs->semester }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary rounded-pill">{{ $krs->detail->count() }}</span>
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('admin.krs-khs.show', $krs->kode_krs) }}"
                            class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>Belum ada data KRS
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($krsList->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan <strong>{{ $krsList->firstItem()
                    }}</strong>–<strong>{{ $krsList->lastItem() }}</strong> dari <strong>{{ $krsList->total()
                    }}</strong> mahasiswa
            </small>
            {{ $krsList->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
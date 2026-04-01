@extends('admin.layout')
@section('title', 'Kelas')
@section('page-title', 'Manajemen Kelas')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-diagram-3-fill me-2"></i>Daftar Kelas</span>
        <form action="{{ route('admin.mahasiswa.sync-siska') }}" method="POST" class="d-inline"
            onsubmit="return confirm('Mulai sinkronisasi data mahasiswa dari SISKA?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-repeat me-1"></i>Sinkronisasi SISKA
            </button>
        </form>
    </div>

    {{-- Filter --}}
    <div class="px-3 py-2 border-bottom bg-light">
        <form method="GET" action="{{ route('admin.kelas.index') }}" class="d-flex flex-wrap gap-2 align-items-center">

            <select name="nama_kelas_id" class="form-select form-select-sm" style="max-width:140px"
                onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($namaKelasList as $nk)
                <option value="{{ $nk->nama_kelas_id }}" {{ request('nama_kelas_id')==$nk->nama_kelas_id ? 'selected' :
                    '' }}>
                    Kelas {{ $nk->nama_kelas }}
                </option>
                @endforeach
            </select>

            <select name="semester" class="form-select form-select-sm" style="max-width:140px"
                onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                @foreach(range(1, 14) as $s)
                <option value="{{ $s }}" {{ request('semester')==$s ? 'selected' : '' }}>
                    Semester {{ $s }}
                </option>
                @endforeach
            </select>

            <select name="kode_tahun_akademik" class="form-select form-select-sm" style="max-width:200px"
                onchange="this.form.submit()">
                <option value="">Semua Tahun Akademik</option>
                @foreach($tahunAkademiks as $ta)
                <option value="{{ $ta->kode_tahun_akademik }}" {{ request('kode_tahun_akademik')==$ta->
                    kode_tahun_akademik ? 'selected' : '' }}>
                    {{ $ta->tahun_akademik }} — {{ $ta->semester }}
                </option>
                @endforeach
            </select>

            <div class="input-group input-group-sm flex-grow-1" style="min-width:180px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama / kode matakuliah..."
                    value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if(request()->hasAny(['nama_kelas_id', 'semester', 'kode_tahun_akademik', 'search']))
                <a href="{{ route('admin.kelas.index') }}" class="btn btn-outline-danger">
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
                    <th>Kelas</th>
                    <th class="d-none d-md-table-cell">Matakuliah</th>
                    <th class="d-none d-sm-table-cell">Semester</th>
                    <th class="d-none d-lg-table-cell">Tahun Akademik</th>
                    <th class="text-center" style="width:80px">Mahasiswa</th>
                    <th class="text-center" style="width:80px">Dosen</th>
                    <th class="text-center" style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kelasList as $i => $k)
                <tr>
                    <td class="ps-3 text-muted small">{{ $kelasList->firstItem() + $i }}</td>
                    <td>
                        <span class="fw-semibold">
                            Kelas {{ $k->namaKelas->nama_kelas ?? '-' }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="fw-medium">{{ $k->matakuliah->nama_matakuliah ?? '-' }}</div>
                        <div class="text-muted small">{{ $k->matakuliah->kode_matakuliah ?? '' }}</div>
                    </td>
                    <td class="d-none d-sm-table-cell text-muted small">{{ $k->semester ?? '-' }}</td>
                    <td class="d-none d-lg-table-cell text-muted small">{{ $k->kode_tahun_akademik ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-info text-dark">
                            {{ $k->kelasMahasiswa->count() }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-warning text-dark">
                            {{ $k->mengajar->count() }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.kelas.show', $k->kelas_id) }}" class="btn btn-sm btn-outline-primary"
                            title="Detail">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data kelas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($kelasList->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $kelasList->links() }}
    </div>
    @endif
</div>
@endsection
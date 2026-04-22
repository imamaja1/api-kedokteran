@extends('admin.layout')
@section('title', $namaKurikulum->nama_kurikulum)
@section('page-title', 'Detail Kurikulum')

@section('content')

{{-- Header kurikulum --}}
<div class="card mb-3">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="bi bi-journal-richtext me-2"></i>
            {{ $namaKurikulum->nama_kurikulum }}
            <span class="badge bg-light text-dark ms-2">Kode: {{ $namaKurikulum->kode_nama_kurikulum }}</span>
        </span>
        <a href="{{ route('admin.kurikulum.index') }}" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body py-2">
        <div class="row g-2">
            <div class="col-sm-4 col-md-3">
                <span class="text-muted small">Angkatan</span>
                <div class="fw-semibold">{{ $namaKurikulum->angkatan1 ?? '-' }}</div>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted small">Ekstensi</span>
                <div>
                    @if($namaKurikulum->ekstensi1 === 'Y')
                    <span class="badge bg-success">Y</span>
                    @else
                    <span class="badge bg-secondary">{{ $namaKurikulum->ekstensi1 ?? 'N' }}</span>
                    @endif
                </div>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted small">Paket</span>
                <div>
                    @if($namaKurikulum->paket1 === 'Y')
                    <span class="badge bg-success">Y</span>
                    @else
                    <span class="badge bg-secondary">{{ $namaKurikulum->paket1 ?? 'N' }}</span>
                    @endif
                </div>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted small">Total MK</span>
                <div class="fw-semibold text-info">{{ $kurikulumList->total() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Angkatan --}}
@if($angkatanList->isNotEmpty())
<div class="card mb-3">
    <div class="card-header-custom">
        <i class="bi bi-people-fill me-2"></i>Data Angkatan
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:50px">#</th>
                    <th style="width:80px">Kode</th>
                    <th>Angkatan</th>
                    <th class="text-center">Ekstensi</th>
                    <th class="text-center">Paket</th>
                    <th class="d-none d-md-table-cell">Semester Stup Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($angkatanList as $i => $ang)
                <tr>
                    <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                    <td class="text-muted small">{{ $ang->kode_kurikulum_angkatan }}</td>
                    <td class="fw-semibold">{{ $ang->angkatan ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $ang->ekstensi === 'Y' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $ang->ekstensi ?? 'N' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $ang->paket === 'Y' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $ang->paket ?? 'N' }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $ang->semester_stup_grade ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Ringkasan Total SKS per Semester --}}
@if($sksBySemester->isNotEmpty())
<div class="card mb-3">
    <div class="card-header-custom">
        <i class="bi bi-calculator-fill me-2"></i>Ringkasan SKS per Semester
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-start">Semester</th>
                    <th>Jumlah MK</th>
                    <th>SKS Teori</th>
                    <th>SKS Praktikum</th>
                    <th class="fw-bold">Total SKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sksBySemester as $sem => $info)
                <tr>
                    <td class="ps-3 text-start fw-semibold">Semester {{ $sem }}</td>
                    <td><span class="badge bg-secondary">{{ $info['jumlah_mk'] }} MK</span></td>
                    <td>{{ $info['sks_teori'] }}</td>
                    <td>{{ $info['sks_praktik'] }}</td>
                    <td><span class="badge bg-primary fs-6">{{ $info['total_sks'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td class="ps-3 text-start">Total Keseluruhan</td>
                    <td>{{ $sksBySemester->sum('jumlah_mk') }} MK</td>
                    <td>{{ $sksBySemester->sum('sks_teori') }}</td>
                    <td>{{ $sksBySemester->sum('sks_praktik') }}</td>
                    <td><span class="badge bg-success fs-6">{{ $sksBySemester->sum('total_sks') }}</span></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Kurikulum (daftar MK) --}}
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-list-check me-2"></i>Daftar Matakuliah dalam Kurikulum</span>
    </div>

    {{-- Filter semester --}}
    <div class="px-3 py-2 border-bottom bg-light">
        <form method="GET" action="{{ route('admin.kurikulum.show', $namaKurikulum->kode_nama_kurikulum) }}"
            class="d-flex flex-wrap gap-2 align-items-center">
            <select name="semester" class="form-select form-select-sm" style="max-width:160px"
                onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                @foreach(range(1, 14) as $s)
                <option value="{{ $s }}" {{ request('semester')==$s ? 'selected' : '' }}>
                    Semester {{ $s }}
                </option>
                @endforeach
            </select>
            @if(request('semester'))
            <a href="{{ route('admin.kurikulum.show', $namaKurikulum->kode_nama_kurikulum) }}"
                class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg"></i> Reset
            </a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:100px">Semester</th>
                    <th class="d-none d-md-table-cell">Kode MK</th>
                    <th>Matakuliah</th>
                    <th class="text-center">SKS teori</th>
                    <th class="text-center">SKS praktikum</th>
                    <th class="text-center">Total SKS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kurikulumList as $i => $k)
                <tr>
                    <td class="text-center">
                        <span class="badge bg-info">Semester {{ $k->semester }}</span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $k->matakuliah->kode_matakuliah ?? '-' }}
                    </td>
                    <td>{{ $k->matakuliah->nama_matakuliah ?? '-' }}</td>
                    <td class="text-center">{{ $k->matakuliah->sks_teori ?? '-' }}</td>
                    <td class="text-center">{{ $k->matakuliah->sks_praktik ?? '-' }}</td>
                    <td class="text-center">{{ ($k->matakuliah->sks_teori ?? 0) + ($k->matakuliah->sks_praktik ?? 0) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-inbox me-2"></i>Belum ada data matakuliah pada kurikulum ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($kurikulumList->hasPages())
    <div class="px-3 py-3 border-top bg-light">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Menampilkan
                <strong>{{ $kurikulumList->firstItem() }}</strong>–<strong>{{ $kurikulumList->lastItem() }}</strong>
                dari <strong>{{ $kurikulumList->total() }}</strong> matakuliah
            </small>
            {{ $kurikulumList->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection
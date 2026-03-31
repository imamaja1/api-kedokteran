@extends('admin.layout')
@section('title', 'Detail KRS #' . $krs->kode_krs)
@section('page-title', 'Detail KRS Mahasiswa')

@section('content')
{{-- Info KRS --}}
<div class="card mb-3">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-text me-2"></i>KRS #{{ $krs->kode_krs }}</span>
        <a href="{{ route('admin.krs-khs.index') }}" class="btn btn-sm btn-light">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">NIM</div>
                <div class="fw-semibold">{{ $krs->nim }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Nama Mahasiswa</div>
                <div class="fw-semibold">{{ $krs->mahasiswa->nama_mahasiswa ?? '—' }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Tahun Akademik</div>
                <div class="fw-semibold">
                    {{ $krs->tahunAkademik->tahun_akademik ?? '—' }}
                    <span class="text-muted">({{ $krs->tahunAkademik->semester ?? '' }})</span>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Semester</div>
                <div class="fw-semibold">{{ $krs->semester }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel KRS Detail + KHS --}}
<div class="card">
    <div class="card-header-custom">
        <i class="bi bi-table me-2"></i>Daftar Matakuliah &amp; Nilai KHS
        <span class="badge bg-light text-dark border ms-2">{{ $krs->detail->count() }} MK</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:.88rem">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th class="ps-2">Kode MK</th>
                    <th>Nama Matakuliah</th>
                    <th class="text-center">SKS Teori</th>
                    <th class="text-center">SKS Praktik</th>
                    <th class="text-center">Status KRS</th>
                    <th class="text-center">Nilai Akhir</th>
                    <th class="text-center">Tidak Berhak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($krs->detail as $i => $detail)
                @php
                $statusColor = ['B' => 'primary', 'U' => 'warning', 'K' => 'info'][$detail->status] ?? 'secondary';
                @endphp
                <tr>
                    <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                    <td class="ps-2">
                        <span class="badge bg-light text-dark border">{{ $detail->kode_matakuliah }}</span>
                    </td>
                    <td>{{ $detail->nama_matakuliah ?? '—' }}</td>
                    <td class="text-center">{{ $detail->sks_teori ?? '—' }}</td>
                    <td class="text-center">{{ $detail->sks_praktik ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-{{ $statusColor }}">{{ $detail->status ?? '—' }}</span>
                    </td>
                    <td class="text-center">{{ $detail->nilai_akhir ?? '—' }}</td>
                    <td class="text-center">{{ $detail->tidak_berhak ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>Belum ada matakuliah di KRS ini
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
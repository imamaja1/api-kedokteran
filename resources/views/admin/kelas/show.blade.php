@extends('admin.layout')
@section('title', 'Detail Kelas')
@section('page-title', 'Detail Kelas')

@section('content')

{{-- Header info kelas --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.kelas.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">
        Kelas {{ $kelas->namaKelas->nama_kelas ?? '-' }}
        &mdash; {{ $kelas->matakuliah->nama_matakuliah ?? 'Matakuliah tidak ditemukan' }}
    </h5>
    <span class="badge bg-secondary ms-1">Semester {{ $kelas->semester }}</span>
    <span class="badge bg-light text-dark border ms-1">TA {{ $kelas->kode_tahun_akademik }}</span>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
<div class="row g-4">
    {{-- ===== MAHASISWA TABLE ===== --}}
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div
                class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <span class="fw-semibold text-dark">
                    <i class="bi bi-mortarboard-fill me-2 text-primary"></i>Mahasiswa
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-1">{{ $kelasMahasiswas->total()
                        }}</span>
                </span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahMhs">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                </button>
            </div>

            {{-- Search bar --}}
            <div class="px-3 py-2 border-bottom bg-light">
                <form method="GET" action="{{ route('admin.kelas.show', $kelas->kelas_id) }}"
                    class="d-flex flex-wrap gap-2 align-items-center">
                    {{-- preserve dosen search --}}
                    @if(request('dosen_search'))
                    <input type="hidden" name="dosen_search" value="{{ request('dosen_search') }}">
                    @endif
                    <div class="input-group input-group-sm flex-grow-1" style="min-width:200px;max-width:400px">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="mhs_search" class="form-control"
                            placeholder="Cari NIM atau nama mahasiswa..." value="{{ request('mhs_search') }}">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        @if(request('mhs_search'))
                        <a href="{{ route('admin.kelas.show', $kelas->kelas_id) }}{{ request('dosen_search') ? '?dosen_search='.request('dosen_search') : '' }}"
                            class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:50px">#</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th class="text-center" style="width:70px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelasMahasiswas as $i => $km)
                        @php $mhs = $km->krsDetail->krs->mahasiswa ?? null; @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $kelasMahasiswas->firstItem() + $i }}</td>
                            <td class="font-monospace small fw-semibold">{{ $mhs->nim ?? '-' }}</td>
                            <td>
                                <div class="fw-medium">{{ $mhs->nama_mahasiswa ?? '(data tidak ditemukan)' }}</div>
                                <div class="text-muted small d-sm-none">{{ $mhs->nim ?? '' }}</div>
                            </td>
                            <td class="text-center">
                                <form
                                    action="{{ route('admin.kelas.destroy-mahasiswa', [$kelas->kelas_id, $km->kelas_mahasiswa_id]) }}"
                                    method="POST" onsubmit="return confirm('Hapus mahasiswa ini dari kelas?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-2"></i>
                                @if(request('mhs_search'))
                                Tidak ditemukan mahasiswa dengan kata kunci "<strong>{{ request('mhs_search')
                                    }}</strong>".
                                @else
                                Belum ada mahasiswa di kelas ini.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($kelasMahasiswas->hasPages())
            <div class="px-3 py-3 border-top bg-light">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Menampilkan
                        <strong>{{ $kelasMahasiswas->firstItem() }}</strong>–<strong>{{ $kelasMahasiswas->lastItem()
                            }}</strong>
                        dari <strong>{{ $kelasMahasiswas->total() }}</strong> mahasiswa
                    </small>
                    {{ $kelasMahasiswas->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== DOSEN TABLE ===== --}}
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div
                class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <span class="fw-semibold text-dark">
                    <i class="bi bi-person-workspace me-2 text-success"></i>Dosen Pengajar
                    <span class="badge bg-success bg-opacity-10 text-success ms-1">{{ $mengajars->total() }}</span>
                </span>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                </button>
            </div>

            {{-- Search bar --}}
            <div class="px-3 py-2 border-bottom bg-light">
                <form method="GET" action="{{ route('admin.kelas.show', $kelas->kelas_id) }}"
                    class="d-flex flex-wrap gap-2 align-items-center">
                    {{-- preserve mahasiswa search --}}
                    @if(request('mhs_search'))
                    <input type="hidden" name="mhs_search" value="{{ request('mhs_search') }}">
                    @endif
                    <div class="input-group input-group-sm flex-grow-1" style="min-width:200px;max-width:400px">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="dosen_search" class="form-control"
                            placeholder="Cari nama atau email dosen..." value="{{ request('dosen_search') }}">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        @if(request('dosen_search'))
                        <a href="{{ route('admin.kelas.show', $kelas->kelas_id) }}{{ request('mhs_search') ? '?mhs_search='.request('mhs_search') : '' }}"
                            class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:50px">#</th>
                            <th>Nama Dosen</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="text-center" style="width:70px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mengajars as $i => $m)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $mengajars->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-medium">{{ $m->dosen->nama_dosen ?? '(tidak ditemukan)' }}</div>
                                <div class="text-muted small d-md-none">{{ $m->dosen->alamat_email ?? '' }}</div>
                            </td>
                            <td class="d-none d-md-table-cell text-muted small">{{ $m->dosen->alamat_email ?? '-' }}
                            </td>
                            <td class="text-center">
                                <form
                                    action="{{ route('admin.kelas.destroy-dosen', [$kelas->kelas_id, $m->mengajar_id]) }}"
                                    method="POST" onsubmit="return confirm('Hapus dosen ini dari kelas?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-2"></i>
                                @if(request('dosen_search'))
                                Tidak ditemukan dosen dengan kata kunci "<strong>{{ request('dosen_search')
                                    }}</strong>".
                                @else
                                Belum ada dosen pengajar di kelas ini.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($mengajars->hasPages())
            <div class="px-3 py-3 border-top bg-light">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Menampilkan
                        <strong>{{ $mengajars->firstItem() }}</strong>–<strong>{{ $mengajars->lastItem() }}</strong>
                        dari <strong>{{ $mengajars->total() }}</strong> dosen
                    </small>
                    {{ $mengajars->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
{{-- ===== MODAL: Tambah Mahasiswa ===== --}}
<div class="modal fade" id="modalTambahMhs" tabindex="-1" aria-labelledby="modalTambahMhsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.kelas.store-mahasiswa', $kelas->kelas_id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahMhsLabel">
                        <i class="bi bi-person-plus-fill me-2"></i>Tambah Mahasiswa ke Kelas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($availableKrsDetails->isEmpty())
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Semua mahasiswa yang mengambil matakuliah ini sudah masuk kelas ini.
                    </p>
                    @else
                    <label class="form-label fw-semibold">Pilih Mahasiswa (KRS Detail)</label>
                    <select name="kode_krs_detail" class="form-select" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        @foreach($availableKrsDetails as $kd)
                        @php $mhs = $kd->krs->mahasiswa ?? null; @endphp
                        <option value="{{ $kd->kode_krs_detail }}">
                            {{ $mhs->nim ?? '?' }} — {{ $mhs->nama_mahasiswa ?? '(tidak ditemukan)' }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hanya mahasiswa yang mengambil matakuliah ini yang ditampilkan.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    @if($availableKrsDetails->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Tambahkan
                    </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL: Tambah Dosen ===== --}}
<div class="modal fade" id="modalTambahDosen" tabindex="-1" aria-labelledby="modalTambahDosenLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.kelas.store-dosen', $kelas->kelas_id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahDosenLabel">
                        <i class="bi bi-person-plus-fill me-2"></i>Tambah Dosen Pengajar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($availableDosens->isEmpty())
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Semua dosen aktif sudah ditugaskan di kelas ini.
                    </p>
                    @else
                    <label class="form-label fw-semibold">Pilih Dosen</label>
                    <select name="kode_dosen" class="form-select" required>
                        <option value="">-- Pilih Dosen --</option>
                        @foreach($availableDosens as $d)
                        <option value="{{ $d->kode_dosen }}">
                            {{ $d->nama_dosen }}@if($d->alamat_email) — {{ $d->alamat_email }}@endif
                        </option>
                        @endforeach
                    </select>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    @if($availableDosens->isNotEmpty())
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Tambahkan
                    </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
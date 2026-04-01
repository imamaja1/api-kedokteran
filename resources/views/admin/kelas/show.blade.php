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

<div class="row g-3">

    {{-- ===== MAHASISWA ===== --}}
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-mortarboard-fill me-2"></i>Mahasiswa
                    <span class="badge bg-light text-dark ms-1">{{ $kelas->kelasMahasiswa->count() }}</span>
                </span>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalTambahMhs">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px">#</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th class="text-center" style="width:70px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelas->kelasMahasiswa as $i => $km)
                        @php $mhs = $km->krsDetail->krs->mahasiswa ?? null; @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td class="small text-muted">{{ $mhs->nim ?? '-' }}</td>
                            <td>{{ $mhs->nama_mahasiswa ?? '(data tidak ditemukan)' }}</td>
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
                            <td colspan="4" class="text-center text-muted py-3">
                                <i class="bi bi-inbox me-1"></i>Belum ada mahasiswa.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== DOSEN ===== --}}
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-workspace me-2"></i>Dosen Pengajar
                    <span class="badge bg-light text-dark ms-1">{{ $kelas->mengajar->count() }}</span>
                </span>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px">#</th>
                            <th>Nama Dosen</th>
                            <th class="text-center" style="width:70px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelas->mengajar as $i => $m)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-medium">{{ $m->dosen->nama_dosen ?? '(tidak ditemukan)' }}</div>
                                <div class="text-muted small">{{ $m->dosen->alamat_email ?? '' }}</div>
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
                            <td colspan="3" class="text-center text-muted py-3">
                                <i class="bi bi-inbox me-1"></i>Belum ada dosen.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                            {{ $d->nama_dosen }}
                            @if($d->alamat_email) — {{ $d->alamat_email }} @endif
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
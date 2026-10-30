<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Matakuliah;
use App\Models\Mengajar;
use App\Models\NamaKelas;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ServiceKelas
{
    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveTahunAkademik(?int $kodeTahunAkademik): ?TahunAkademik
    {
        if ($kodeTahunAkademik) {
            return TahunAkademik::find($kodeTahunAkademik);
        }

        return TahunAkademik::active()->first();
    }

    private function encryptId($id): string
    {
        return Crypt::encryptString((string) $id);
    }

    /**
     * Query mahasiswa (krs_detail) yang mengambil matakuliah tertentu di TA tertentu
     * tetapi belum ditempatkan ke kelas manapun.
     */
    private function belumKelasQuery(int $idMatakuliah, int $kodeTahunAkademik)
    {
        return KrsDetail::with([
            'krs:kode_krs,nim,kode_tahun_akademik,semester',
            'krs.mahasiswa:nim,nama_mahasiswa',
        ])
            ->where('id_matakuliah', $idMatakuliah)
            ->whereHas('krs', function ($q) use ($kodeTahunAkademik) {
                $q->where('kode_tahun_akademik', $kodeTahunAkademik);
            })
            ->doesntHave('kelasMahasiswa');
    }

    private function formatMahasiswa($detail): array
    {
        return [
            'code_krs_detail' => $detail->toCode(),
            'kode_krs_detail' => $detail->kode_krs_detail,
            'nim' => $detail->krs?->nim,
            'nama_mahasiswa' => $detail->krs?->mahasiswa?->nama_mahasiswa,
            'semester' => $detail->krs?->semester,
        ];
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Konfigurasi matakuliah: daftar matakuliah yang diambil mahasiswa pada
     * suatu tahun akademik, apakah sudah punya kelas, dan berapa mahasiswa
     * yang belum masuk kelas.
     */
    public function matakuliahKonfigurasi(array $filters): JsonResponse
    {
        $ta = $this->resolveTahunAkademik($filters['kode_tahun_akademik'] ?? null);
        if (! $ta) {
            return ApiResponse::notFound('Tahun akademik tidak ditemukan.');
        }

        $prodi = $filters['kode_program_studi'] ?? null;
        $semester = $filters['semester'] ?? null;

        $krsQuery = Krs::where('kode_tahun_akademik', $ta->kode_tahun_akademik);
        if ($prodi) {
            $krsQuery->whereHas('mahasiswa', fn ($q) => $q->where('program_studi_kode', $prodi));
        }
        if ($semester) {
            $krsQuery->where('semester', $semester);
        }

        $krsIds = $krsQuery->pluck('kode_krs');
        if ($krsIds->isEmpty()) {
            return ApiResponse::success([], 'Belum ada KRS pada tahun akademik ini.');
        }

        $rows = KrsDetail::query()
            ->join('krs', 'krs_detail.kode_krs', '=', 'krs.kode_krs')
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->leftJoin('kelas_mahasiswa', 'kelas_mahasiswa.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
            ->whereIn('krs_detail.kode_krs', $krsIds)
            ->selectRaw('matakuliah.id_matakuliah, matakuliah.kode_matakuliah, matakuliah.nama_matakuliah, matakuliah.sks_teori, matakuliah.sks_praktik, MIN(krs.semester) as semester, COUNT(DISTINCT krs_detail.kode_krs_detail) as total_mahasiswa, COUNT(DISTINCT CASE WHEN kelas_mahasiswa.kelas_mahasiswa_id IS NULL THEN krs_detail.kode_krs_detail END) as belum_masuk_kelas')
            ->groupBy('matakuliah.id_matakuliah', 'matakuliah.kode_matakuliah', 'matakuliah.nama_matakuliah', 'matakuliah.sks_teori', 'matakuliah.sks_praktik')
            ->orderBy('matakuliah.kode_matakuliah')
            ->get();

        $kelasMap = Kelas::with([
            'namaKelas',
            'assessmentTemplate',
            'assessmentDosen' => fn ($q) => $q->withCount('assignments'),
        ])
            ->withCount(['kelasMahasiswa', 'mengajar'])
            ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
            ->whereIn('id_matakuliah', $rows->pluck('id_matakuliah'))
            ->get()
            ->groupBy('id_matakuliah');

        $data = $rows->map(function ($row) use ($kelasMap) {
            $kelas = $kelasMap->get($row->id_matakuliah, collect());

            return [
                'code_matakuliah' => $this->encryptId($row->id_matakuliah),
                'kode_matakuliah' => $row->kode_matakuliah,
                'nama_matakuliah' => $row->nama_matakuliah,
                'semester' => $row->semester,
                'sks_teori' => $row->sks_teori,
                'sks_praktik' => $row->sks_praktik,
                'total_mahasiswa' => (int) $row->total_mahasiswa,
                'belum_masuk_kelas' => (int) $row->belum_masuk_kelas,
                'has_kelas' => $kelas->isNotEmpty(),
                'kelas' => $kelas->map(fn ($k) => [
                    'code_kelas' => $k->toCode(),
                    'kelas_id' => $k->kelas_id,
                    'nama_kelas' => $k->namaKelas?->nama_kelas,
                    'jumlah_mahasiswa' => $k->kelas_mahasiswa_count,
                    'jumlah_dosen' => $k->mengajar_count,
                    'template_matakuliah' => (bool) ($k->assessmentTemplate !== null),
                    'template_kelas' => (bool) ($k->assessmentDosen !== null && $k->assessmentDosen->template_id !== null),
                    'template_assignments' => (bool) ($k->assessmentDosen !== null && $k->assessmentDosen->template_id !== null),
                    'setup_assignments' => (bool) ($k->assessmentDosen && $k->assessmentDosen->assignments_count > 0),
                ])->values(),
            ];
        })->values()->toArray();

        return ApiResponse::success($data, 'Konfigurasi kelas matakuliah berhasil diambil.');
    }

    /**
     * Daftar kelas dengan filter.
     */
    public function index(array $filters): JsonResponse
    {
        $query = Kelas::with([
            'namaKelas:nama_kelas_id,nama_kelas',
            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik',
            'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            'assessmentTemplate',
            'assessmentDosen' => fn ($q) => $q->withCount('assignments'),
        ])->withCount(['kelasMahasiswa', 'mengajar']);

        if (! empty($filters['kode_tahun_akademik'])) {
            $query->where('kode_tahun_akademik', $filters['kode_tahun_akademik']);
        }
        if (! empty($filters['kode_program_studi'])) {
            $query->where('kode_program_studi', $filters['kode_program_studi']);
        }
        if (! empty($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }
        if (! empty($filters['id_matakuliah'])) {
            $query->where('id_matakuliah', $filters['id_matakuliah']);
        }

        $data = $query->orderByDesc('kelas_id')->get()->map(fn ($k) => [
            'code_kelas' => $k->toCode(),
            'kelas_id' => $k->kelas_id,
            'nama_kelas' => $k->namaKelas?->nama_kelas,
            'semester' => $k->semester,
            'kode_program_studi' => $k->kode_program_studi,
            'tahun_akademik' => $k->tahunAkademik?->tahun_akademik,
            'code_matakuliah' => $this->encryptId($k->id_matakuliah),
            'kode_matakuliah' => $k->matakuliah?->kode_matakuliah,
            'nama_matakuliah' => $k->matakuliah?->nama_matakuliah,
            'jumlah_mahasiswa' => $k->kelas_mahasiswa_count,
            'jumlah_dosen' => $k->mengajar_count,
            'template_matakuliah' => (bool) ($k->assessmentTemplate !== null),
            'template_kelas' => (bool) ($k->assessmentDosen !== null && $k->assessmentDosen->template_id !== null),
            'template_assignments' => (bool) ($k->assessmentDosen !== null && $k->assessmentDosen->template_id !== null),
            'setup_assignments' => (bool) ($k->assessmentDosen && $k->assessmentDosen->assignments_count > 0),
        ])->values()->toArray();

        return ApiResponse::success($data, 'Daftar kelas berhasil diambil.');
    }

    /**
     * Buat kelas baru untuk suatu matakuliah.
     */
    public function store(array $data): JsonResponse
    {
        $matakuliah = Matakuliah::find($data['id_matakuliah']);
        if (! $matakuliah) {
            return ApiResponse::notFound('Matakuliah tidak ditemukan.');
        }

        $ta = $this->resolveTahunAkademik($data['kode_tahun_akademik'] ?? null);
        if (! $ta) {
            return ApiResponse::notFound('Tahun akademik tidak ditemukan.');
        }

        $namaKelasStr = strtoupper(trim($data['nama_kelas']));
        $namaKelas = NamaKelas::firstOrCreate(['nama_kelas' => $namaKelasStr]);

        $prodi = $data['kode_program_studi'] ?? $matakuliah->kode_program_studi;
        $semester = $data['semester'] ?? $ta->semester;

        $existing = Kelas::where('kode_tahun_akademik', $ta->kode_tahun_akademik)
            ->where('id_matakuliah', $matakuliah->id_matakuliah)
            ->where('nama_kelas_id', $namaKelas->nama_kelas_id)
            ->first();

        if ($existing) {
            return ApiResponse::error(
                "Kelas {$namaKelasStr} untuk matakuliah {$matakuliah->kode_matakuliah} pada tahun akademik {$ta->tahun_akademik} sudah ada.",
                422
            );
        }

        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => $semester,
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'kode_program_studi' => $prodi,
            'id_matakuliah' => $matakuliah->id_matakuliah,
        ]);

        return ApiResponse::success([
            'code_kelas' => $kelas->toCode(),
            'kelas_id' => $kelas->kelas_id,
            'nama_kelas' => $namaKelas->nama_kelas,
            'semester' => $kelas->semester,
            'kode_program_studi' => $kelas->kode_program_studi,
            'tahun_akademik' => $ta->tahun_akademik,
            'code_matakuliah' => $this->encryptId($matakuliah->id_matakuliah),
            'kode_matakuliah' => $matakuliah->kode_matakuliah,
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
        ], 'Kelas berhasil dibuat.', 201);
    }

    /**
     * Detail kelas: info + mahasiswa terdaftar + dosen pengampu + mahasiswa belum masuk kelas.
     */
    public function show(int $kelasId): JsonResponse
    {
        $kelas = Kelas::with([
            'namaKelas:nama_kelas_id,nama_kelas',
            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik',
            'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            'programStudi:kode_program_studi,nama_program_studi',
            'assessmentTemplate',
            'assessmentDosen' => fn ($q) => $q->withCount('assignments'),
        ])->find($kelasId);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $mahasiswa = KelasMahasiswa::with([
            'krsDetail:kode_krs_detail,kode_krs,id_matakuliah',
            'krsDetail.krs:kode_krs,nim,semester',
            'krsDetail.krs.mahasiswa:nim,nama_mahasiswa',
        ])
            ->where('kelas_id', $kelasId)
            ->get()
            ->map(fn ($km) => [
                'kelas_mahasiswa_id' => $km->kelas_mahasiswa_id,
                'kode_krs_detail' => $km->kode_krs_detail,
                'nim' => $km->krsDetail?->krs?->nim,
                'nama_mahasiswa' => $km->krsDetail?->krs?->mahasiswa?->nama_mahasiswa,
                'semester' => $km->krsDetail?->krs?->semester,
            ])->values()->toArray();

        $dosen = Mengajar::with(['dosen' => function ($q) {
            $q->withTrashed()->select('kode_dosen', 'nama_dosen', 'homebase', 'deleted_at');
        }])
            ->where('kelas_id', $kelasId)
            ->get()
            ->map(fn ($m) => [
                'mengajar_id' => $m->mengajar_id,
                'code_dosen' => $m->dosen?->toCode(),
                'kode_dosen' => $m->kode_dosen,
                'nama_dosen' => $m->dosen?->nama_dosen,
            ])->values()->toArray();

        $belumMasukKelas = $this->belumKelasQuery($kelas->id_matakuliah, $kelas->kode_tahun_akademik)
            ->get()
            ->map(fn ($detail) => $this->formatMahasiswa($detail))
            ->values()->toArray();

        return ApiResponse::success([
            'kelas' => [
                'code_kelas' => $kelas->toCode(),
                'kelas_id' => $kelas->kelas_id,
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas->semester,
                'kode_program_studi' => $kelas->kode_program_studi,
                'nama_program_studi' => $kelas->programStudi?->nama_program_studi,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
                'code_matakuliah' => $this->encryptId($kelas->id_matakuliah),
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
                'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah,
                'template_matakuliah' => (bool) ($kelas->assessmentTemplate !== null),
                'template_kelas' => (bool) ($kelas->assessmentDosen !== null && $kelas->assessmentDosen->template_id !== null),
                'template_assignments' => (bool) ($kelas->assessmentDosen !== null && $kelas->assessmentDosen->template_id !== null),
                'setup_assignments' => (bool) ($kelas->assessmentDosen && $kelas->assessmentDosen->assignments_count > 0),
            ],
            'mahasiswa' => $mahasiswa,
            'dosen' => $dosen,
            'belum_masuk_kelas' => $belumMasukKelas,
        ], 'Detail kelas berhasil diambil.');
    }

    /**
     * Daftar mahasiswa yang mengambil suatu matakuliah di TA tertentu
     * tetapi belum masuk kelas manapun.
     */
    public function mahasiswaBelumKelas(array $data): JsonResponse
    {
        $matakuliah = Matakuliah::find($data['id_matakuliah']);
        if (! $matakuliah) {
            return ApiResponse::notFound('Matakuliah tidak ditemukan.');
        }

        $ta = $this->resolveTahunAkademik($data['kode_tahun_akademik'] ?? null);
        if (! $ta) {
            return ApiResponse::notFound('Tahun akademik tidak ditemukan.');
        }

        $list = $this->belumKelasQuery($matakuliah->id_matakuliah, $ta->kode_tahun_akademik)
            ->get()
            ->map(fn ($detail) => $this->formatMahasiswa($detail))
            ->values()->toArray();

        return ApiResponse::success($list, 'Mahasiswa belum masuk kelas berhasil diambil.');
    }

    /**
     * Tambah dosen pengampu ke kelas.
     */
    public function storeDosen(array $data): JsonResponse
    {
        $kelas = Kelas::find($data['kelas_id']);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $dosen = Dosen::find($data['kode_dosen']);
        if (! $dosen) {
            return ApiResponse::notFound('Dosen tidak ditemukan.');
        }

        $exists = Mengajar::where('kelas_id', $kelas->kelas_id)
            ->where('kode_dosen', $dosen->kode_dosen)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Dosen sudah mengajar di kelas ini.', 422);
        }

        $mengajar = Mengajar::create([
            'kelas_id' => $kelas->kelas_id,
            'kode_dosen' => $dosen->kode_dosen,
        ]);

        return ApiResponse::success([
            'mengajar_id' => $mengajar->mengajar_id,
            'kelas_id' => $kelas->kelas_id,
            'code_dosen' => $dosen->toCode(),
            'kode_dosen' => $dosen->kode_dosen,
            'nama_dosen' => $dosen->nama_dosen,
        ], 'Dosen pengampu berhasil ditambahkan ke kelas.', 201);
    }

    /**
     * Hapus dosen pengampu dari kelas.
     */
    public function destroyDosen(int $mengajarId): JsonResponse
    {
        $mengajar = Mengajar::find($mengajarId);

        if (! $mengajar) {
            return ApiResponse::notFound('Data dosen pengampu tidak ditemukan.');
        }

        $mengajar->delete();

        return ApiResponse::success(null, 'Dosen pengampu berhasil dihapus dari kelas.');
    }

    /**
     * Hapus kelas.
     */
    public function destroy(int $kelasId): JsonResponse
    {
        $kelas = Kelas::find($kelasId);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        DB::beginTransaction();
        try {
            $kelas->kelasMahasiswa()->delete();
            $kelas->mengajar()->delete();
            $kelas->delete();
            DB::commit();

            return ApiResponse::success(null, 'Kelas berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal menghapus kelas.');
        }
    }
}

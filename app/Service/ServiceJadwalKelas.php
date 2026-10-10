<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Hari;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\JadwalKelas;
use App\Models\Ruangan;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;

class ServiceJadwalKelas
{
    /**
     * Resolve Tahun Akademik aktif atau dari ID yang diberikan.
     */
    public function resolveTahunAkademik(?int $kodeTahunAkademik): ?TahunAkademik
    {
        if ($kodeTahunAkademik) {
            return TahunAkademik::find($kodeTahunAkademik);
        }

        return TahunAkademik::active()->first();
    }

    /**
     * Format output objek JadwalKelas.
     */
    public function formatJadwal(JadwalKelas $jadwal): array
    {
        $kapasitasRuangan = $jadwal->ruangan?->kapasitas ?? 0;
        $jumlahMahasiswa = $jadwal->kelas?->kelasMahasiswa?->count() ?? 0;

        $hasCapacityWarning = $kapasitasRuangan > 0 && $jumlahMahasiswa > $kapasitasRuangan;

        return [
            'id' => $jadwal->id,
            'code' => $jadwal->toCode(),
            'code_jadwal' => $jadwal->toCode(),
            'kelas_id' => $jadwal->kelas_id,
            'code_kelas' => $jadwal->kelas?->toCode(),
            'nama_kelas' => $jadwal->kelas?->namaKelas?->nama_kelas,
            'semester' => $jadwal->kelas?->semester,
            'kode_tahun_akademik' => $jadwal->kode_tahun_akademik,
            'code_tahun_akademik' => $jadwal->tahunAkademik?->toCode(),
            'tahun_akademik' => $jadwal->tahunAkademik?->tahun_akademik,
            'ruangan_id' => $jadwal->ruangan_id,
            'code_ruangan' => $jadwal->ruangan?->toCode(),
            'hari_id' => $jadwal->hari_id,
            'code_hari' => $jadwal->hari?->toCode(),
            'matakuliah' => [
                'id_matakuliah' => $jadwal->kelas?->matakuliah?->id_matakuliah,
                'code_matakuliah' => $jadwal->kelas?->matakuliah?->toCode(),
                'kode_matakuliah' => $jadwal->kelas?->matakuliah?->kode_matakuliah,
                'nama_matakuliah' => $jadwal->kelas?->matakuliah?->nama_matakuliah,
                'sks' => $jadwal->kelas?->matakuliah?->sks_total ?? $jadwal->kelas?->matakuliah?->sks,
            ],
            'ruangan' => [
                'id' => $jadwal->ruangan?->id,
                'code' => $jadwal->ruangan?->toCode(),
                'code_ruangan' => $jadwal->ruangan?->toCode(),
                'kode_ruangan' => $jadwal->ruangan?->kode_ruangan,
                'nama_ruangan' => $jadwal->ruangan?->nama_ruangan,
                'kapasitas' => $jadwal->ruangan?->kapasitas,
                'jenis' => $jadwal->ruangan?->jenis,
                'lokasi' => $jadwal->ruangan?->lokasi,
            ],
            'hari' => [
                'id' => $jadwal->hari?->id,
                'code' => $jadwal->hari?->toCode(),
                'code_hari' => $jadwal->hari?->toCode(),
                'nama_hari' => $jadwal->hari?->nama_hari,
                'urutan' => $jadwal->hari?->urutan,
            ],
            'jam_mulai' => substr($jadwal->jam_mulai, 0, 5),
            'jam_selesai' => substr($jadwal->jam_selesai, 0, 5),
            'jam_mulai_full' => $jadwal->jam_mulai,
            'jam_selesai_full' => $jadwal->jam_selesai,
            'keterangan' => $jadwal->keterangan,
            'jumlah_mahasiswa' => $jumlahMahasiswa,
            'kapasitas_ruangan' => $kapasitasRuangan,
            'kapasitas_warning' => $hasCapacityWarning,
            'dosen' => $jadwal->kelas?->mengajar?->map(function ($m) {
                return [
                    'kode_dosen' => $m->dosen?->kode_dosen,
                    'code_dosen' => $m->dosen?->toCode(),
                    'nama_dosen' => $m->dosen?->nama_dosen,
                    'gelar' => $m->dosen?->gelar,
                ];
            })->values()->all() ?? [],
        ];
    }

    /**
     * Normalisasi format jam ke 'H:i:s'.
     */
    private function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        return $time;
    }

    /**
     * Cek bentrok jadwal ruangan pada hari dan periode akademik yang sama (Aturan B-01).
     * Irisan waktu: (jam_mulai < jam_selesai_baru) AND (jam_selesai > jam_mulai_baru).
     */
    public function findConflict(int $ruanganId, int $hariId, int $kodeTahunAkademik, string $jamMulai, string $jamSelesai, ?int $excludeId = null): ?JadwalKelas
    {
        $query = JadwalKelas::with(['kelas.matakuliah', 'kelas.namaKelas', 'ruangan', 'hari'])
            ->where('ruangan_id', $ruanganId)
            ->where('hari_id', $hariId)
            ->where('kode_tahun_akademik', $kodeTahunAkademik)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Ambil daftar semua jadwal dengan filter.
     */
    public function index(array $filters = []): JsonResponse
    {
        $ta = $this->resolveTahunAkademik($filters['kode_tahun_akademik'] ?? null);
        $kodeTahunAkademik = $ta?->kode_tahun_akademik;

        $query = JadwalKelas::with([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ]);

        if ($kodeTahunAkademik) {
            $query->where('kode_tahun_akademik', $kodeTahunAkademik);
        }

        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', (int) $filters['kelas_id']);
        }

        if (! empty($filters['ruangan_id'])) {
            $query->where('ruangan_id', (int) $filters['ruangan_id']);
        }

        if (! empty($filters['hari_id'])) {
            $query->where('hari_id', (int) $filters['hari_id']);
        }

        if (! empty($filters['semester'])) {
            $query->whereHas('kelas', fn ($q) => $q->where('semester', $filters['semester']));
        }

        if (! empty($filters['id_matakuliah'])) {
            $query->whereHas('kelas', fn ($q) => $q->where('id_matakuliah', $filters['id_matakuliah']));
        }

        // Urutkan berdasarkan hari (urutan) lalu jam mulai
        $query->join('hari', 'jadwal_kelas.hari_id', '=', 'hari.id')
            ->select('jadwal_kelas.*')
            ->orderBy('hari.urutan')
            ->orderBy('jadwal_kelas.jam_mulai');

        if (! empty($filters['per_page'])) {
            $paginator = $query->paginate((int) $filters['per_page']);
            $items = collect($paginator->items())->map(fn ($j) => $this->formatJadwal($j));

            return response()->json([
                'status' => true,
                'message' => 'Daftar jadwal kelas berhasil diambil.',
                'jumlah' => $paginator->total(),
                'data' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        $jadwalList = $query->get()->map(fn ($j) => $this->formatJadwal($j));

        return ApiResponse::success($jadwalList, 'Daftar jadwal kelas berhasil diambil.');
    }

    /**
     * Simpan jadwal kelas baru dengan validasi bentrok B-01, peringatan kapasitas B-03, jam B-04, aktif B-05.
     */
    public function store(array $data): JsonResponse
    {
        $jamMulai = $this->normalizeTime($data['jam_mulai']);
        $jamSelesai = $this->normalizeTime($data['jam_selesai']);

        // Aturan B-04: jam selesai harus setelah jam mulai
        if (strtotime($jamSelesai) <= strtotime($jamMulai)) {
            return ApiResponse::validation([
                'jam_selesai' => ["Jam selesai ({$jamSelesai}) harus lebih besar dari jam mulai ({$jamMulai})."],
            ], 'Validasi waktu gagal.');
        }

        // Cek kelas
        $kelas = Kelas::with(['matakuliah', 'namaKelas', 'kelasMahasiswa'])->find($data['kelas_id']);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas kuliah tidak ditemukan.');
        }

        // Resolusi tahun akademik
        $kodeTahunAkademik = $data['kode_tahun_akademik'] ?? $kelas->kode_tahun_akademik;
        $ta = $this->resolveTahunAkademik($kodeTahunAkademik);
        if (! $ta) {
            return ApiResponse::error('Tahun akademik aktif tidak ditemukan.', 422);
        }
        $kodeTahunAkademik = $ta->kode_tahun_akademik;

        // Aturan B-05: Cek Ruangan aktif
        $ruangan = Ruangan::find($data['ruangan_id']);
        if (! $ruangan || ! $ruangan->status_aktif) {
            return ApiResponse::validation([
                'ruangan_id' => ['Ruangan sedang tidak aktif atau tidak ditemukan.'],
            ], 'Ruangan tidak valid.');
        }

        // Aturan B-05: Cek Hari aktif
        $hari = Hari::find($data['hari_id']);
        if (! $hari || ! $hari->status_aktif) {
            return ApiResponse::validation([
                'hari_id' => ['Hari sedang tidak aktif atau tidak ditemukan.'],
            ], 'Hari tidak valid.');
        }

        // Aturan B-01: Pengecekan bentrok ruangan
        $conflict = $this->findConflict($ruangan->id, $hari->id, $kodeTahunAkademik, $jamMulai, $jamSelesai);
        if ($conflict) {
            $conflictingClassName = $conflict->kelas?->namaKelas?->nama_kelas ?? 'Kelas';
            $conflictingCourseName = $conflict->kelas?->matakuliah?->nama_matakuliah ?? 'Matakuliah';
            $conflictTime = substr($conflict->jam_mulai, 0, 5) . ' - ' . substr($conflict->jam_selesai, 0, 5);

            return response()->json([
                'status' => false,
                'message' => "Jadwal bentrok dengan {$conflictingClassName} [{$conflictingCourseName}] pada ruangan {$ruangan->nama_ruangan} ({$conflictTime}).",
                'error' => 'ROOM_SCHEDULE_CONFLICT',
                'conflict' => [
                    'id' => $conflict->id,
                    'code' => $conflict->toCode(),
                    'code_jadwal' => $conflict->toCode(),
                    'kelas_id' => $conflict->kelas_id,
                    'code_kelas' => $conflict->kelas?->toCode(),
                    'nama_kelas' => $conflictingClassName,
                    'nama_matakuliah' => $conflictingCourseName,
                    'kode_matakuliah' => $conflict->kelas?->matakuliah?->kode_matakuliah,
                    'code_matakuliah' => $conflict->kelas?->matakuliah?->toCode(),
                    'ruangan' => $ruangan->nama_ruangan,
                    'code_ruangan' => $ruangan->toCode(),
                    'hari' => $hari->nama_hari,
                    'code_hari' => $hari->toCode(),
                    'jam_mulai' => substr($conflict->jam_mulai, 0, 5),
                    'jam_selesai' => substr($conflict->jam_selesai, 0, 5),
                ],
            ], 409);
        }

        // Simpan jadwal
        $jadwal = JadwalKelas::create([
            'kelas_id' => $kelas->kelas_id,
            'ruangan_id' => $ruangan->id,
            'hari_id' => $hari->id,
            'kode_tahun_akademik' => $kodeTahunAkademik,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        $jadwal->load([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ]);

        // Aturan B-03: Cek Kapasitas Ruangan (Peringatan / Warning)
        $warnings = [];
        $jumlahMahasiswa = $kelas->kelasMahasiswa->count();
        if ($ruangan->kapasitas < $jumlahMahasiswa) {
            $warnings[] = "Kapasitas ruangan {$ruangan->nama_ruangan} ({$ruangan->kapasitas} orang) lebih kecil dari jumlah mahasiswa kelas ({$jumlahMahasiswa} orang).";
        }

        $formatted = $this->formatJadwal($jadwal);

        $responsePayload = [
            'status' => true,
            'message' => 'Jadwal kelas berhasil dibuat.' . (! empty($warnings) ? ' (Dengan peringatan)' : ''),
            'data' => $formatted,
        ];

        if (! empty($warnings)) {
            $responsePayload['warnings'] = $warnings;
        }

        return response()->json($responsePayload, 201);
    }

    /**
     * Tampilkan detail satu jadwal kelas.
     */
    public function show(int $id): JsonResponse
    {
        $jadwal = JadwalKelas::with([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ])->find($id);

        if (! $jadwal) {
            return ApiResponse::notFound('Jadwal kelas tidak ditemukan.');
        }

        return ApiResponse::success($this->formatJadwal($jadwal), 'Detail jadwal kelas berhasil diambil.');
    }

    /**
     * Update jadwal kelas.
     */
    public function update(int $id, array $data): JsonResponse
    {
        $jadwal = JadwalKelas::with(['kelas.kelasMahasiswa'])->find($id);
        if (! $jadwal) {
            return ApiResponse::notFound('Jadwal kelas tidak ditemukan.');
        }

        $ruanganId = $data['ruangan_id'] ?? $jadwal->ruangan_id;
        $hariId = $data['hari_id'] ?? $jadwal->hari_id;
        $kodeTahunAkademik = $data['kode_tahun_akademik'] ?? $jadwal->kode_tahun_akademik;
        $jamMulai = isset($data['jam_mulai']) ? $this->normalizeTime($data['jam_mulai']) : $jadwal->jam_mulai;
        $jamSelesai = isset($data['jam_selesai']) ? $this->normalizeTime($data['jam_selesai']) : $jadwal->jam_selesai;

        // Aturan B-04
        if (strtotime($jamSelesai) <= strtotime($jamMulai)) {
            return ApiResponse::validation([
                'jam_selesai' => ["Jam selesai ({$jamSelesai}) harus lebih besar dari jam mulai ({$jamMulai})."],
            ], 'Validasi waktu gagal.');
        }

        // Aturan B-05: Cek Ruangan aktif jika berubah
        $ruangan = Ruangan::find($ruanganId);
        if (! $ruangan || ! $ruangan->status_aktif) {
            return ApiResponse::validation([
                'ruangan_id' => ['Ruangan sedang tidak aktif atau tidak ditemukan.'],
            ], 'Ruangan tidak valid.');
        }

        // Aturan B-05: Cek Hari aktif jika berubah
        $hari = Hari::find($hariId);
        if (! $hari || ! $hari->status_aktif) {
            return ApiResponse::validation([
                'hari_id' => ['Hari sedang tidak aktif atau tidak ditemukan.'],
            ], 'Hari tidak valid.');
        }

        // Aturan B-01: Pengecekan bentrok ruangan
        $conflict = $this->findConflict($ruanganId, $hariId, $kodeTahunAkademik, $jamMulai, $jamSelesai, $jadwal->id);
        if ($conflict) {
            $conflictingClassName = $conflict->kelas?->namaKelas?->nama_kelas ?? 'Kelas';
            $conflictingCourseName = $conflict->kelas?->matakuliah?->nama_matakuliah ?? 'Matakuliah';
            $conflictTime = substr($conflict->jam_mulai, 0, 5) . ' - ' . substr($conflict->jam_selesai, 0, 5);

            return response()->json([
                'status' => false,
                'message' => "Jadwal bentrok dengan {$conflictingClassName} [{$conflictingCourseName}] pada ruangan {$ruangan->nama_ruangan} ({$conflictTime}).",
                'error' => 'ROOM_SCHEDULE_CONFLICT',
                'conflict' => [
                    'id' => $conflict->id,
                    'code' => $conflict->toCode(),
                    'code_jadwal' => $conflict->toCode(),
                    'kelas_id' => $conflict->kelas_id,
                    'code_kelas' => $conflict->kelas?->toCode(),
                    'nama_kelas' => $conflictingClassName,
                    'nama_matakuliah' => $conflictingCourseName,
                    'kode_matakuliah' => $conflict->kelas?->matakuliah?->kode_matakuliah,
                    'code_matakuliah' => $conflict->kelas?->matakuliah?->toCode(),
                    'ruangan' => $ruangan->nama_ruangan,
                    'code_ruangan' => $ruangan->toCode(),
                    'hari' => $hari->nama_hari,
                    'code_hari' => $hari->toCode(),
                    'jam_mulai' => substr($conflict->jam_mulai, 0, 5),
                    'jam_selesai' => substr($conflict->jam_selesai, 0, 5),
                ],
            ], 409);
        }

        $jadwal->update([
            'ruangan_id' => $ruanganId,
            'hari_id' => $hariId,
            'kode_tahun_akademik' => $kodeTahunAkademik,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'keterangan' => array_key_exists('keterangan', $data) ? $data['keterangan'] : $jadwal->keterangan,
        ]);

        $jadwal->load([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ]);

        // Aturan B-03: Cek Kapasitas Ruangan
        $warnings = [];
        $jumlahMahasiswa = $jadwal->kelas?->kelasMahasiswa?->count() ?? 0;
        if ($ruangan->kapasitas < $jumlahMahasiswa) {
            $warnings[] = "Kapasitas ruangan {$ruangan->nama_ruangan} ({$ruangan->kapasitas} orang) lebih kecil dari jumlah mahasiswa kelas ({$jumlahMahasiswa} orang).";
        }

        $responsePayload = [
            'status' => true,
            'message' => 'Jadwal kelas berhasil diperbarui.' . (! empty($warnings) ? ' (Dengan peringatan)' : ''),
            'data' => $this->formatJadwal($jadwal),
        ];

        if (! empty($warnings)) {
            $responsePayload['warnings'] = $warnings;
        }

        return response()->json($responsePayload, 200);
    }

    /**
     * Hapus jadwal kelas.
     */
    public function destroy(int $id): JsonResponse
    {
        $jadwal = JadwalKelas::find($id);
        if (! $jadwal) {
            return ApiResponse::notFound('Jadwal kelas tidak ditemukan.');
        }

        $jadwal->delete();
        return ApiResponse::success(null, 'Jadwal kelas berhasil dihapus.');
    }

    /**
     * Salin jadwal ke kelas lain atau hari lain (F-14).
     */
    public function copy(int $id, array $target): JsonResponse
    {
        $source = JadwalKelas::find($id);
        if (! $source) {
            return ApiResponse::notFound('Jadwal sumber tidak ditemukan.');
        }

        $targetPayload = [
            'kelas_id' => $target['kelas_id'] ?? $source->kelas_id,
            'ruangan_id' => $target['ruangan_id'] ?? $source->ruangan_id,
            'hari_id' => $target['hari_id'] ?? $source->hari_id,
            'kode_tahun_akademik' => $target['kode_tahun_akademik'] ?? $source->kode_tahun_akademik,
            'jam_mulai' => $target['jam_mulai'] ?? $source->jam_mulai,
            'jam_selesai' => $target['jam_selesai'] ?? $source->jam_selesai,
            'keterangan' => $target['keterangan'] ?? $source->keterangan,
        ];

        return $this->store($targetPayload);
    }

    /**
     * T-01: Endpoint jadwal per kelas kuliah, dikelompokkan per hari dan diurutkan menurut jam.
     */
    public function getByKelas(int $kelasId): JsonResponse
    {
        $kelas = Kelas::with(['matakuliah', 'namaKelas', 'kelasMahasiswa', 'mengajar.dosen'])->find($kelasId);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas kuliah tidak ditemukan.');
        }

        $schedules = JadwalKelas::with(['ruangan', 'hari', 'tahunAkademik'])
            ->where('kelas_id', $kelasId)
            ->join('hari', 'jadwal_kelas.hari_id', '=', 'hari.id')
            ->select('jadwal_kelas.*')
            ->orderBy('hari.urutan')
            ->orderBy('jadwal_kelas.jam_mulai')
            ->get();

        $jumlahMahasiswa = $kelas->kelasMahasiswa->count();
        $formattedSchedules = $schedules->map(function ($j) use ($kelas, $jumlahMahasiswa) {
            $j->setRelation('kelas', $kelas);
            return $this->formatJadwal($j);
        });

        // Kelompokkan per hari
        $groupedByHari = $formattedSchedules->groupBy(fn ($item) => $item['hari']['nama_hari']);

        return ApiResponse::success([
            'kelas' => [
                'kelas_id' => $kelas->kelas_id,
                'code_kelas' => $kelas->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas->semester,
                'kode_program_studi' => $kelas->kode_program_studi,
                'matakuliah' => [
                    'id_matakuliah' => $kelas->matakuliah?->id_matakuliah,
                    'code_matakuliah' => $kelas->matakuliah?->toCode(),
                    'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
                    'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah,
                    'sks' => $kelas->matakuliah?->sks_total ?? $kelas->matakuliah?->sks,
                ],
                'jumlah_mahasiswa' => $jumlahMahasiswa,
                'dosen' => $kelas->mengajar->map(fn ($m) => [
                    'kode_dosen' => $m->dosen?->kode_dosen,
                    'code_dosen' => $m->dosen?->toCode(),
                    'nama_dosen' => $m->dosen?->nama_dosen,
                    'gelar' => $m->dosen?->gelar,
                ])->values()->all(),
            ],
            'total_jadwal' => $schedules->count(),
            'jadwal_list' => $formattedSchedules->values()->all(),
            'jadwal_per_hari' => $groupedByHari,
        ], 'Jadwal per kelas berhasil diambil.');
    }

    /**
     * T-02: Endpoint jadwal per ruangan, untuk mengetahui penggunaan dan kekosongan ruangan.
     */
    public function getByRuangan(int $ruanganId, array $filters = []): JsonResponse
    {
        $ruangan = Ruangan::find($ruanganId);
        if (! $ruangan) {
            return ApiResponse::notFound('Ruangan tidak ditemukan.');
        }

        $ta = $this->resolveTahunAkademik($filters['kode_tahun_akademik'] ?? null);
        $kodeTahunAkademik = $ta?->kode_tahun_akademik;

        $query = JadwalKelas::with([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ])
            ->where('ruangan_id', $ruanganId);

        if ($kodeTahunAkademik) {
            $query->where('kode_tahun_akademik', $kodeTahunAkademik);
        }

        if (! empty($filters['hari_id'])) {
            $query->where('hari_id', (int) $filters['hari_id']);
        }

        $query->join('hari', 'jadwal_kelas.hari_id', '=', 'hari.id')
            ->select('jadwal_kelas.*')
            ->orderBy('hari.urutan')
            ->orderBy('jadwal_kelas.jam_mulai');

        $schedules = $query->get()->map(fn ($j) => $this->formatJadwal($j));

        return ApiResponse::success([
            'ruangan' => (new ServiceRuangan)->formatRuangan($ruangan),
            'tahun_akademik' => $ta?->tahun_akademik,
            'total_jadwal' => $schedules->count(),
            'jadwal_list' => $schedules->values()->all(),
            'jadwal_per_hari' => $schedules->groupBy(fn ($item) => $item['hari']['nama_hari']),
        ], 'Jadwal ruangan berhasil diambil.');
    }

    /**
     * T-03: Endpoint jadwal per hari.
     */
    public function getByHari(int $hariId, array $filters = []): JsonResponse
    {
        $hari = Hari::find($hariId);
        if (! $hari) {
            return ApiResponse::notFound('Hari tidak ditemukan.');
        }

        $ta = $this->resolveTahunAkademik($filters['kode_tahun_akademik'] ?? null);
        $kodeTahunAkademik = $ta?->kode_tahun_akademik;

        $query = JadwalKelas::with([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ])
            ->where('hari_id', $hariId);

        if ($kodeTahunAkademik) {
            $query->where('kode_tahun_akademik', $kodeTahunAkademik);
        }

        if (! empty($filters['ruangan_id'])) {
            $query->where('ruangan_id', (int) $filters['ruangan_id']);
        }

        $query->orderBy('jam_mulai');

        $schedules = $query->get()->map(fn ($j) => $this->formatJadwal($j));

        return ApiResponse::success([
            'hari' => (new ServiceHari)->formatHari($hari),
            'tahun_akademik' => $ta?->tahun_akademik,
            'total_jadwal' => $schedules->count(),
            'jadwal_list' => $schedules->values()->all(),
        ], 'Jadwal hari berhasil diambil.');
    }

    /**
     * F-13 & T-05: Status kelas kuliah (terjadwal vs belum terjadwal).
     */
    public function statusKelas(array $filters = []): JsonResponse
    {
        $ta = $this->resolveTahunAkademik($filters['kode_tahun_akademik'] ?? null);
        $kodeTahunAkademik = $ta?->kode_tahun_akademik;

        $query = Kelas::with([
            'namaKelas',
            'matakuliah',
            'kelasMahasiswa',
            'mengajar.dosen',
            'jadwalKelas.ruangan',
            'jadwalKelas.hari',
            'jadwalKelas.tahunAkademik',
        ]);

        if ($kodeTahunAkademik) {
            $query->where('kode_tahun_akademik', $kodeTahunAkademik);
        }

        if (! empty($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (! empty($filters['kode_program_studi'])) {
            $query->where('kode_program_studi', $filters['kode_program_studi']);
        }

        $statusFilter = $filters['status'] ?? null; // 'belum_terjadwal' | 'sudah_terjadwal'
        if ($statusFilter === 'belum_terjadwal') {
            $query->doesntHave('jadwalKelas');
        } elseif ($statusFilter === 'sudah_terjadwal') {
            $query->has('jadwalKelas');
        }

        $kelasList = $query->get()->map(function ($kelas) {
            $jumlahMahasiswa = $kelas->kelasMahasiswa->count();
            $jumlahDosen = $kelas->mengajar->count();
            $schedules = $kelas->jadwalKelas;

            $hasWarning = false;
            $warnings = [];
            foreach ($schedules as $j) {
                if ($j->ruangan && $j->ruangan->kapasitas < $jumlahMahasiswa) {
                    $hasWarning = true;
                    $warnings[] = "Kapasitas ruangan {$j->ruangan->nama_ruangan} ({$j->ruangan->kapasitas}) < Mahasiswa ({$jumlahMahasiswa}).";
                }
            }

            return [
                'kelas_id' => $kelas->kelas_id,
                'code_kelas' => $kelas->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas->semester,
                'kode_program_studi' => $kelas->kode_program_studi,
                'matakuliah' => [
                    'id_matakuliah' => $kelas->matakuliah?->id_matakuliah,
                    'code_matakuliah' => $kelas->matakuliah?->toCode(),
                    'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
                    'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah,
                    'sks' => $kelas->matakuliah?->sks_total ?? $kelas->matakuliah?->sks,
                ],
                'jumlah_mahasiswa' => $jumlahMahasiswa,
                'jumlah_dosen' => $jumlahDosen,
                'status_jadwal' => $schedules->isEmpty() ? 'belum_terjadwal' : 'sudah_terjadwal',
                'total_jadwal' => $schedules->count(),
                'kapasitas_warning' => $hasWarning,
                'warnings' => $warnings,
                'jadwal' => $schedules->map(fn ($j) => [
                    'id' => $j->id,
                    'code' => $j->toCode(),
                    'code_jadwal' => $j->toCode(),
                    'hari' => $j->hari?->nama_hari,
                    'code_hari' => $j->hari?->toCode(),
                    'ruangan' => $j->ruangan?->nama_ruangan,
                    'code_ruangan' => $j->ruangan?->toCode(),
                    'kapasitas_ruangan' => $j->ruangan?->kapasitas,
                    'jam_mulai' => substr($j->jam_mulai, 0, 5),
                    'jam_selesai' => substr($j->jam_selesai, 0, 5),
                ])->values()->all(),
            ];
        });

        $totalBelum = $kelasList->where('status_jadwal', 'belum_terjadwal')->count();
        $totalSudah = $kelasList->where('status_jadwal', 'sudah_terjadwal')->count();

        return ApiResponse::success([
            'tahun_akademik' => $ta?->tahun_akademik,
            'summary' => [
                'total_kelas' => $kelasList->count(),
                'sudah_terjadwal' => $totalSudah,
                'belum_terjadwal' => $totalBelum,
            ],
            'data' => $kelasList->values()->all(),
        ], 'Status penjadwalan kelas berhasil diambil.');
    }

    /**
     * T-06: Endpoint jadwal untuk mahasiswa yang sedang login.
     */
    public function getJadwalMahasiswa(string $nim, ?int $kodeTahunAkademik = null): JsonResponse
    {
        $ta = $this->resolveTahunAkademik($kodeTahunAkademik);
        $kodeTa = $ta?->kode_tahun_akademik;

        // Ambil kelas_id yang diikuti oleh mahasiswa ini pada tahun akademik tertentu
        $kelasIds = KelasMahasiswa::whereHas('krsDetail.krs', function ($q) use ($nim, $kodeTa) {
            $q->where('nim', $nim);
            if ($kodeTa) {
                $q->where('kode_tahun_akademik', $kodeTa);
            }
        })->pluck('kelas_id');

        if ($kelasIds->isEmpty()) {
            return ApiResponse::success([
                'tahun_akademik' => $ta?->tahun_akademik,
                'total_jadwal' => 0,
                'jadwal_list' => [],
                'jadwal_per_hari' => [],
            ], 'Mahasiswa belum terdaftar pada kelas manapun di periode ini.');
        }

        $schedules = JadwalKelas::with([
            'kelas.namaKelas',
            'kelas.matakuliah',
            'kelas.kelasMahasiswa',
            'kelas.mengajar.dosen',
            'ruangan',
            'hari',
            'tahunAkademik',
        ])
            ->whereIn('kelas_id', $kelasIds)
            ->join('hari', 'jadwal_kelas.hari_id', '=', 'hari.id')
            ->select('jadwal_kelas.*')
            ->orderBy('hari.urutan')
            ->orderBy('jadwal_kelas.jam_mulai')
            ->get()
            ->map(fn ($j) => $this->formatJadwal($j));

        $groupedByHari = $schedules->groupBy(fn ($item) => $item['hari']['nama_hari']);

        return ApiResponse::success([
            'tahun_akademik' => $ta?->tahun_akademik,
            'total_jadwal' => $schedules->count(),
            'jadwal_list' => $schedules->values()->all(),
            'jadwal_per_hari' => $groupedByHari,
        ], 'Jadwal kuliah mahasiswa berhasil diambil.');
    }
}

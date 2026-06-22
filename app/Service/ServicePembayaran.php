<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\PerwalianKrsValidasi;
use App\Models\SksRule;
use App\Models\TahunAkademik;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ServicePembayaran
{
    public function index(array $filters = []): JsonResponse
    {
        $query = Pembayaran::with(['mahasiswa:nim,nama_mahasiswa', 'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester']);

        if (! empty($filters['nim'])) {
            $query->where('nim', $filters['nim']);
        }

        if (! empty($filters['kode_tahun_akademik'])) {
            $query->where('kode_tahun_akademik', $filters['kode_tahun_akademik']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $paginator = $query->orderByDesc('id')->paginate(20);

        $paginator->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->toCode(),
                'nim' => $item->nim,
                'nama_mahasiswa' => $item->mahasiswa?->nama_mahasiswa,
                'kode_tahun_akademik' => $item->kode_tahun_akademik,
                'tahun_akademik' => $item->tahunAkademik?->tahun_akademik,
                'semester' => $item->tahunAkademik?->semester,
                'status' => $item->status,
                'status_mahasiswa' => $item->status_mahasiswa,
                'tanggal_bayar' => $item->tanggal_bayar?->format('Y-m-d'),
                'keterangan' => $item->keterangan,
                'sks_override' => $item->sks_override,
                'sks_override_reason' => $item->sks_override_reason,
                'sks_override_by' => $item->overrideBy?->name,
                'sks_override_at' => $item->sks_override_at?->toIso8601String(),
            ];
        });

        return ApiResponse::paginated($paginator, 'Data pembayaran berhasil diambil.');
    }

    public function show(string $code): JsonResponse
    {
        try {
            $id = Crypt::decryptString($code);
        } catch (DecryptException) {
            return ApiResponse::error('Kode tidak valid.', 422);
        }

        $item = Pembayaran::with(['mahasiswa:nim,nama_mahasiswa', 'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester'])
            ->find($id);

        if (! $item) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        return ApiResponse::success([
            'id' => $item->id,
            'code' => $item->toCode(),
            'nim' => $item->nim,
            'nama_mahasiswa' => $item->mahasiswa?->nama_mahasiswa,
            'kode_tahun_akademik' => $item->kode_tahun_akademik,
            'tahun_akademik' => $item->tahunAkademik?->tahun_akademik,
            'semester' => $item->tahunAkademik?->semester,
            'status' => $item->status,
            'status_mahasiswa' => $item->status_mahasiswa,
            'tanggal_bayar' => $item->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $item->keterangan,
            'sks_override' => $item->sks_override,
            'sks_override_reason' => $item->sks_override_reason,
            'sks_override_by' => $item->overrideBy?->name,
            'sks_override_at' => $item->sks_override_at?->toIso8601String(),
        ], 'Detail pembayaran berhasil diambil.');
    }

    public function store(array $data): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $data['nim'])->first();
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan.');
        }

        $ta = TahunAkademik::find($data['kode_tahun_akademik']);
        if (! $ta) {
            return ApiResponse::notFound('Tahun akademik tidak ditemukan.');
        }

        $existing = Pembayaran::where('nim', $data['nim'])
            ->where('kode_tahun_akademik', $data['kode_tahun_akademik'])
            ->first();

        if ($existing) {
            return ApiResponse::error('Pembayaran untuk mahasiswa ini di tahun akademik ini sudah ada.', 422);
        }

        // Hitung semester mahasiswa
        $serviceSemester = new ServiceSemester();
        $semester = $serviceSemester->hitung($data['nim'], $ta);

        // Hitung SKS limit dari rules
        $sksLimit = $this->getSksLimitFromRule($data['nim'], $mahasiswa->program_studi_kode, $semester);

        $pembayaran = Pembayaran::create([
            'nim' => $data['nim'],
            'kode_tahun_akademik' => $data['kode_tahun_akademik'],
            'status' => $data['status'] ?? 'belum',
            'status_mahasiswa' => $data['status_mahasiswa'] ?? 'aktif',
            'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
            'keterangan' => $data['keterangan'] ?? $this->generateKeterangan($data['nim'], $ta),
            'sks_override' => $sksLimit,
        ]);

        // Auto-create KRS + PerwalianKrsValidasi jika belum ada
        $this->autoCreateKrs($data['nim'], $ta);

        return ApiResponse::success([
            'id' => $pembayaran->id,
            'code' => $pembayaran->toCode(),
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'status' => $pembayaran->status,
            'status_mahasiswa' => $pembayaran->status_mahasiswa,
            'tanggal_bayar' => $pembayaran->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $pembayaran->keterangan,
            'sks_override' => $pembayaran->sks_override,
        ], 'Pembayaran berhasil dibuat.', 201);
    }

    public function update(array $data): JsonResponse
    {
        try {
            $id = Crypt::decryptString($data['code']);
        } catch (DecryptException) {
            return ApiResponse::error('Kode tidak valid.', 422);
        }

        $pembayaran = Pembayaran::with('mahasiswa:nim,program_studi_kode')->find($id);
        if (! $pembayaran) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        $oldStatus = $pembayaran->status;

        $ta = TahunAkademik::find($pembayaran->kode_tahun_akademik);

        // Hitung SKS limit jika status_mahasiswa berubah atau pertama kali
        $sksLimit = $pembayaran->sks_override;
        $newStatusMahasiswa = $data['status_mahasiswa'] ?? $pembayaran->status_mahasiswa;

        if ($newStatusMahasiswa !== $pembayaran->status_mahasiswa || $pembayaran->sks_override === null) {
            $serviceSemester = new ServiceSemester();
            $semester = $serviceSemester->hitung($pembayaran->nim, $ta);
            $sksLimit = $this->getSksLimitFromRule($pembayaran->nim, $pembayaran->mahasiswa?->program_studi_kode, $semester);
        }

        $pembayaran->update([
            'status' => $data['status'] ?? $pembayaran->status,
            'status_mahasiswa' => $newStatusMahasiswa,
            'tanggal_bayar' => $data['tanggal_bayar'] ?? $pembayaran->tanggal_bayar,
            'keterangan' => $data['keterangan'] ?? $pembayaran->keterangan ?? ($ta ? $this->generateKeterangan($pembayaran->nim, $ta) : $pembayaran->keterangan),
            'sks_override' => $sksLimit,
        ]);

        // Jika status berubah dari belum → lunas dan KRS belum ada, auto-create
        if ($oldStatus === 'belum' && $pembayaran->status === 'lunas' && $ta) {
            $this->autoCreateKrs($pembayaran->nim, $ta);
        }

        return ApiResponse::success([
            'id' => $pembayaran->id,
            'code' => $pembayaran->toCode(),
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'status' => $pembayaran->status,
            'status_mahasiswa' => $pembayaran->status_mahasiswa,
            'tanggal_bayar' => $pembayaran->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $pembayaran->keterangan,
            'sks_override' => $pembayaran->sks_override,
        ], 'Pembayaran berhasil diperbarui.');
    }

    public function getSksLimit(array $data): JsonResponse
    {
        $pembayaran = Pembayaran::where('nim', $data['nim'])
            ->where('kode_tahun_akademik', $data['kode_tahun_akademik'])
            ->where('status', 'lunas')
            ->first();

        if (! $pembayaran) {
            return ApiResponse::error('Pembayaran lunas tidak ditemukan untuk mahasiswa ini.', 404);
        }

        $limit = $pembayaran->sks_override ?? 24;

        return ApiResponse::success([
            'nim' => $pembayaran->nim,
            'nama_mahasiswa' => $pembayaran->mahasiswa?->nama_mahasiswa,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'sks_limit' => $limit,
            'sks_override' => $pembayaran->sks_override ? true : false,
            'sks_override_reason' => $pembayaran->sks_override_reason,
            'sks_override_by' => $pembayaran->overrideBy?->name,
        ], 'Info SKS limit berhasil diambil.');
    }

    public function setSksOverride(array $data, int $staffId): JsonResponse
    {
        $pembayaran = Pembayaran::where('nim', $data['nim'])
            ->where('kode_tahun_akademik', $data['kode_tahun_akademik'])
            ->first();

        if (! $pembayaran) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        if ($pembayaran->status !== 'lunas') {
            return ApiResponse::error('SKS override hanya bisa diatur untuk pembayaran yang sudah lunas.', 422);
        }

        $oldLimit = $pembayaran->sks_override ?? 24;

        $pembayaran->update([
            'sks_override' => $data['sks_override'],
            'sks_override_reason' => $data['sks_override_reason'],
            'sks_override_by' => $staffId,
            'sks_override_at' => now(),
        ]);

        try {
            ActivityLog::create([
                'guard' => 'staff_web',
                'user_id' => (string) $staffId,
                'user_type' => 'staff',
                'method' => 'PUT',
                'path' => 'api/staff/pembayaran/sks-override',
                'status_code' => 200,
                'description' => "Set SKS override {$oldLimit}→{$data['sks_override']} untuk NIM {$data['nim']}, alasan: {$data['sks_override_reason']}",
            ]);
        } catch (\Throwable) {
            // Logging failure should not block the response
        }

        return ApiResponse::success([
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'sks_override' => $pembayaran->sks_override,
            'sks_override_reason' => $pembayaran->sks_override_reason,
            'sks_override_at' => $pembayaran->sks_override_at?->toIso8601String(),
        ], 'SKS override berhasil diatur.');
    }

    // ─── Private Methods ────────────────────────────────────────────────

    /**
     * Auto-generate keterangan: "Pembayaran UKT Semester X"
     */
    private function generateKeterangan(string $nim, TahunAkademik $ta): string
    {
        $serviceSemester = new ServiceSemester();
        $semester = $serviceSemester->hitung($nim, $ta);

        return "Pembayaran UKT Semester {$semester}";
    }

    /**
     * Auto-create KRS + PerwalianKrsValidasi jika belum ada.
     */
    private function autoCreateKrs(string $nim, TahunAkademik $ta): void
    {
        // Cek apakah KRS sudah ada untuk TA ini
        $existingKrs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
            ->exists();

        if ($existingKrs) {
            return;
        }

        // Hitung semester mahasiswa
        $serviceSemester = new ServiceSemester();
        $semester = $serviceSemester->hitung($nim, $ta);

        // Auto-create KRS
        Krs::create([
            'nim' => $nim,
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'semester' => $semester,
        ]);

        // Auto-create PerwalianKrsValidasi (status 'N = belum divalidasi)
        PerwalianKrsValidasi::create([
            'nim' => $nim,
            'kode_dosen_validator' => null,
            'status_krs' => 'N',
        ]);
    }

    /**
     * Dapatkan SKS limit dari rules berdasarkan IPK semester sebelumnya.
     * Loop mundur jika ada cuti.
     */
    private function getSksLimitFromRule(string $nim, ?int $kodeProdi, int $currentSemester): int
    {
        if (! $kodeProdi || $currentSemester <= 1) {
            return 24;
        }

        $previousIpk = $this->findPreviousIpk($nim, $currentSemester - 1);

        if ($previousIpk === null) {
            return 24;
        }

        $rule = SksRule::cariRule($kodeProdi, $previousIpk);

        return $rule?->sks_yang_dapat_diambil ?? 24;
    }

    /**
     * Cari IPK dari semester sebelumnya (loop mundur jika cuti)
     */
    private function findPreviousIpk(string $nim, int $semester): ?float
    {
        while ($semester >= 1) {
            // 1. Cek apakah ada KRS untuk semester ini
            $krs = Krs::where('nim', $nim)->where('semester', $semester)->first();

            if ($krs) {
                // KRS ada → hitung IPK dari KHS semester ini
                $ipk = $this->computeIpkFromKrs($krs);
                if ($ipk !== null) {
                    return $ipk;
                }
                // KRS ada tapi belum ada nilai, anggap bukan cuti
                return null;
            }

            // 2. Tidak ada KRS → cek apakah cuti
            if (! $this->isCuti($nim, $semester)) {
                return null; // Bukan cuti → kasus abnormal, stop
            }

            // 3. Cuti → mundur ke semester sebelumnya
            $semester--;
        }

        return null; // Tidak ada IP ditemukan
    }

    /**
     * Cek apakah mahasiswa cuti di semester tertentu
     */
    private function isCuti(string $nim, int $targetSemester): bool
    {
        $angkatan = (int) substr($nim, 0, 2);

        // Cari semua TA dan hitung semester yang sesuai
        $tas = TahunAkademik::all();

        foreach ($tas as $ta) {
            $tahunSekarang = (int) explode('/', $ta->tahun_akademik)[0];
            $semesterTa = (int) $ta->semester;

            $calcSemester = (($tahunSekarang - (2000 + $angkatan)) * 2) + $semesterTa;

            if ($calcSemester === $targetSemester) {
                $pembayaran = Pembayaran::where('nim', $nim)
                    ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
                    ->first();

                return $pembayaran?->status_mahasiswa === 'cuti';
            }
        }

        return false;
    }

    /**
     * Hitung IPK dari KRS berdasarkan KHS detail
     */
    private function computeIpkFromKrs(Krs $krs): ?float
    {
        $details = KrsDetail::where('kode_krs', $krs->kode_krs)
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->leftJoin('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
            ->select(
                'matakuliah.sks_teori',
                'matakuliah.sks_praktik',
                'khs_detail.score'
            )
            ->whereNotNull('khs_detail.score')
            ->get();

        if ($details->isEmpty()) {
            return null;
        }

        $totalSks = 0;
        $totalWeighted = 0;

        foreach ($details as $detail) {
            $sks = ($detail->sks_teori ?? 0) + ($detail->sks_praktik ?? 0);
            $totalSks += $sks;
            $totalWeighted += $sks * $detail->score;
        }

        return $totalSks > 0 ? round($totalWeighted / $totalSks, 2) : null;
    }
}

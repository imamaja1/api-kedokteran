<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\PerwalianKrsValidasi;
use App\Models\TahunAkademik;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

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

        $pembayaran = Pembayaran::create([
            'nim' => $data['nim'],
            'kode_tahun_akademik' => $data['kode_tahun_akademik'],
            'status' => $data['status'] ?? 'belum',
            'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        // Auto-create KRS + PerwalianKrsValidasi jika belum ada
        $this->autoCreateKrs($data['nim'], $ta);

        return ApiResponse::success([
            'id' => $pembayaran->id,
            'code' => $pembayaran->toCode(),
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'status' => $pembayaran->status,
            'tanggal_bayar' => $pembayaran->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $pembayaran->keterangan,
        ], 'Pembayaran berhasil dibuat.', 201);
    }

    public function update(array $data): JsonResponse
    {
        try {
            $id = Crypt::decryptString($data['code']);
        } catch (DecryptException) {
            return ApiResponse::error('Kode tidak valid.', 422);
        }

        $pembayaran = Pembayaran::find($id);
        if (! $pembayaran) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        $oldStatus = $pembayaran->status;

        $pembayaran->update([
            'status' => $data['status'] ?? $pembayaran->status,
            'tanggal_bayar' => $data['tanggal_bayar'] ?? $pembayaran->tanggal_bayar,
            'keterangan' => $data['keterangan'] ?? $pembayaran->keterangan,
        ]);

        // Jika status berubah dari belum → lunas dan KRS belum ada, auto-create
        if ($oldStatus === 'belum' && $pembayaran->status === 'lunas') {
            $ta = TahunAkademik::find($pembayaran->kode_tahun_akademik);
            if ($ta) {
                $this->autoCreateKrs($pembayaran->nim, $ta);
            }
        }

        return ApiResponse::success([
            'id' => $pembayaran->id,
            'code' => $pembayaran->toCode(),
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'status' => $pembayaran->status,
            'tanggal_bayar' => $pembayaran->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $pembayaran->keterangan,
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
}

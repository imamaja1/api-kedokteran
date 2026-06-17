<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;

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
        $id = \Crypt::decryptString($code);

        $item = Pembayaran::with(['mahasiswa:nim,nama_mahasiswa', 'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester'])
            ->find($id);

        if (! $item) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        return ApiResponse::success([
            'id' => $item->id,
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

        return ApiResponse::success([
            'id' => $pembayaran->id,
            'nim' => $pembayaran->nim,
            'kode_tahun_akademik' => $pembayaran->kode_tahun_akademik,
            'status' => $pembayaran->status,
            'tanggal_bayar' => $pembayaran->tanggal_bayar?->format('Y-m-d'),
            'keterangan' => $pembayaran->keterangan,
        ], 'Pembayaran berhasil dibuat.', 201);
    }

    public function update(array $data): JsonResponse
    {
        $id = \Crypt::decryptString($data['code']);

        $pembayaran = Pembayaran::find($id);
        if (! $pembayaran) {
            return ApiResponse::notFound('Pembayaran tidak ditemukan.');
        }

        $pembayaran->update([
            'status' => $data['status'] ?? $pembayaran->status,
            'tanggal_bayar' => $data['tanggal_bayar'] ?? $pembayaran->tanggal_bayar,
            'keterangan' => $data['keterangan'] ?? $pembayaran->keterangan,
        ]);

        return ApiResponse::success([
            'id' => $pembayaran->id,
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
}

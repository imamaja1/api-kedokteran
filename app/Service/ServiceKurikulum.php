<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\NamaKurikulum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class ServiceKurikulum
{
    private const CACHE_TTL = 3600; // 1 jam

    /**
     * Get kurikulum untuk mahasiswa berdasarkan NIM (optimized dengan cache)
     * Entire result cached untuk 1 jam untuk menghindari query berulang
     */
    public function kurikulum_by_nim(string $nim, string $kode_prodi): JsonResponse
    {
        $angkatan = substr($nim, 0, 2);
        $cacheKey = "kurikulum_by_nim::{$nim}::{$kode_prodi}";

        // Check cache first untuk keseluruhan hasil
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return ApiResponse::success($cachedData, 'Kurikulum Mahasiswa retrieved successfully. (cached)');
        }

        // Jika tidak ada di cache, query dan simpan ke cache
        $kurikulumAngkatan = KurikulumAngkatan::select(
            'kurikulum_angkatan.angkatan',
            'kurikulum_angkatan.kode_nama_kurikulum',
            'nama_kurikulum.nama_kurikulum',
            'nama_kurikulum.kode_program_studi'
        )
            ->join('nama_kurikulum', 'kurikulum_angkatan.kode_nama_kurikulum', '=', 'nama_kurikulum.kode_nama_kurikulum')
            ->with('namaKurikulum:kode_nama_kurikulum,kode_program_studi')  // Eager load for relationship
            ->whereRaw('substr(kurikulum_angkatan.angkatan, 3, 2) = ?', [$angkatan])
            ->where('nama_kurikulum.kode_program_studi', $kode_prodi)
            ->first();

        if (! $kurikulumAngkatan) {
            return ApiResponse::error('Kurikulum untuk angkatan mahasiswa tidak ditemukan.', 404);
        }

        $data['kurikulum'] = $kurikulumAngkatan;
        $data['data_kurikulum'] = $this->buildKurikulumData($kurikulumAngkatan->kode_nama_kurikulum);

        // Cache result untuk 1 jam
        Cache::put($cacheKey, $data, self::CACHE_TTL);

        return ApiResponse::success($data, 'Kurikulum Mahasiswa retrieved successfully.');
    }

    /**
     * Get semua nama kurikulum dengan pagination
     */
    public function nama_kurikulum(): JsonResponse
    {
        $paginator = NamaKurikulum::with('programStudi:kode_program_studi,nama_program_studi')
            ->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code_nama_kurikulum' => Crypt::encryptString($item->kode_nama_kurikulum),
                'nama_kurikulum' => $item->nama_kurikulum,
                'nama_program_studi' => $item->programStudi?->nama_program_studi,
                'angkatan1' => $item->angkatan1,
                'ekstensi1' => $item->ekstensi1,
                'paket1' => $item->paket1,
            ];
        });

        return ApiResponse::paginated($paginator, 'Nama Kurikulum retrieved successfully.');
    }

    /**
     * Get satu nama kurikulum
     */
    public function getOneNamaKurikulum(string $id): JsonResponse
    {
        $item = NamaKurikulum::find($id);

        if (! $item) {
            return ApiResponse::notFound('Nama Kurikulum tidak ditemukan');
        }

        return ApiResponse::success([
            'code_nama_kurikulum' => Crypt::encryptString($item->kode_nama_kurikulum),
            'nama_kurikulum' => $item->nama_kurikulum,
            'kode_program_studi' => $item->kode_program_studi,
            'angkatan1' => $item->angkatan1,
            'ekstensi1' => $item->ekstensi1,
            'paket1' => $item->paket1,
        ], 'Nama Kurikulum retrieved successfully.');
    }

    /**
     * Create nama kurikulum (invalidate cache)
     */
    public function storeNamaKurikulum(array $object): JsonResponse
    {
        try {
            $namaKurikulum = NamaKurikulum::create($object);
            // Invalidate cache untuk kurikulum ini
            Cache::forget("kurikulum::{$namaKurikulum->kode_nama_kurikulum}");
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal membuat Nama Kurikulum: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'code_nama_kurikulum' => Crypt::encryptString($namaKurikulum->kode_nama_kurikulum),
            'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
            'kode_program_studi' => $namaKurikulum->kode_program_studi,
            'angkatan1' => $namaKurikulum->angkatan1,
            'ekstensi1' => $namaKurikulum->ekstensi1,
            'paket1' => $namaKurikulum->paket1,
        ], 'Nama Kurikulum berhasil dibuat', 201);
    }

    /**
     * Update nama kurikulum (invalidate cache)
     */
    public function updateNamaKurikulum(string $id, array $object): JsonResponse
    {
        $namaKurikulum = NamaKurikulum::find($id);

        if (! $namaKurikulum) {
            return ApiResponse::notFound('Nama Kurikulum tidak ditemukan');
        }

        try {
            $namaKurikulum->update($object);
            // Invalidate cache
            Cache::forget("kurikulum::{$namaKurikulum->kode_nama_kurikulum}");
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal memperbarui Nama Kurikulum: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'code_nama_kurikulum' => Crypt::encryptString($namaKurikulum->kode_nama_kurikulum),
            'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
            'kode_program_studi' => $namaKurikulum->kode_program_studi,
            'angkatan1' => $namaKurikulum->angkatan1,
            'ekstensi1' => $namaKurikulum->ekstensi1,
            'paket1' => $namaKurikulum->paket1,
        ], 'Nama Kurikulum berhasil diperbarui');
    }

    /**
     * Delete nama kurikulum (invalidate cache)
     */
    public function deleteNamaKurikulum(string $id): JsonResponse
    {
        $namaKurikulum = NamaKurikulum::find($id);

        if (! $namaKurikulum) {
            return ApiResponse::notFound('Nama Kurikulum tidak ditemukan');
        }

        $kodeToInvalidate = $namaKurikulum->kode_nama_kurikulum;

        try {
            $namaKurikulum->delete();
            // Invalidate cache
            Cache::forget("kurikulum::{$kodeToInvalidate}");
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal menghapus Nama Kurikulum: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'code_nama_kurikulum' => Crypt::encryptString($kodeToInvalidate),
            'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
            'kode_program_studi' => $namaKurikulum->kode_program_studi,
            'angkatan1' => $namaKurikulum->angkatan1,
            'ekstensi1' => $namaKurikulum->ekstensi1,
            'paket1' => $namaKurikulum->paket1,
        ], 'Nama Kurikulum berhasil dihapus');
    }

    /**
     * Get kurikulum berdasarkan kode nama kurikulum (dengan cache)
     */
    public function kurikulum_by_nama_kurikulum(string $kode_nama_kurikulum): JsonResponse
    {
        $data = $this->buildKurikulumData($kode_nama_kurikulum);

        return ApiResponse::success($data, 'Kurikulum retrieved successfully.');
    }

    /**
     * Build kurikulum data dengan caching (optimized)
     * Sebelumnya: 1 query join kompleks tiap kali
     * Sekarang: 1 query eager load pertama kali, kemudian cached 1 jam
     */
    private function buildKurikulumData(string $kode_nama_kurikulum): array
    {
        $cacheKey = "kurikulum::{$kode_nama_kurikulum}";

        // Return cached data jika ada
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kode_nama_kurikulum) {
            return Kurikulum::where('kode_nama_kurikulum', $kode_nama_kurikulum)
                ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block')
                ->select('semester', 'id_matakuliah', 'kode_nama_kurikulum')
                ->orderBy('semester')
                ->get()
                ->groupBy('semester')
                ->map(function ($items, $sem) {
                    $total_sks = $items->sum(function ($item) {
                        return ($item->matakuliah->sks_teori ?? 0) + ($item->matakuliah->sks_praktik ?? 0);
                    });

                    return [
                        'semester' => $sem,
                        'total_sks' => $total_sks,
                        'matakuliah' => $items->map(fn ($item) => [
                            'kode_matakuliah' => $item->matakuliah->kode_matakuliah,
                            'nama_matakuliah' => $item->matakuliah->nama_matakuliah,
                            'sks_teori' => $item->matakuliah->sks_teori,
                            'sks_praktik' => $item->matakuliah->sks_praktik,
                            'block' => (bool) $item->matakuliah->block,
                        ])->values(),
                    ];
                })
                ->values()
                ->toArray();
        });
    }
}

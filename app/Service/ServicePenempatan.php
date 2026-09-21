<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;

class ServicePenempatan
{
    public function index(array $filters = []): JsonResponse
    {
        $query = KelasMahasiswa::with([
            'kelas' => function ($q) {
                $q->with([
                    'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah',
                    'namaKelas:nama_kelas_id,nama_kelas',
                    'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                ]);
            },
            'krsDetail' => function ($q) {
                $q->with('krs:kode_krs,nim,kode_tahun_akademik')
                    ->with('krs.mahasiswa:nim,nama_mahasiswa');
            },
        ]);

        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }

        if (! empty($filters['nim'])) {
            $query->whereHas('krsDetail.krs', function ($q) use ($filters) {
                $q->where('nim', $filters['nim']);
            });
        }

        $data = $query->orderByDesc('kelas_mahasiswa_id')->get()
            ->map(function ($item) {
                return [
                    'kelas_mahasiswa_id' => $item->kelas_mahasiswa_id,
                    'kode_krs_detail' => $item->kode_krs_detail,
                    'kelas_id' => $item->kelas_id,
                    'nama_kelas' => $item->kelas?->namaKelas?->nama_kelas,
                    'kode_matakuliah' => $item->kelas?->matakuliah?->kode_matakuliah,
                    'nama_mahasiswa' => $item->krsDetail?->krs?->mahasiswa?->nama_mahasiswa,
                    'nim' => $item->krsDetail?->krs?->nim,
                    'tahun_akademik' => $item->kelas?->tahunAkademik?->tahun_akademik,
                    'semester' => $item->kelas?->tahunAkademik?->semester,
                ];
            })->values()->toArray();

        return ApiResponse::success($data, 'Data penempatan kelas berhasil diambil.');
    }

    public function belumKelas(array $filters = []): JsonResponse
    {
        $query = KrsDetail::with([
            'krs:kode_krs,nim,kode_tahun_akademik,semester',
            'krs.mahasiswa:nim,nama_mahasiswa',
            'krs.tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
        ])->doesntHave('kelasMahasiswa');

        if (! empty($filters['tahun_akademik'])) {
            $query->whereHas('krs', function ($q) use ($filters) {
                $q->whereHas('tahunAkademik', function ($q2) use ($filters) {
                    $q2->where('tahun_akademik', $filters['tahun_akademik']);
                });
            });
        }

        if (! empty($filters['id_matakuliah'])) {
            $query->where('id_matakuliah', $filters['id_matakuliah']);
        }

        $data = $query->orderByDesc('kode_krs_detail')->get()
            ->map(fn ($item) => [
                'code_krs_detail' => $item->toCode(),
                'kode_krs_detail' => $item->kode_krs_detail,
                'nim' => $item->krs?->nim,
                'nama_mahasiswa' => $item->krs?->mahasiswa?->nama_mahasiswa,
                'semester' => $item->krs?->semester,
                'tahun_akademik' => $item->krs?->tahunAkademik?->tahun_akademik,
                'kode_matakuliah' => $item->matakuliah?->kode_matakuliah,
                'nama_matakuliah' => $item->matakuliah?->nama_matakuliah,
                'sks_teori' => $item->matakuliah?->sks_teori,
                'sks_praktik' => $item->matakuliah?->sks_praktik,
            ])->values()->toArray();

        return ApiResponse::success($data, 'Matakuliah yang belum masuk kelas berhasil diambil.');
    }

    public function store(array $data): JsonResponse
    {
        try {
            $krsDetail = KrsDetail::with(['krs.mahasiswa', 'matakuliah'])
                ->where('kode_krs_detail', $data['kode_krs_detail'])
                ->first();

            if (! $krsDetail) {
                return ApiResponse::notFound('Detail KRS tidak ditemukan.');
            }

            $kelas = Kelas::with(['matakuliah', 'namaKelas'])
                ->where('kelas_id', $data['kelas_id'])
                ->first();

            if (! $kelas) {
                return ApiResponse::notFound('Kelas tidak ditemukan.');
            }

            if ($krsDetail->id_matakuliah !== $kelas->id_matakuliah) {
                return ApiResponse::error('Matakuliah di KRS tidak sesuai dengan matakuliah kelas.', 422);
            }

            $existingSameMatakuliah = KelasMahasiswa::whereHas('krsDetail.krs', function ($q) use ($krsDetail) {
                $q->where('nim', $krsDetail->krs?->nim);
            })
                ->whereHas('kelas', function ($q) use ($kelas) {
                    $q->where('id_matakuliah', $kelas->id_matakuliah);
                })
                ->first();

            if ($existingSameMatakuliah) {
                return ApiResponse::error('Mahasiswa sudah ditempatkan di kelas lain untuk matakuliah yang sama.', 422);
            }

            $existingSameDetail = KelasMahasiswa::where('kode_krs_detail', $data['kode_krs_detail'])
                ->where('kelas_id', $data['kelas_id'])
                ->first();

            if ($existingSameDetail) {
                return ApiResponse::error('Mahasiswa sudah ditempatkan di kelas ini.', 422);
            }

            $penempatan = KelasMahasiswa::create([
                'kode_krs_detail' => $data['kode_krs_detail'],
                'kelas_id' => $data['kelas_id'],
            ]);

            return ApiResponse::success([
                'kelas_mahasiswa_id' => $penempatan->kelas_mahasiswa_id,
                'kode_krs_detail' => $penempatan->kode_krs_detail,
                'kelas_id' => $penempatan->kelas_id,
                'nim' => $krsDetail->krs?->nim,
                'nama_mahasiswa' => $krsDetail->krs?->mahasiswa?->nama_mahasiswa,
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
            ], 'Mahasiswa berhasil ditempatkan di kelas.', 201);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal menyimpan penempatan: '.$e->getMessage(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $penempatan = KelasMahasiswa::find($id);

            if (! $penempatan) {
                return ApiResponse::notFound('Penempatan tidak ditemukan.');
            }

            $penempatan->delete();

            return ApiResponse::success(null, 'Penempatan berhasil dihapus.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal menghapus penempatan: '.$e->getMessage(), 422);
        }
    }
}

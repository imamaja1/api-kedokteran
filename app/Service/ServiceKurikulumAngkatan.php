<?php

namespace App\Service;

use App\Models\KurikulumAngkatan;
use Illuminate\Http\JsonResponse;

class ServiceKurikulumAngkatan
{
    public function getAllKurikulumAngkatan(array $filters = []): JsonResponse
    {
        $query = KurikulumAngkatan::with('namaKurikulum')
            ->orderByDesc('kode_kurikulum_angkatan');

        if (! empty($filters['angkatan'])) {
            $query->where('angkatan', $filters['angkatan']);
        }

        if (! empty($filters['kode_nama_kurikulum'])) {
            $query->where('kode_nama_kurikulum', $filters['kode_nama_kurikulum']);
        }

        if (! empty($filters['ekstensi'])) {
            $query->where('ekstensi', $filters['ekstensi']);
        }

        if (! empty($filters['paket'])) {
            $query->where('paket', $filters['paket']);
        }

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => $item->toCode(),
                'angkatan' => $item->angkatan,
                'code_nama_kurikulum' => $item->namaKurikulum?->toCode(),
                'nama_kurikulum' => $item->namaKurikulum?->nama_kurikulum ?? null,
                'ekstensi' => $item->ekstensi,
                'paket' => $item->paket,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'API Kurikulum Angkatan',
            'jumlah' => $paginator->total(),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function getOneKurikulumAngkatan(string $id): JsonResponse
    {
        $data = KurikulumAngkatan::with('namaKurikulum')->find($id);

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Kurikulum angkatan tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Kurikulum Angkatan',
            'data' => [
                'code' => $data->toCode(),
                'angkatan' => $data->angkatan,
                'code_nama_kurikulum' => $data->namaKurikulum?->toCode(),
                'nama_kurikulum' => $data->namaKurikulum?->nama_kurikulum ?? null,
                'ekstensi' => $data->ekstensi,
                'paket' => $data->paket,
            ],
        ]);
    }

    public function storeKurikulumAngkatan(array $object): JsonResponse
    {
        try {
            $kurikulumAngkatan = KurikulumAngkatan::create($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Kurikulum Angkatan',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Kurikulum Angkatan berhasil dibuat',
            'data' => [
                'code' => $kurikulumAngkatan->toCode(),
                'angkatan' => $kurikulumAngkatan->angkatan,
                'code_nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->toCode(),
                'ekstensi' => $kurikulumAngkatan->ekstensi,
                'paket' => $kurikulumAngkatan->paket,
            ],
        ], 201);
    }

    public function updateKurikulumAngkatan(string $id, array $object): JsonResponse
    {
        $kurikulumAngkatan = KurikulumAngkatan::find($id);

        if (! $kurikulumAngkatan) {
            return response()->json([
                'status' => false,
                'message' => 'Kurikulum angkatan tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $kurikulumAngkatan->update($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Kurikulum Angkatan',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Kurikulum Angkatan berhasil diperbarui',
            'data' => [
                'code' => $kurikulumAngkatan->toCode(),
                'angkatan' => $kurikulumAngkatan->angkatan,
                'code_nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->toCode(),
                'ekstensi' => $kurikulumAngkatan->ekstensi,
                'paket' => $kurikulumAngkatan->paket,
            ],
        ]);
    }

    public function deleteKurikulumAngkatan(string $id): JsonResponse
    {
        $kurikulumAngkatan = KurikulumAngkatan::find($id);

        if (! $kurikulumAngkatan) {
            return response()->json([
                'status' => false,
                'message' => 'Kurikulum angkatan tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $kurikulumAngkatan->delete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Kurikulum Angkatan',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Kurikulum Angkatan berhasil dihapus',
            'data' => [
                'code' => $kurikulumAngkatan->toCode(),
                'angkatan' => $kurikulumAngkatan->angkatan,
                'code_nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->toCode(),
                'ekstensi' => $kurikulumAngkatan->ekstensi,
                'paket' => $kurikulumAngkatan->paket,
            ],
        ]);
    }
}

<?php

namespace App\Service;

use App\Models\Matakuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class ServiceMatakuliah
{
    public function getAllMatakuliah(?string $kode_program_studi = null): JsonResponse
    {
        $query = Matakuliah::select(
            'id_matakuliah',
            'kode_matakuliah',
            'nama_matakuliah',
            'sks_teori',
            'sks_praktik',
            'block',
        );

        if ($kode_program_studi) {
            $query->where('kode_program_studi', $kode_program_studi);
        }

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => Crypt::encryptString($item->id_matakuliah),
                'kode_matakuliah' => $item->kode_matakuliah,
                'nama_matakuliah' => $item->nama_matakuliah,
                'sks_teori' => $item->sks_teori,
                'sks_praktik' => $item->sks_praktik,
                'block' => (bool) $item->block,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'API Matakuliah',
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

    public function getOneMatakuliah(string $id): JsonResponse
    {
        $matakuliah = Matakuliah::where('id_matakuliah', $id)->first([
            'id_matakuliah',
            'kode_matakuliah',
            'nama_matakuliah',
            'sks_teori',
            'sks_praktik',
            'block',
        ]);

        if (! $matakuliah) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Matakuliah',
            'data' => [
                'code' => Crypt::encryptString($matakuliah->id_matakuliah),
                'kode_matakuliah' => $matakuliah->kode_matakuliah,
                'nama_matakuliah' => $matakuliah->nama_matakuliah,
                'sks_teori' => $matakuliah->sks_teori,
                'sks_praktik' => $matakuliah->sks_praktik,
                'block' => (bool) $matakuliah->block,
            ],
        ]);
    }

    public function storeMatakuliah(array $object): JsonResponse
    {
        try {
            $matakuliah = Matakuliah::create($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Matakuliah',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dibuat',
            'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),
        ], 201);
    }

    public function updateMatakuliah(string $id, array $object): JsonResponse
    {
        $matakuliah = Matakuliah::where('id_matakuliah', $id)->first();

        if (! $matakuliah) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $matakuliah->update($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Matakuliah',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil diperbarui',
            'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),
        ]);
    }

    public function deleteMatakuliah(string $id): JsonResponse
    {
        $matakuliah = Matakuliah::where('id_matakuliah', $id)->first();

        if (! $matakuliah) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $matakuliah->delete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Matakuliah',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dihapus',
            'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),
        ]);
    }
}

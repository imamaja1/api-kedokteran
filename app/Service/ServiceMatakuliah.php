<?php

namespace App\Service;

use App\Models\Matakuliah;
use Illuminate\Support\Facades\Crypt;

class ServiceMatakuliah
{
    public function getAllMatakuliah(?string $kode_program_studi = null)
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

        $data = $query->get()
            ->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
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
            'data' => $data,
        ]);
    }

    public function getOneMatakuliah(string $id)
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

        $data = [
            'id' => 1,
            'code' => Crypt::encryptString($matakuliah->id_matakuliah),
            'kode_matakuliah' => $matakuliah->kode_matakuliah,
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'sks_teori' => $matakuliah->sks_teori,
            'sks_praktik' => $matakuliah->sks_praktik,
            'block' => (bool) $matakuliah->block,
        ];

        return response()->json([
            'status' => true,
            'message' => 'API Matakuliah',
            'data' => $data,
        ]);
    }

    public function storeMatakuliah(array $object)
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

    public function updateMatakuliah(string $id, array $object)
    {
        $matakuliah = Matakuliah::find($id);

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
                'data' => $matakuliah,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil diperbarui',
            'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),
        ]);
    }

    public function deleteMatakuliah(string $id)
    {
        $matakuliah = Matakuliah::find($id);

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
                'data' => $matakuliah,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dihapus',
            'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),
        ]);
    }
}

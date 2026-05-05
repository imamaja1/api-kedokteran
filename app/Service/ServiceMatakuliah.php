<?php

namespace App\Service;
use App\Models\Matakuliah;
use Illuminate\Support\Facades\Crypt;

class ServiceMatakuliah
{
    public function __construct()
    {
        //
    }

    public function getAllMatakuliah()
    {
        $data = Matakuliah::select(
            'id_matakuliah',
            'kode_matakuliah',
            'nama_matakuliah',
            'sks_teori',
            'sks_praktik',
            'block'
            )->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_matakuliah,
                    'code' => Crypt::encryptString($item->id_matakuliah),
                    'kode_matakuliah' => $item->kode_matakuliah,
                    'nama_matakuliah' => $item->nama_matakuliah,
                    'sks_teori' => $item->sks_teori,
                    'sks_praktik' => $item->sks_praktik,
                    'block' => $item->block
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'API Matakuliah',
            'data' => $data
        ]);
    }

    public function getOneMatakuliah($id)
    {
        $data = Matakuliah::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Matakuliah',
            'data' => $data
        ]);
    }

    public function storeMatakuliah($object)
    {
        try {
              $matakuliah = Matakuliah::create($object);
        } catch (\Throwable $th) {
           return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Matakuliah',
                'data' => $matakuliah
            ], 500);
        }
      
        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dibuat',
            'data' => $matakuliah
        ], 201);
    }

    public function updateMatakuliah($id, $object)
    {
        $matakuliah = Matakuliah::find($id);

        if (!$matakuliah) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null
            ], 404);
        }

        try {
            $matakuliah->update($object);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Matakuliah',
                'data' => $matakuliah
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil diperbarui',
            'data' => $matakuliah
        ]);
    }

    public function deleteMatakuliah($id)
    {
        $matakuliah = Matakuliah::find($id);

        if (!$matakuliah) {
            return response()->json([
                'status' => false,
                'message' => 'Matakuliah tidak ditemukan',
                'data' => null
            ], 404);
        }
        try {
            $matakuliah->delete();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Matakuliah',
                'data' => $matakuliah
            ], 500);
        }
        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dihapus',
            'data' => $matakuliah
        ]);
    }
}

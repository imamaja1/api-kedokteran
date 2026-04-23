<?php

namespace App\Service;
use App\Models\Mahasiswa;

class ServiceMahasiswa
{
    public function __construct()
    {
        //
    }
    public function getAllMahasiswa($nim=null, $kode_prodi=null, $angkatan=null)
    {
        $query = Mahasiswa::query();

        if ($nim) {
            $query->where('nim', $nim);
        }

        if ($kode_prodi) {
            $query->where('program_studi_kode', $kode_prodi);
        }

        if ($angkatan) {
            $query->whereRaw('substr(nim, 1, 2) = ?', [$angkatan]);
        }

        $data = $query->get();
        if ($data->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa not found',
                'data' => null
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa',
            'data' => $data
        ]);
    }
}

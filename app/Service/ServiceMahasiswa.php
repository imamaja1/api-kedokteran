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

        $data = $query->select(
            'nim',
            'nik',
            'program_studi_kode',
            'nama_mahasiswa',
            'tempat_lahir',
            'tanggal_lahir',
            'alamat',
            'kota',
            'propinsi',
            'telepon',
            'jenis_kelamin',
            'agama',
            'golongan_darah',
            'kewarganegaraan',
            'email',
            'nama_ayah',
            'agama_ayah',
            'pekerjaan_ayah',
            'nama_ibu',
            'agama_ibu',
            'pekerjaan_ibu',
            'alamat_orangtua',
            'kota_orangtua',
            'propinsi_orangtua',
            'telepon_orangtua',
            'foto',
            'status',
            'status_pendaftaran',
        )->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa not found',
                'jumlah' => 0,
                'data' => null
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa',
            'jumlah' => $data->count(),
            'data' => $data
        ]);
    }
}

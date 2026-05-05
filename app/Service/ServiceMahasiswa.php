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

        $data = $query->get()->map(function ($item,$nomor) {
         return [
            'id' => $nomor+1,
            'nim' => $item->nim,
            'nik' => $item->nik,
            'program_studi_kode' => $item->program_studi_kode,
            'nama_mahasiswa' => $item->nama_mahasiswa,
            'tempat_lahir' => $item->tempat_lahir,
            'tanggal_lahir' => $item->tanggal_lahir,
            'alamat' => $item->alamat,
            'kota' => $item->kota,
            'propinsi' => $item->propinsi,
            'telepon' => $item->telepon,
            'jenis_kelamin' => $item->jenis_kelamin,
            'agama' => $item->agama,
            'golongan_darah' => $item->golongan_darah,
            'kewarganegaraan' => $item->kewarganegaraan,
            'email' => $item->email,
            'nama_ayah' => $item->nama_ayah,
            'agama_ayah' => $item->agama_ayah,
            'pekerjaan_ayah' => $item->pekerjaan_ayah,
            'nama_ibu' => $item->nama_ibu,
            'agama_ibu' => $item->agama_ibu,
            'pekerjaan_ibu' => $item->pekerjaan_ibu,
            'alamat_orangtua' => $item->alamat_orangtua,
            'kota_orangtua' => $item->kota_orangtua,
            'propinsi_orangtua' => $item->propinsi_orangtua,
            'telepon_orangtua' => $item->telepon_orangtua,
            'foto' => $item->foto,
            'status' => $item->status,
            'status_pendaftaran' => $item->status_pendaftaran,
            ];
        });

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

<?php

namespace App\Service;

use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;

class ServiceMahasiswa
{
    public function __construct()
    {
        //
    }

    public function getAllMahasiswa($nim = null, $kode_prodi = null, $angkatan = null)
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

        $data = $query->get()->map(function ($item, $nomor) {
            return [
                'id' => $nomor + 1,
                'code' => Crypt::encryptString($item->nim),
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
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa',
            'jumlah' => $data->count(),
            'data' => $data,
        ]);
    }

    public function getOneMahasiswa($nim)
    {
        $data = Mahasiswa::where('nim', $nim)->first();

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa',
            'data' => [
                'code' => Crypt::encryptString($data->nim),
                'nim' => $data->nim,
                'nik' => $data->nik,
                'program_studi_kode' => $data->program_studi_kode,
                'nama_mahasiswa' => $data->nama_mahasiswa,
                'tempat_lahir' => $data->tempat_lahir,
                'tanggal_lahir' => $data->tanggal_lahir,
                'alamat' => $data->alamat,
                'kota' => $data->kota,
                'propinsi' => $data->propinsi,
                'telepon' => $data->telepon,
                'jenis_kelamin' => $data->jenis_kelamin,
                'agama' => $data->agama,
                'golongan_darah' => $data->golongan_darah,
                'kewarganegaraan' => $data->kewarganegaraan,
                'email' => $data->email,
                'nama_ayah' => $data->nama_ayah,
                'agama_ayah' => $data->agama_ayah,
                'pekerjaan_ayah' => $data->pekerjaan_ayah,
                'nama_ibu' => $data->nama_ibu,
                'agama_ibu' => $data->agama_ibu,
                'pekerjaan_ibu' => $data->pekerjaan_ibu,
                'alamat_orangtua' => $data->alamat_orangtua,
                'kota_orangtua' => $data->kota_orangtua,
                'propinsi_orangtua' => $data->propinsi_orangtua,
                'telepon_orangtua' => $data->telepon_orangtua,
                'foto' => $data->foto,
                'status' => $data->status,
                'status_pendaftaran' => $data->status_pendaftaran,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ],
        ]);
    }

    public function storeMahasiswa($object)
    {
        try {
            $mahasiswa = Mahasiswa::create($object);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Mahasiswa: '.$th->getMessage(),
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dibuat',
            'data' => $mahasiswa,
        ], 201);
    }

    public function updateMahasiswa($nim, $object)
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $mahasiswa->update($object);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Mahasiswa: '.$th->getMessage(),
                'data' => $mahasiswa,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil diperbarui',
            'data' => $mahasiswa,
        ]);
    }

    public function deleteMahasiswa($nim)
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $mahasiswa->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Mahasiswa: '.$th->getMessage(),
                'data' => $mahasiswa,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dihapus',
            'data' => $mahasiswa,
        ]);
    }
}

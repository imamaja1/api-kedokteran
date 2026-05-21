<?php

namespace App\Service;

use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ServiceMahasiswa
{
    public function getAllMahasiswa(?string $nim = null, ?string $kode_prodi = null, ?string $angkatan = null): JsonResponse
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

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return $this->formatMahasiswa($item, $index + 1);
        });

        if ($paginator->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa',
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

    public function getOneMahasiswa(string $nim): JsonResponse
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
            'data' => $this->formatMahasiswa($data),
        ]);
    }

    public function storeMahasiswa(array $object): JsonResponse
    {
        if (! empty($object['sandi'])) {
            $object['sandi'] = Hash::make($object['sandi']);
        }

        try {
            $mahasiswa = Mahasiswa::create($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Mahasiswa',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dibuat',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            ],
        ], 201);
    }

    public function updateMahasiswa(string $nim, array $object): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if (! empty($object['sandi'])) {
            $object['sandi'] = Hash::make($object['sandi']);
        }

        try {
            $mahasiswa->update($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Mahasiswa',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil diperbarui',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            ],
        ]);
    }

    public function deleteMahasiswa(string $nim): JsonResponse
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
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Mahasiswa',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dihapus',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            ],
        ]);
    }

    public function getMahasiswaTrash(?string $nim = null, ?string $kode_prodi = null, ?string $angkatan = null): JsonResponse
    {
        $query = Mahasiswa::onlyTrashed();

        if ($nim) {
            $query->where('nim', $nim);
        }

        if ($kode_prodi) {
            $query->where('program_studi_kode', $kode_prodi);
        }

        if ($angkatan) {
            $query->whereRaw('substr(nim, 1, 2) = ?', [$angkatan]);
        }

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return $this->formatMahasiswaTrash($item, $index + 1);
        });

        if ($paginator->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada Mahasiswa yang dihapus',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Mahasiswa (Trash)',
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

    public function restoreMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->where('nim', $nim)->first();

        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan di trash',
                'data' => null,
            ], 404);
        }

        try {
            $mahasiswa->restore();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memulihkan Mahasiswa',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dipulihkan',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            ],
        ]);
    }

    public function forceDeleteMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->where('nim', $nim)->first();

        if (! $mahasiswa) {
            return response()->json([
                'status' => false,
                'message' => 'Mahasiswa tidak ditemukan di trash',
                'data' => null,
            ], 404);
        }

        try {
            $mahasiswa->forceDelete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus permanen Mahasiswa',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dihapus permanen',
            'data' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            ],
        ]);
    }

    private function formatMahasiswa(Mahasiswa $item, ?int $index = null): array
    {
        $data = [
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

        if ($index !== null) {
            $data = ['id' => $index] + $data;
        }

        return $data;
    }

    private function formatMahasiswaTrash(Mahasiswa $item, ?int $index = null): array
    {
        $data = $this->formatMahasiswa($item, $index);
        $data['deleted_at'] = $item->deleted_at;

        return $data;
    }
}

<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ServiceMahasiswa
{
    // Kolom yang diselect untuk list view (optimize response size)
    private const LIST_COLUMNS = [
        'nim',
        'nama_mahasiswa',
        'program_studi_kode',
        'email',
        'telepon',
        'status',
    ];

    // Kolom yang diselect untuk single view (semua kecuali sandi yang di-hidden)
    private const DETAIL_COLUMNS = [
        'nim', 'nik', 'npm', 'nisn', 'nomor_pendaftaran', 'nomor_pendaftaran_ulang',
        'program_studi_kode', 'nama_mahasiswa', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'kota', 'propinsi', 'telepon', 'jenis_kelamin', 'agama',
        'golongan_darah', 'kewarganegaraan', 'nama_instansi', 'email',
        'nama_ayah', 'agama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'agama_ibu',
        'pekerjaan_ibu', 'alamat_orangtua', 'kota_orangtua', 'propinsi_orangtua',
        'telepon_orangtua', 'foto', 'status', 'status_pendaftaran', 'ta_lulus', 'created_at', 'updated_at',
    ];

    /**
     * Get semua mahasiswa (Real-Time)
     */
    public function getAllMahasiswa(?string $nim = null, ?string $kode_prodi = null, ?string $angkatan = null): JsonResponse
    {
        $query = Mahasiswa::select(self::LIST_COLUMNS)
            ->with('programStudi:kode_program_studi,nama_program_studi');

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
            return $this->formatMahasiswaList($item, $index + 1);
        });

        return ApiResponse::paginated($paginator, 'Data Mahasiswa');
    }

    /**
     * Get satu mahasiswa (Real-Time)
     */
    public function getOneMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::select(self::DETAIL_COLUMNS)
            ->with('programStudi:kode_program_studi,nama_program_studi')
            ->where('nim', $nim)
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        return ApiResponse::success($this->formatMahasiswaDetail($mahasiswa), 'Data Mahasiswa');
    }

    /**
     * Create mahasiswa
     */
    public function storeMahasiswa(array $object): JsonResponse
    {
        if (! empty($object['sandi'])) {
            $object['sandi'] = Hash::make($object['sandi']);
        }

        try {
            $mahasiswa = Mahasiswa::create($object);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal membuat Mahasiswa: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
        ], 'Mahasiswa berhasil dibuat', 201);
    }

    /**
     * Update mahasiswa
     */
    public function updateMahasiswa(string $nim, array $object): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        if (! empty($object['sandi'])) {
            $object['sandi'] = Hash::make($object['sandi']);
        }

        try {
            $mahasiswa->update($object);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal memperbarui Mahasiswa: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
        ], 'Mahasiswa berhasil diperbarui');
    }

    /**
     * Soft delete mahasiswa
     */
    public function deleteMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        try {
            $mahasiswa->delete();
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal menghapus Mahasiswa: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
        ], 'Mahasiswa berhasil dihapus');
    }

    /**
     * Get mahasiswa yang dihapus (trash) - Real-Time
     */
    public function getMahasiswaTrash(?string $nim = null, ?string $kode_prodi = null, ?string $angkatan = null): JsonResponse
    {
        $query = Mahasiswa::onlyTrashed()
            ->select(self::LIST_COLUMNS)
            ->with('programStudi:kode_program_studi,nama_program_studi');

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
            return $this->formatMahasiswaList($item, $index + 1);
        });

        return ApiResponse::paginated($paginator, 'Data Mahasiswa (Trash)');
    }

    /**
     * Restore mahasiswa dari trash
     */
    public function restoreMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->where('nim', $nim)->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan di trash');
        }

        try {
            $mahasiswa->restore();
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal memulihkan Mahasiswa: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
        ], 'Mahasiswa berhasil dipulihkan');
    }

    /**
     * Force delete mahasiswa secara permanen
     */
    public function forceDeleteMahasiswa(string $nim): JsonResponse
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->where('nim', $nim)->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan di trash');
        }

        try {
            $mahasiswa->forceDelete();
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal menghapus permanen Mahasiswa: '.$e->getMessage(), 500);
        }

        return ApiResponse::success([
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
        ], 'Mahasiswa berhasil dihapus permanen');
    }

    /**
     * Format mahasiswa untuk list view
     */
    private function formatMahasiswaList(Mahasiswa $item, ?int $index = null): array
    {
        $data = [
            'code' => Crypt::encryptString($item->nim),
            'nim' => $item->nim,
            'nama_mahasiswa' => $item->nama_mahasiswa,
            'program_studi_kode' => $item->program_studi_kode,
            'nama_program_studi' => $item->programStudi?->nama_program_studi,
            'email' => $item->email,
            'telepon' => $item->telepon,
            'status' => $item->status,
        ];

        if ($index !== null) {
            $data = ['id' => $index] + $data;
        }

        return $data;
    }

    /**
     * Format mahasiswa untuk detail view
     */
    private function formatMahasiswaDetail(Mahasiswa $item): array
    {
        return [
            'code' => Crypt::encryptString($item->nim),
            'nim' => $item->nim,
            'nik' => $item->nik,
            'npm' => $item->npm,
            'nisn' => $item->nisn,
            'nomor_pendaftaran' => $item->nomor_pendaftaran,
            'nomor_pendaftaran_ulang' => $item->nomor_pendaftaran_ulang,
            'program_studi_kode' => $item->program_studi_kode,
            'nama_program_studi' => $item->programStudi?->nama_program_studi,
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
            'nama_instansi' => $item->nama_instansi,
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
            'ta_lulus' => $item->ta_lulus,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}

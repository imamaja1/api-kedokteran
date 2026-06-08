<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Krs;
use App\Models\Mahasiswa;

class ServiceKRS
{
    /**
     * Get KRS untuk mahasiswa dengan eager loading (optimized)
     * Queries: 1 untuk mahasiswa + 1 untuk KRS dengan all relations = 2 total
     * Sebelumnya: 10-15 queries (N+1 problem)
     */
    public function getKRSMhs(string $nim, ?int $semester = null)
    {
        // Get mahasiswa data
        $mahasiswa = Mahasiswa::where('nim', $nim)
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode', 'alamat', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'telepon_orangtua')
            ->with('programStudi:kode_program_studi,nama_program_studi')
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        // Determine semester
        if ($semester === null) {
            $latestKrs = Krs::where('nim', $nim)->orderBy('semester', 'desc')->first();
            if (! $latestKrs) {
                return ApiResponse::success([
                    'mahasiswa' => [],
                    'krs' => [],
                ], 'Belum ada data KRS.');
            }
            $semester = $latestKrs->semester;
        }

        // Get KRS dengan eager loading untuk semua relations
        // FIX: Include all necessary keys dan columns untuk eager loading
        $krs = Krs::where('nim', $nim)
            ->where('semester', $semester)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')  // Include primary key explicitly
            ->with([
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah', 'status')
                        ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::success([
                'mahasiswa' => [],
                'krs' => [],
            ], 'Belum ada data KRS untuk semester ini.');
        }
        // Format data
        $data = [
            'mahasiswa' => [
                [
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $mahasiswa->programStudi->nama_program_studi,
                    'alamat' => $mahasiswa->alamat,
                    'tempat_lahir' => $mahasiswa->tempat_lahir,
                    'tanggal_lahir' => $mahasiswa->tanggal_lahir,
                    'telepon' => $mahasiswa->telepon,
                    'telepon_orangtua' => $mahasiswa->telepon_orangtua,
                    'semester' => $semester,
                ],
            ],
            'krs' => $krs->krsDetail->map(function ($detail, $idx) {
                return [
                    'id' => $idx + 1,
                    'kode' => $detail->toCode(),
                    'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                    'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                    'sks_teori' => $detail->matakuliah->sks_teori,
                    'sks_praktik' => $detail->matakuliah->sks_praktik,
                    'block' => (bool) $detail->matakuliah->block,
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'KRS Mahasiswa retrieved successfully.');
    }

    /**
     * Get all KRS records untuk mahasiswa
     */
    public function getAllKRS(string $nim)
    {
        $krsRecords = Krs::where('nim', $nim)
            ->select('kode_krs', 'nim', 'semester')  // Include primary key explicitly
            ->orderBy('semester', 'desc')
            ->get();

        if ($krsRecords->isEmpty()) {
            return ApiResponse::notFound('Tidak ada data KRS untuk mahasiswa ini');
        }

        $data = $krsRecords->map(fn ($item, $idx) => [
            'id' => $idx + 1,
            'code_krs' => $item->toCode(),
            'semester' => $item->semester,
        ])->values()->toArray();

        return ApiResponse::success($data, 'KRS Mahasiswa retrieved successfully.');
    }

    /**
     * Get KRS detail dengan eager loading
     */
    public function getKRSDetail(string $kode_krs)
    {
        $krs = Krs::where('kode_krs', $kode_krs)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')  // Include primary key
            ->with([
                'mahasiswa:nim,nama_mahasiswa,program_studi_kode',
                'mahasiswa.programStudi:kode_program_studi,nama_program_studi',
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('kode_krs_detail', 'kode_krs', 'id_matakuliah', 'status')
                        ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS tidak ditemukan');
        }

        $data = [
            'mahasiswa' => [
                [
                    'nim' => $krs->mahasiswa->nim,
                    'nama_mahasiswa' => $krs->mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $krs->mahasiswa->programStudi->nama_program_studi,
                    'semester' => $krs->tahunAkademik->semester,
                ],
            ],
            'krs' => $krs->krsDetail->map(fn ($detail, $idx) => [
                'id' => $idx + 1,
                'code' => $detail->toCode(),
                'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                'sks_teori' => $detail->matakuliah->sks_teori,
                'sks_praktik' => $detail->matakuliah->sks_praktik,
                'block' => (bool) $detail->matakuliah->block,
            ])->values()->toArray(),
        ];

        return ApiResponse::success($data, 'KRS Detail retrieved successfully.');
    }
}

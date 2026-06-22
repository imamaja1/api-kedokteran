<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Krs;
use App\Models\Mahasiswa;

class ServiceKHS
{
    /**
     * Get KHS untuk mahasiswa dengan eager loading (optimized)
     * Queries: 1 untuk mahasiswa + 1 untuk KRS dengan all relations = 2 total
     * Sebelumnya: 12-18 queries (N+1 problem dengan KhsDetail)
     */
    public function getKHSMhs(string $nim, ?int $semester = null)
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
                    'khs' => [],
                ], 'Belum ada data KHS.');
            }
            $semester = $latestKrs->semester;
        }

        // Get KRS dengan eager loading untuk semua relations termasuk KhsDetail
        $krs = Krs::where('nim', $nim)
            ->where('semester', $semester)
            ->with([
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah')
                        ->with([
                            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
                            'khsDetail:kode_khs_detail,kode_krs_detail,nilai_akhir,grade,score',
                        ]);
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::success([
                'mahasiswa' => [],
                'khs' => [],
            ], 'Belum ada data KHS untuk semester ini.');
        }

        // Format data
        $totalSks = 0;
        $totalWeightedScore = 0;

        $khsData = $krs->krsDetail->map(function ($detail, $idx) use (&$totalSks, &$totalWeightedScore) {
            $sks = ($detail->matakuliah->sks_teori ?? 0) + ($detail->matakuliah->sks_praktik ?? 0);

            if ($detail->khsDetail?->score !== null) {
                $totalSks += $sks;
                $totalWeightedScore += $sks * $detail->khsDetail->score;
            }

            return [
                'id' => $idx + 1,
                'code' => $detail->khsDetail ? $detail->khsDetail->toCode() : null,
                'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                'sks_teori' => $detail->matakuliah->sks_teori,
                'sks_praktik' => $detail->matakuliah->sks_praktik,
                'block' => (bool) $detail->matakuliah->block,
                'nilai_akhir' => $detail->khsDetail?->nilai_akhir,
                'grade' => $detail->khsDetail?->grade,
                'score' => $detail->khsDetail?->score,
            ];
        })->values()->toArray();

        $ipkSemester = $totalSks > 0 ? round($totalWeightedScore / $totalSks, 2) : null;

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
            'khs' => $khsData,
            'ringkasan' => [
                'total_sks' => $totalSks,
                'ipk_semester' => $ipkSemester,
            ],
        ];

        return ApiResponse::success($data, 'KHS Mahasiswa berhasil diambil.');
    }

    /**
     * Get all KHS records untuk mahasiswa
     */
    public function getAllKHS(string $nim)
    {
        $krsRecords = Krs::where('nim', $nim)
            ->select('kode_krs', 'nim', 'semester')  // ✅ Include primary key for model hydration
            ->orderBy('semester', 'desc')
            ->get();

        if ($krsRecords->isEmpty()) {
            return ApiResponse::notFound('Tidak ada data KHS untuk mahasiswa ini');
        }

        $data = $krsRecords->map(fn ($item, $idx) => [
            'id' => $idx + 1,
            'code_krs' => $item->toCode(),
            'semester' => $item->semester,
        ])->values()->toArray();

        return ApiResponse::success($data, 'KHS Mahasiswa berhasil diambil.');
    }

    /**
     * Get KHS detail dengan eager loading
     */
    public function getKHSDetail(string $kode_krs)
    {
        $krs = Krs::where('kode_krs', $kode_krs)
            ->with([
                'mahasiswa:nim,nama_mahasiswa,program_studi_kode',
                'mahasiswa.programStudi:kode_program_studi,nama_program_studi',
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah')
                        ->with([
                            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
                            'khsDetail:kode_khs_detail,kode_krs_detail,nilai_akhir,grade,score',
                        ]);
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KHS tidak ditemukan');
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
            'khs' => $krs->krsDetail->map(fn ($detail, $idx) => [
                'id' => $idx + 1,
                'code' => $detail->toCode(),
                'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                'sks_teori' => $detail->matakuliah->sks_teori,
                'sks_praktik' => $detail->matakuliah->sks_praktik,
                'block' => (bool) $detail->matakuliah->block,
                'nilai_akhir' => $detail->khsDetail?->nilai_akhir,
                'grade' => $detail->khsDetail?->grade,
                'score' => $detail->khsDetail?->score,
            ])->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Detail KHS berhasil diambil.');
    }
}

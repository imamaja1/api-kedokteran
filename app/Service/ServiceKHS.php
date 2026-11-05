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

    /**
     * Direct input nilai langsung ke KHS oleh Staff Akademik (Bypass Kaprodi)
     */
    public function directInputKhs(array $payload)
    {
        $codeKrsDetail = $payload['code_krs_detail'] ?? null;
        $krsDetail = null;

        if ($codeKrsDetail) {
            $krsDetail = \App\Models\KrsDetail::findByCode($codeKrsDetail);
        }

        if (! $krsDetail) {
            return ApiResponse::notFound('Data KRS Detail tidak ditemukan.');
        }

        $krsDetail->load(['krs.mahasiswa', 'matakuliah']);
        $prodiKode = $krsDetail->krs?->mahasiswa?->program_studi_kode;

        $nilaiAkhir = isset($payload['nilai_akhir']) ? (float) $payload['nilai_akhir'] : null;
        $grade = $payload['grade'] ?? null;
        $score = isset($payload['score']) ? (float) $payload['score'] : null;

        if ($nilaiAkhir !== null) {
            $serviceGrade = new ServiceGrade();
            $gradeData = $serviceGrade->konversi($nilaiAkhir, $prodiKode);
            $grade = $gradeData['grade'] ?? $grade;
            $score = $gradeData['score'] ?? $score;
        } elseif ($grade !== null) {
            $gradeRecord = \App\Models\GradeNilai::where('grade', strtoupper($grade))->first();
            if ($gradeRecord) {
                $score = (float) $gradeRecord->skor;
                if ($nilaiAkhir === null) {
                    $nilaiAkhir = (float) $gradeRecord->nilai_min;
                }
            } else {
                $standardMap = [
                    'A' => 4.0, 'B+' => 3.5, 'B' => 3.0, 'C+' => 2.5, 'C' => 2.0, 'D' => 1.0, 'E' => 0.0,
                ];
                $score = $standardMap[strtoupper($grade)] ?? 0.0;
            }
        } else {
            return ApiResponse::error('Field nilai_akhir atau grade wajib diisi.', 422);
        }

        $khsDetail = \App\Models\KhsDetail::updateOrCreate(
            ['kode_krs_detail' => $krsDetail->kode_krs_detail],
            [
                'nilai_akhir'  => $nilaiAkhir,
                'grade'        => $grade,
                'score'        => $score,
                'tidak_berhak' => 'A',
            ]
        );

        return ApiResponse::success([
            'kode_krs_detail' => $krsDetail->kode_krs_detail,
            'code_krs_detail' => $krsDetail->toCode(),
            'nim'             => $krsDetail->krs?->nim,
            'nama_mahasiswa'  => $krsDetail->krs?->mahasiswa?->nama_mahasiswa,
            'matakuliah'      => $krsDetail->matakuliah?->nama_matakuliah,
            'nilai_akhir'     => $nilaiAkhir,
            'grade'           => $grade,
            'score'           => $score,
            'status'          => 'berhasil_diterbitkan_khs',
        ], 'Nilai berhasil langsung diterbitkan ke KHS oleh Staff.');
    }
}

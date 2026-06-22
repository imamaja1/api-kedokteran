<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Cache;

class ServicePetikanNilai
{
    private const CACHE_TTL = 3600; // 1 jam

    /**
     * Get petikan nilai untuk mahasiswa berdasarkan NIM (optimized dengan cache)
     * Cache key: petikan_nilai::{nim}
     */
    public function petikan_nilai_by_nim(string $nim, string $kode_prodi)
    {
        $cacheKey = "petikan_nilai::{$nim}::{$kode_prodi}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($nim, $kode_prodi) {
            $angkatan = substr($nim, 0, 2);

            $mahasiswa = Mahasiswa::where('nim', $nim)
                ->select('nim', 'nama_mahasiswa', 'program_studi_kode', 'alamat', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'telepon_orangtua')
                ->with('programStudi:kode_program_studi,nama_program_studi')
                ->first();

            if (! $mahasiswa) {
                return ApiResponse::notFound('Mahasiswa tidak ditemukan');
            }

            $kurikulumAngkatan = KurikulumAngkatan::select(
                'kode_kurikulum_angkatan',
                'angkatan',
                'kode_nama_kurikulum',
            )
                ->with('namaKurikulum:kode_nama_kurikulum,nama_kurikulum,kode_program_studi')
                ->whereRaw('substr(angkatan, 3, 2) = ?', [$angkatan])
                ->whereHas('namaKurikulum', fn ($q) => $q->where('kode_program_studi', $kode_prodi))
                ->first();

            if (! $kurikulumAngkatan) {
                return ApiResponse::notFound('Kurikulum untuk angkatan mahasiswa tidak ditemukan.');
            }

            $kurikulumData = $this->buildKurikulumWithNilai($nim, $kurikulumAngkatan->kode_nama_kurikulum);

            $data = [
                'mahasiswa' => [
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $mahasiswa->programStudi?->nama_program_studi,
                    'alamat' => $mahasiswa->alamat,
                    'tempat_lahir' => $mahasiswa->tempat_lahir,
                    'tanggal_lahir' => $mahasiswa->tanggal_lahir,
                    'telepon' => $mahasiswa->telepon,
                    'telepon_orangtua' => $mahasiswa->telepon_orangtua,
                ],
                'kurikulum' => [
                    'id' => 1,
                    'code' => $kurikulumAngkatan->toCode(),
                    'angkatan' => $kurikulumAngkatan->angkatan,
                    'nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->nama_kurikulum,
                    'code_nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->toCode(),
                ],
                'data_kurikulum' => $kurikulumData['data_kurikulum'],
                'ringkasan' => $kurikulumData['ringkasan'],
            ];

            return ApiResponse::success($data, 'Petikan nilai retrieved successfully.');
        });
    }

    /**
     * Get transkrip nilai lengkap untuk mahasiswa (optimized dengan cache)
     */
    public function getTranskrip(string $nim)
    {
        $cacheKey = "transkrip::{$nim}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($nim) {
            $angkatan = substr($nim, 0, 2);

            $mahasiswa = Mahasiswa::where('nim', $nim)
                ->select('nim', 'nama_mahasiswa', 'program_studi_kode', 'alamat', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'telepon_orangtua')
                ->with('programStudi:kode_program_studi,nama_program_studi')
                ->first();

            if (! $mahasiswa) {
                return ApiResponse::notFound('Mahasiswa tidak ditemukan');
            }

            $kode_prodi = $mahasiswa->program_studi_kode;

            $kurikulumAngkatan = KurikulumAngkatan::select(
                'kode_kurikulum_angkatan',
                'angkatan',
                'kode_nama_kurikulum',
            )
                ->with('namaKurikulum:kode_nama_kurikulum,nama_kurikulum,kode_program_studi')
                ->whereRaw('substr(angkatan, 3, 2) = ?', [$angkatan])
                ->whereHas('namaKurikulum', fn ($q) => $q->where('kode_program_studi', $kode_prodi))
                ->first();

            if (! $kurikulumAngkatan) {
                return ApiResponse::notFound('Kurikulum untuk angkatan mahasiswa tidak ditemukan.');
            }

            $kurikulumData = $this->buildKurikulumWithNilai($nim, $kurikulumAngkatan->kode_nama_kurikulum);

            $data = [
                'mahasiswa' => [
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $mahasiswa->programStudi?->nama_program_studi,
                    'alamat' => $mahasiswa->alamat,
                    'tempat_lahir' => $mahasiswa->tempat_lahir,
                    'tanggal_lahir' => $mahasiswa->tanggal_lahir,
                    'telepon' => $mahasiswa->telepon,
                    'telepon_orangtua' => $mahasiswa->telepon_orangtua,
                ],
                'kurikulum' => [
                    'id' => 1,
                    'code' => $kurikulumAngkatan->toCode(),
                    'angkatan' => $kurikulumAngkatan->angkatan,
                    'nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->nama_kurikulum,
                    'code_nama_kurikulum' => $kurikulumAngkatan->namaKurikulum?->toCode(),
                ],
                'data_kurikulum' => $kurikulumData['data_kurikulum'],
                'ringkasan' => $kurikulumData['ringkasan'],
            ];

            return ApiResponse::success($data, 'Transkrip nilai retrieved successfully.');
        });
    }

    /**
     * Build kurikulum data dengan nilai mahasiswa (optimized)
     * Sebelumnya: 4 heavy joins (krs → krs_detail → khs_detail → matakuliah)
     * Sekarang: 2 queries terpisah + PHP mapping (lebih efisien)
     */
    private function buildKurikulumWithNilai(string $nim, string $kode_nama_kurikulum): array
    {
        $cacheKey = "kurikulum_nilai::{$nim}::{$kode_nama_kurikulum}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($nim, $kode_nama_kurikulum) {
            // Query 1: Ambil nilai mahasiswa (grouped by id_matakuliah)
            $nilaiMap = Krs::where('krs.nim', $nim)
                ->join('krs_detail', 'krs.kode_krs', '=', 'krs_detail.kode_krs')
                ->join('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
                ->select(
                    'krs_detail.id_matakuliah',
                    'krs.semester',
                    'khs_detail.nilai_akhir',
                    'khs_detail.grade',
                    'khs_detail.score',
                )
                ->orderBy('khs_detail.nilai_akhir', 'asc')
                ->get()
                ->groupBy('id_matakuliah');

            // Query 2: Ambil kurikulum matakuliah
            $kurikulumItems = Kurikulum::where('kode_nama_kurikulum', $kode_nama_kurikulum)
                ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block')
                ->select('semester', 'id_matakuliah', 'kode_nama_kurikulum')
                ->orderBy('semester')
                ->get();

            // Hitung IPK keseluruhan (menggunakan nilai tertinggi per matakuliah)
            $totalSks = 0;
            $totalWeightedScore = 0;

            foreach ($nilaiMap as $idMatakuliah => $nilaiList) {
                $tertinggi = $this->getNilaiTertinggi($nilaiList);
                if ($tertinggi && $tertinggi['score'] !== null) {
                    $kurikulumItem = $kurikulumItems->firstWhere('id_matakuliah', $idMatakuliah);
                    if ($kurikulumItem) {
                        $sks = ($kurikulumItem->matakuliah->sks_teori ?? 0) + ($kurikulumItem->matakuliah->sks_praktik ?? 0);
                        $totalSks += $sks;
                        $totalWeightedScore += $sks * $tertinggi['score'];
                    }
                }
            }

            $ipkKeseluruhan = $totalSks > 0 ? round($totalWeightedScore / $totalSks, 2) : null;

            $dataKurikulum = $kurikulumItems
                ->groupBy('semester')
                ->map(function ($items, $sem) use ($nilaiMap) {
                    $total_sks = $items->sum(function ($item) {
                        return ($item->matakuliah->sks_teori ?? 0) + ($item->matakuliah->sks_praktik ?? 0);
                    });

                    return [
                        'id' => $sem,
                        'semester' => $sem,
                        'total_sks' => $total_sks,
                        'matakuliah' => $items->map(function ($item) use ($nilaiMap) {
                            $nilaiList = $nilaiMap->get($item->id_matakuliah, collect());

                            return [
                                'kode_matakuliah' => $item->matakuliah->kode_matakuliah,
                                'nama_matakuliah' => $item->matakuliah->nama_matakuliah,
                                'sks_teori' => $item->matakuliah->sks_teori,
                                'sks_praktik' => $item->matakuliah->sks_praktik,
                                'block' => (bool) $item->matakuliah->block,
                                'nilai' => $nilaiList->map(fn ($n) => [
                                    'semester' => $n->semester,
                                    'nilai_akhir' => $n->nilai_akhir,
                                    'grade' => $n->grade,
                                    'score' => $n->score,
                                ])->values()->toArray(),
                                'nilai_tertinggi' => $this->getNilaiTertinggi($nilaiList),
                            ];
                        })->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return [
                'data_kurikulum' => $dataKurikulum,
                'ringkasan' => [
                    'total_sks' => $totalSks,
                    'ipk' => $ipkKeseluruhan,
                ],
            ];
        });
    }

    /**
     * Ambil nilai tertinggi dari collection nilai
     */
    private function getNilaiTertinggi($nilaiCollection): ?array
    {
        if ($nilaiCollection->isEmpty()) {
            return null;
        }

        $tertinggi = $nilaiCollection->sortByDesc('nilai_akhir')->first();

        return [
            'semester' => $tertinggi->semester,
            'nilai_akhir' => $tertinggi->nilai_akhir,
            'grade' => $tertinggi->grade,
            'score' => $tertinggi->score,
        ];
    }
}

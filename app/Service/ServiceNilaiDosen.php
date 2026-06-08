<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KhsDetail;
use App\Models\KrsDetail;
use App\Models\Mengajar;
use Illuminate\Http\JsonResponse;

class ServiceNilaiDosen
{
    public function getTreePenilaian(int $kode_dosen): JsonResponse
    {
        $kelasList = Mengajar::where('kode_dosen', $kode_dosen)
            ->with([
                'kelas:mengajar_id,kelas_id,nama_kelas_id,semester,kode_tahun_akademik,kode_program_studi,id_matakuliah',
                'kelas.namaKelas:nama_kelas_id,nama_kelas',
                'kelas.matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
                'kelas.tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'kelas.programStudi:kode_program_studi,nama_program_studi',
            ])
            ->orderBy('kelas_id', 'desc')
            ->get();

        $data = $kelasList->map(function ($item) {
            $kelas = $item->kelas;
            $mk = $kelas?->matakuliah;

            return [
                'code_kelas' => $kelas?->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas?->semester,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
                'nama_program_studi' => $kelas->programStudi?->nama_program_studi,
                'kode_matakuliah' => $mk?->kode_matakuliah,
                'nama_matakuliah' => $mk?->nama_matakuliah,
                'block' => (bool) ($mk?->block ?? false),
            ];
        })->values()->toArray();

        return ApiResponse::success($data, 'Tree penilaian retrieved successfully.');
    }

    public function getMahasiswa(string $code_kelas, int $kode_dosen): JsonResponse
    {
        $kelas = Kelas::findByCode($code_kelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kode_dosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $kelasMahasiswa = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
            ->with([
                'krsDetail' => function ($q) {
                    $q->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah')
                      ->with('khsDetail');
                    $q->with(['krs' => function ($q2) {
                        $q2->with('mahasiswa:nim,nama_mahasiswa');
                    }]);
                },
            ])
            ->get();

        $data = $kelasMahasiswa->map(function ($km) {
            $krsDetail = $km->krsDetail;
            $khs = $krsDetail?->khsDetail;
            $krs = $krsDetail?->krs;
            $mahasiswa = $krs?->mahasiswa;

            return [
                'code_mahasiswa' => $mahasiswa?->toCode(),
                'nim' => $mahasiswa?->nim,
                'nama_mahasiswa' => $mahasiswa?->nama_mahasiswa,
                'status_mahasiswa' => $khs?->tidak_berhak == 1 ? 'Tidak Berhak' : 'Aktif',
                'nilai_harian' => $khs?->nilai_harian,
                'nilai_uts' => $khs?->nilai_uts,
                'nilai_uas' => $khs?->nilai_uas,
                'nilai_akhir' => $khs?->nilai_akhir,
            ];
        })->filter()->values()->toArray();

        return ApiResponse::success($data, 'Daftar mahasiswa kelas retrieved successfully.');
    }

    public function inputNilai(array $payload, int $kode_dosen): JsonResponse
    {
        $kelas = Kelas::findByCode($payload['code_kelas']);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kode_dosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $results = [];

        foreach ($payload['mahasiswa'] as $item) {
            $mahasiswa = \App\Models\Mahasiswa::findByCode($item['code_mahasiswa']);
            if (! $mahasiswa) {
                $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Mahasiswa tidak ditemukan'];
                continue;
            }

            $km = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
                ->whereHas('krsDetail.krs', function ($q) use ($mahasiswa) {
                    $q->where('nim', $mahasiswa->nim);
                })
                ->first();

            if (! $km) {
                $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Mahasiswa tidak terdaftar di kelas ini'];
                continue;
            }

            $krsDetail = $km->krsDetail;
            if (! $krsDetail) {
                $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Data KRS detail tidak ditemukan'];
                continue;
            }

            $khs = KhsDetail::where('kode_krs_detail', $krsDetail->kode_krs_detail)->first();

            $nilaiData = [
                'nilai_harian' => $item['nilai_harian'],
                'nilai_uts'    => $item['nilai_uts'],
                'nilai_uas'    => $item['nilai_uas'],
                'nilai_akhir'  => $item['nilai_akhir'],
                'tidak_berhak' => $item['tidak_berhak'] ?? 0,
            ];

            if ($khs) {
                $khs->update($nilaiData);
            } else {
                KhsDetail::create(array_merge($nilaiData, [
                    'kode_krs_detail' => $krsDetail->kode_krs_detail,
                ]));
            }

            $results[] = [
                'code_mahasiswa' => $mahasiswa->toCode(),
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'status' => 'Berhasil',
            ];
        }

        return ApiResponse::success($results, 'Input nilai selesai diproses.');
    }
}

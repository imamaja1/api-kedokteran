<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Kelas;
use App\Models\Mengajar;
use Illuminate\Http\JsonResponse;

class ServiceMengajar
{
    public function getKelasDosen(int $kode_dosen, int $perPage = 20): JsonResponse
    {
        $paginator = Mengajar::where('kode_dosen', $kode_dosen)
            ->with([
                'kelas:mengajar_id,kelas_id,nama_kelas_id,semester,kode_tahun_akademik,kode_program_studi,id_matakuliah',
                'kelas.namaKelas:nama_kelas_id,nama_kelas',
                'kelas.matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
                'kelas.tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'kelas.programStudi:kode_program_studi,nama_program_studi',
            ])
            ->orderBy('kelas_id', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            $kelas = $item->kelas;
            $mk = $kelas?->matakuliah;

            return [
                'code_mengajar' => $item->toCode(),
                'code_kelas' => $kelas?->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas?->semester,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
                'nama_program_studi' => $kelas->programStudi?->nama_program_studi,
                'kode_matakuliah' => $mk?->kode_matakuliah,
                'nama_matakuliah' => $mk?->nama_matakuliah,
                'sks_teori' => $mk?->sks_teori,
                'sks_praktik' => $mk?->sks_praktik,
                'block' => (bool) ($mk?->block ?? false),
            ];
        });

        return ApiResponse::paginated($paginator, 'Daftar kelas dosen retrieved successfully.');
    }

    public function getDetailKelas(string $code_kelas, int $kode_dosen): JsonResponse
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

        $kelas->load([
            'namaKelas:nama_kelas_id,nama_kelas',
            'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
            'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            'programStudi:kode_program_studi,nama_program_studi',
            'kelasMahasiswa' => function ($q) {
                $q->with([
                    'krsDetail' => function ($q2) {
                        $q2->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                    },
                ]);
            },
        ]);

        $kelas->load(['kelasMahasiswa.krsDetail.krs.mahasiswa:nim,nama_mahasiswa']);

        $mk = $kelas->matakuliah;
        $data = [
            'code_kelas' => $kelas->toCode(),
            'nama_kelas' => $kelas->namaKelas?->nama_kelas,
            'semester' => $kelas->semester,
            'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
            'nama_program_studi' => $kelas->programStudi?->nama_program_studi,
            'kode_matakuliah' => $mk?->kode_matakuliah,
            'nama_matakuliah' => $mk?->nama_matakuliah,
            'sks_teori' => $mk?->sks_teori,
            'sks_praktik' => $mk?->sks_praktik,
            'block' => (bool) ($mk?->block ?? false),
            'mahasiswa' => $kelas->kelasMahasiswa->map(function ($km) {
                $krsDetail = $km->krsDetail;
                $krs = $krsDetail?->krs;
                $mahasiswa = $krs?->mahasiswa;

                return [
                    'code_mahasiswa' => $mahasiswa?->toCode(),
                    'nim' => $mahasiswa?->nim,
                    'nama_mahasiswa' => $mahasiswa?->nama_mahasiswa,
                ];
            })->filter()->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Detail kelas retrieved successfully.');
    }
}

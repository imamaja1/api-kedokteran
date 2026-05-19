<?php

namespace App\Service;

use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;

class ServiceKRS
{
    public function getKRSMhs(string $nim, ?int $semester = null)
    {
        if ($semester === null) {
            $latestKrs = Krs::where('nim', $nim)->orderBy('semester', 'desc')->first();
            if (! $latestKrs) {
                return response()->json([
                    'status' => false,
                    'message' => 'Belum ada data KRS.',
                    'data' => ['mahasiswa' => [], 'krs' => []],
                ]);
            }
            $semester = $latestKrs->semester;
        }

        $data['mahasiswa'] = Mahasiswa::join('program_studi', 'program_studi.kode_program_studi', '=', 'mahasiswa.program_studi_kode')
            ->join('krs', 'krs.nim', '=', 'mahasiswa.nim')
            ->where('krs.semester', $semester)
            ->where('mahasiswa.nim', $nim)
            ->select(
                'mahasiswa.nim',
                'nama_mahasiswa',
                'nama_program_studi',
                'alamat',
                'tempat_lahir',
                'tanggal_lahir',
                'telepon',
                'telepon_orangtua',
                'krs.semester',
            )->get();

        $data['krs'] = Krs::join('tahun_akademik', 'krs.kode_tahun_akademik', '=', 'tahun_akademik.kode_tahun_akademik')
            ->join('krs_detail', 'krs.kode_krs', '=', 'krs_detail.kode_krs')
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->where('krs.nim', $nim)
            ->where('tahun_akademik.semester', $semester)
            ->select(
                'krs_detail.kode_krs_detail as id',
                'krs_detail.kode_krs_detail as kode',
                'matakuliah.kode_matakuliah',
                'matakuliah.nama_matakuliah',
                'matakuliah.sks_teori',
                'matakuliah.sks_praktik',
                'matakuliah.block',
            )
            ->get()
            ->map(function ($item, $nomor) {
                $item->id = $nomor + 1;
                $item->kode = Crypt::encryptString($item->kode);
                return $item;
            });

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa',
            'data' => $data,
        ]);
    }

    public function getAllKRS(string $nim)
    {
        $data = Krs::where('nim', $nim)
            ->get()
            ->map(fn ($item, $nomor) => [
                'id' => $nomor + 1,
                'code_krs' => Crypt::encryptString($item->kode_krs),
                'semester' => $item->semester,
            ])
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function getKRSDetail(string $kode_krs)
    {
        $data['mahasiswa'] = Mahasiswa::join('program_studi', 'program_studi.kode_program_studi', '=', 'mahasiswa.program_studi_kode')
            ->join('krs', 'krs.nim', '=', 'mahasiswa.nim')
            ->where('krs.kode_krs', $kode_krs)
            ->select(
                'mahasiswa.nim',
                'nama_mahasiswa',
                'nama_program_studi',
                'alamat',
                'tempat_lahir',
                'tanggal_lahir',
                'telepon',
                'telepon_orangtua',
                'krs.semester',
            )->get();

        $data['krs'] = Krs::join('tahun_akademik', 'krs.kode_tahun_akademik', '=', 'tahun_akademik.kode_tahun_akademik')
            ->join('krs_detail', 'krs.kode_krs', '=', 'krs_detail.kode_krs')
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->where('krs.kode_krs', $kode_krs)
            ->select(
                'krs.semester',
                'matakuliah.kode_matakuliah',
                'matakuliah.nama_matakuliah',
                'matakuliah.sks_teori',
                'matakuliah.sks_praktik',
                'matakuliah.block',
            )
            ->get()
            ->groupBy('semester')
            ->map(fn ($items, $sem) => [
                'semester' => $sem,
                'matakuliah' => $items->map(fn ($item) => [
                    'kode_matakuliah' => $item->kode_matakuliah,
                    'nama_matakuliah' => $item->nama_matakuliah,
                    'sks_teori' => $item->sks_teori,
                    'sks_praktik' => $item->sks_praktik,
                    'block' => (bool) $item->block,
                ]),
            ])
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa retrieved successfully.',
            'data' => $data,
        ]);
    }
}

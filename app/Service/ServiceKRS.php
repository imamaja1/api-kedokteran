<?php

namespace App\Service;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;

class ServiceKRS
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getKRSMhs($nim, $semester = null)
    {
        if ($semester === null) {
            $semester = Krs::where('nim', $nim)->orderBy('semester', 'desc')->first()->semester;
        }
        $data['mahasiswa'] = Mahasiswa::join('program_studi', 'program_studi.kode_program_studi', '=', 'mahasiswa.program_studi_kode')
                                ->join('krs', 'krs.nim', '=', 'mahasiswa.nim')
                                ->where('krs.semester', $semester)
                                ->where('mahasiswa.nim', $nim)->select(
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
                                'block'
                            )
                            ->get()
                            ->map(function ($item) {
                                $item->kode = Crypt::encryptString($item->kode);
                                return $item;
                            });

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa',
            'data' => $data
        ]);
    }
}

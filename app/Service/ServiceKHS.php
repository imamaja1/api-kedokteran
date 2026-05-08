<?php

namespace App\Service;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;

class ServiceKHS
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getKHSMhs($nim, $semester = null)
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
                    ->join('krs_detail','krs.kode_krs','=','krs_detail.kode_krs')
                    ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
                    ->join('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
                    ->where('krs.nim', $nim)
                    ->where('tahun_akademik.semester', $semester)
                    ->select(
                        'khs_detail.kode_khs_detail as id',
                        'khs_detail.kode_khs_detail as code',
                        'kode_matakuliah',
                        'nama_matakuliah',
                        'sks_teori',
                        'sks_praktik',
                        'khs_detail.nilai_akhir',
                    )
                    ->get()
                    ->map(function ($item,$nomor) {
                        $item->id = $nomor + 1;
                        $item->code = Crypt::encryptString($item->code);
                        return $item;
                    });

        return response()->json([
            'status' => true,
            'message' => 'KHS Mahasiswa',
            'data' => $data
        ]);
    }

    public function getAllKHS(){
        $data = Krs::all()
            ->map(fn($items, $nomor) => [
                'id' => $nomor + 1,
                'code_krs' => Crypt::encryptString($items->kode_krs),
                'semester'   => $items->semester,
            ])
            ->values(); 

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa retrieved successfully.',
            'data' => $data
        ]);
    }

    public function getKHSDetail($kode_krs){
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
                            ->join('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
                            ->where('krs.kode_krs', $kode_krs)
                            ->select('*')
                            ->get()
                            ->groupBy('semester')
                            ->map(fn($items, $sem) => [
                                'semester'   => $sem,
                                'matakuliah' => $items->map(fn($item) => [
                                    'kode_matakuliah' => $item->kode_matakuliah,
                                    'nama_matakuliah' => $item->nama_matakuliah,
                                    'sks_teori' => $item->sks_teori,
                                    'sks_praktik' => $item->sks_praktik,
                                    'block' => $item->block == 1 ? true : false,
                                    'nilai' => $item->nilai
                                ]),
                            ])
                            ->values();

        return response()->json([
            'status' => true,
            'message' => 'KRS Mahasiswa retrieved successfully.',
            'data' => $data
        ]);
    }
}

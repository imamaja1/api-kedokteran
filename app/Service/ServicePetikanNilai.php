<?php

namespace App\Service;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Crypt;

class ServicePetikanNilai
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function petikan_nilai_by_nim($nim, $kode_prodi)
    {
        $angkatan = substr((string) $nim, 0, 2);
        $data['mahasiswa'] = Mahasiswa::join('program_studi', 'program_studi.kode_program_studi', '=', 'mahasiswa.program_studi_kode')
                                ->where('mahasiswa.nim', $nim)->select(
                                    'mahasiswa.nim',
                                    'nama_mahasiswa',
                                    'nama_program_studi',
                                    'alamat',
                                    'tempat_lahir',
                                    'tanggal_lahir',
                                    'telepon',
                                    'telepon_orangtua'
                                )->get();
        $data['kurikulum'] = KurikulumAngkatan::select('kode_kurikulum_angkatan as id','kurikulum_angkatan.angkatan','nama_kurikulum.nama_kurikulum','nama_kurikulum.kode_nama_kurikulum')
                        ->join('nama_kurikulum', 'kurikulum_angkatan.kode_nama_kurikulum', '=', 'nama_kurikulum.kode_nama_kurikulum')
                        ->whereRaw('substr(angkatan, 3, 2) = ?', $angkatan)
                        ->where('nama_kurikulum.kode_program_studi', $kode_prodi)
                        ->first();
                        
        $nilaiMap = Krs::select('krs_detail.kode_krs_detail as id','krs_detail.id_matakuliah', 'krs.semester', 'khs_detail.nilai_akhir')
                        ->join('krs_detail', 'krs.kode_krs', '=', 'krs_detail.kode_krs')
                        ->join('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
                        ->where('krs.nim', $nim)
                        ->orderBy('khs_detail.nilai_akhir', 'asc')
                        ->get()
                        ->groupBy('id_matakuliah');

        $data['data_kurikulum'] = Kurikulum::join('matakuliah', 'kurikulum.id_matakuliah', '=', 'matakuliah.id_matakuliah')
                                ->where('kode_nama_kurikulum', $data['kurikulum']->kode_nama_kurikulum)
                                ->select('kurikulum.semester', 'matakuliah.*')
                                ->selectRaw('(COALESCE(matakuliah.sks_teori, 0) + COALESCE(matakuliah.sks_praktik, 0)) as sks')
                                ->orderBy('kurikulum.semester')
                                ->get()
                                ->groupBy('semester')
                                ->map(fn($items, $sem) => [
                                    'id' => $sem,
                                    'semester'   => $sem,
                                    'total_sks'  => $items->sum('sks'),
                                    'matakuliah' => $items->map(fn($item) => [
                                        'kode_matakuliah' => $item->kode_matakuliah,
                                        'nama_matakuliah' => $item->nama_matakuliah,
                                        'sks_teori'       => $item->sks_teori,
                                        'sks_praktik'     => $item->sks_praktik,
                                        'nilai' => $nilaiMap->get($item->id_matakuliah, collect())->values(),
                                    ]),
                                ])
                                ->values();

        return response()->json([
            'status' => true,
            'message' => 'Petikan nilai retrieved successfully.',
            'data' => $data
        ]);
    }
}

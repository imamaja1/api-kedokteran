<?php

namespace App\Service;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;

class ServiceKurikulum
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function kurikulum_by_nim($nim, $kode_prodi)
    {
        $angkatan = substr((string) $nim, 0, 2);
        $data['kurikulum'] = KurikulumAngkatan::select('kurikulum_angkatan.angkatan','nama_kurikulum.nama_kurikulum','nama_kurikulum.kode_nama_kurikulum')
                        ->join('nama_kurikulum', 'kurikulum_angkatan.kode_nama_kurikulum', '=', 'nama_kurikulum.kode_nama_kurikulum')
                        ->whereRaw('substr(angkatan, 3, 2) = ?', $angkatan)
                        ->where('nama_kurikulum.kode_program_studi', $kode_prodi)
                        ->first();
        $data['data_kurikulum'] = Kurikulum::join('matakuliah', 'kurikulum.id_matakuliah', '=', 'matakuliah.id_matakuliah')
                                ->where('kode_nama_kurikulum', $data['kurikulum']->kode_nama_kurikulum)
                                ->select('kurikulum.semester', 'matakuliah.*')
                                ->selectRaw('(COALESCE(matakuliah.sks_teori, 0) + COALESCE(matakuliah.sks_praktik, 0)) as sks')
                                ->orderBy('kurikulum.semester')
                                ->get()
                                ->groupBy('semester')
                                ->map(fn($items, $sem) => [
                                    'semester'   => $sem,
                                    'total_sks'  => $items->sum('sks'),
                                    'matakuliah' => $items->map(fn($item) => [
                                        'kode_matakuliah' => $item->kode_matakuliah,
                                        'nama_matakuliah' => $item->nama_matakuliah,
                                        'sks_teori' => $item->sks_teori,
                                        'sks_praktik' => $item->sks_praktik,
                                        'block' => $item->block == 1 ? true : false,
                                    ]),
                                ])
                                ->values();

        return response()->json([
            'status' => true,
            'message' => 'Kurikulum Mahasiswa retrieved successfully.',
            'data' => $data
        ]);
    }
}

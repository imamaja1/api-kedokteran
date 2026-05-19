<?php

namespace App\Service;

use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\NamaKurikulum;
use Illuminate\Support\Facades\Crypt;

class ServiceKurikulum
{
    public function kurikulum_by_nim(string $nim, string $kode_prodi)
    {
        $angkatan = substr((string) $nim, 0, 2);

        $kurikulumAngkatan = KurikulumAngkatan::select(
            'kurikulum_angkatan.angkatan',
            'nama_kurikulum.nama_kurikulum',
            'nama_kurikulum.kode_nama_kurikulum',
        )
            ->join('nama_kurikulum', 'kurikulum_angkatan.kode_nama_kurikulum', '=', 'nama_kurikulum.kode_nama_kurikulum')
            ->whereRaw('substr(angkatan, 3, 2) = ?', [$angkatan])
            ->where('nama_kurikulum.kode_program_studi', $kode_prodi)
            ->first();

        if (! $kurikulumAngkatan) {
            return response()->json([
                'status' => false,
                'message' => 'Kurikulum untuk angkatan mahasiswa tidak ditemukan.',
                'data' => ['kurikulum' => null, 'data_kurikulum' => []],
            ]);
        }

        $data['kurikulum'] = $kurikulumAngkatan;

        $data['data_kurikulum'] = Kurikulum::join('matakuliah', 'kurikulum.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->where('kode_nama_kurikulum', $kurikulumAngkatan->kode_nama_kurikulum)
            ->select('kurikulum.semester', 'matakuliah.*')
            ->selectRaw('(COALESCE(matakuliah.sks_teori, 0) + COALESCE(matakuliah.sks_praktik, 0)) as sks')
            ->orderBy('kurikulum.semester')
            ->get()
            ->groupBy('semester')
            ->map(fn ($items, $sem) => [
                'semester' => $sem,
                'total_sks' => $items->sum('sks'),
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
            'message' => 'Kurikulum Mahasiswa retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function nama_kurikulum()
    {
        $data = NamaKurikulum::with('programStudi')
            ->get()
            ->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
                    'code_nama_kurikulum' => Crypt::encryptString($item->kode_nama_kurikulum),
                    'nama_kurikulum' => $item->nama_kurikulum,
                    'nama_program_studi' => $item->programStudi?->nama_program_studi,
                    'angkatan1' => $item->angkatan1,
                    'ekstensi1' => $item->ekstensi1,
                    'paket1' => $item->paket1,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Nama Kurikulum retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function getOneNamaKurikulum(string $id)
    {
        $item = NamaKurikulum::find($id);

        if (! $item) {
            return response()->json([
                'status' => false,
                'message' => 'Nama Kurikulum tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Nama Kurikulum retrieved successfully.',
            'data' => [
                'code_nama_kurikulum' => Crypt::encryptString($item->kode_nama_kurikulum),
                'nama_kurikulum' => $item->nama_kurikulum,
                'kode_program_studi' => $item->kode_program_studi,
                'angkatan1' => $item->angkatan1,
                'ekstensi1' => $item->ekstensi1,
                'paket1' => $item->paket1,
            ],
        ]);
    }

    public function storeNamaKurikulum(array $object)
    {
        try {
            $namaKurikulum = NamaKurikulum::create($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Nama Kurikulum',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Nama Kurikulum berhasil dibuat',
            'data' => [
                'code_nama_kurikulum' => Crypt::encryptString($namaKurikulum->kode_nama_kurikulum),
                'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
                'kode_program_studi' => $namaKurikulum->kode_program_studi,
                'angkatan1' => $namaKurikulum->angkatan1,
                'ekstensi1' => $namaKurikulum->ekstensi1,
                'paket1' => $namaKurikulum->paket1,
            ],
        ], 201);
    }

    public function updateNamaKurikulum(string $id, array $object)
    {
        $namaKurikulum = NamaKurikulum::find($id);

        if (! $namaKurikulum) {
            return response()->json([
                'status' => false,
                'message' => 'Nama Kurikulum tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $namaKurikulum->update($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Nama Kurikulum',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Nama Kurikulum berhasil diperbarui',
            'data' => [
                'code_nama_kurikulum' => Crypt::encryptString($namaKurikulum->kode_nama_kurikulum),
                'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
                'kode_program_studi' => $namaKurikulum->kode_program_studi,
                'angkatan1' => $namaKurikulum->angkatan1,
                'ekstensi1' => $namaKurikulum->ekstensi1,
                'paket1' => $namaKurikulum->paket1,
            ],
        ]);
    }

    public function deleteNamaKurikulum(string $id)
    {
        $namaKurikulum = NamaKurikulum::find($id);

        if (! $namaKurikulum) {
            return response()->json([
                'status' => false,
                'message' => 'Nama Kurikulum tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $namaKurikulum->delete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Nama Kurikulum',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Nama Kurikulum berhasil dihapus',
            'data' => [
                'code_nama_kurikulum' => Crypt::encryptString($namaKurikulum->kode_nama_kurikulum),
                'nama_kurikulum' => $namaKurikulum->nama_kurikulum,
                'kode_program_studi' => $namaKurikulum->kode_program_studi,
                'angkatan1' => $namaKurikulum->angkatan1,
                'ekstensi1' => $namaKurikulum->ekstensi1,
                'paket1' => $namaKurikulum->paket1,
            ],
        ]);
    }

    public function kurikulum_by_nama_kurikulum(string $kode_nama_kurikulum)
    {
        $data = Kurikulum::join('matakuliah', 'kurikulum.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->where('kode_nama_kurikulum', $kode_nama_kurikulum)
            ->select('kurikulum.semester', 'matakuliah.*')
            ->selectRaw('(COALESCE(matakuliah.sks_teori, 0) + COALESCE(matakuliah.sks_praktik, 0)) as sks')
            ->orderBy('kurikulum.semester')
            ->get()
            ->groupBy('semester')
            ->map(fn ($items, $sem) => [
                'semester' => $sem,
                'total_sks' => $items->sum('sks'),
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
            'message' => 'Kurikulum retrieved successfully.',
            'data' => $data,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuanganSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'kode_ruangan' => 'RK.01',
                'nama_ruangan' => 'Ruang Kuliah 01',
                'kapasitas' => 40,
                'jenis' => 'kuliah',
                'lokasi' => 'Gedung Kedokteran Lt. 1',
                'status_aktif' => true,
            ],
            [
                'kode_ruangan' => 'RK.02',
                'nama_ruangan' => 'Ruang Kuliah 02',
                'kapasitas' => 50,
                'jenis' => 'kuliah',
                'lokasi' => 'Gedung Kedokteran Lt. 1',
                'status_aktif' => true,
            ],
            [
                'kode_ruangan' => 'LAB.KMED',
                'nama_ruangan' => 'Lab Keterampilan Medik (CSL)',
                'kapasitas' => 30,
                'jenis' => 'skill_lab',
                'lokasi' => 'Gedung Kedokteran Lt. 2',
                'status_aktif' => true,
            ],
            [
                'kode_ruangan' => 'LAB.ANAT',
                'nama_ruangan' => 'Lab Anatomi & Biomedik',
                'kapasitas' => 35,
                'jenis' => 'laboratorium',
                'lokasi' => 'Gedung Kedokteran Lt. 2',
                'status_aktif' => true,
            ],
            [
                'kode_ruangan' => 'R.TUT.01',
                'nama_ruangan' => 'Ruang Tutorial PBL 01',
                'kapasitas' => 20,
                'jenis' => 'tutorial',
                'lokasi' => 'Gedung Kedokteran Lt. 3',
                'status_aktif' => true,
            ],
        ];

        foreach ($rooms as $room) {
            DB::table('ruangan')->updateOrInsert(
                ['kode_ruangan' => $room['kode_ruangan']],
                array_merge($room, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}

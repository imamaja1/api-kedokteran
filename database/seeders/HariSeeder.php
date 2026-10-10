<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HariSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            ['nama_hari' => 'Senin', 'urutan' => 1, 'status_aktif' => true],
            ['nama_hari' => 'Selasa', 'urutan' => 2, 'status_aktif' => true],
            ['nama_hari' => 'Rabu', 'urutan' => 3, 'status_aktif' => true],
            ['nama_hari' => 'Kamis', 'urutan' => 4, 'status_aktif' => true],
            ['nama_hari' => 'Jumat', 'urutan' => 5, 'status_aktif' => true],
            ['nama_hari' => 'Sabtu', 'urutan' => 6, 'status_aktif' => true],
            ['nama_hari' => 'Minggu', 'urutan' => 7, 'status_aktif' => false],
        ];

        foreach ($days as $day) {
            DB::table('hari')->updateOrInsert(
                ['nama_hari' => $day['nama_hari']],
                array_merge($day, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}

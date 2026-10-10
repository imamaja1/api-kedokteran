<?php

namespace Database\Seeders;

use App\Models\Hari;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class JadwalKelasSampleSeeder extends Seeder
{
    public function run(): void
    {
        $rk01 = Ruangan::where('kode_ruangan', 'RK.01')->first();
        $rk02 = Ruangan::where('kode_ruangan', 'RK.02')->first();
        $labKmed = Ruangan::where('kode_ruangan', 'LAB.KMED')->first();
        $labAnat = Ruangan::where('kode_ruangan', 'LAB.ANAT')->first();

        $senin = Hari::where('nama_hari', 'Senin')->first();
        $selasa = Hari::where('nama_hari', 'Selasa')->first();
        $rabu = Hari::where('nama_hari', 'Rabu')->first();
        $kamis = Hari::where('nama_hari', 'Kamis')->first();

        if (! $rk01 || ! $senin) {
            return;
        }

        $sampleSchedules = [
            // Kelas 11605: Pancasila -> Senin 08:00 - 09:40 di RK.01
            [
                'kelas_id' => 11605,
                'ruangan_id' => $rk01->id,
                'hari_id' => $senin->id,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:40:00',
                'keterangan' => 'Kuliah Tatap Muka',
            ],
            // Kelas 11606: Bahasa Indonesia -> Senin 10:00 - 11:40 di RK.01
            [
                'kelas_id' => 11606,
                'ruangan_id' => $rk01->id,
                'hari_id' => $senin->id,
                'jam_mulai' => '10:00:00',
                'jam_selesai' => '11:40:00',
                'keterangan' => 'Kuliah Tatap Muka',
            ],
            // Kelas 11607: Keterampilan Medik I -> Selasa 08:00 - 10:30 di LAB.KMED
            [
                'kelas_id' => 11607,
                'ruangan_id' => $labKmed?->id ?? $rk01->id,
                'hari_id' => $selasa->id,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:30:00',
                'keterangan' => 'Praktikum CSL',
            ],
            // Kelas 11608: Blok Keterampilan Belajar -> Rabu 08:00 - 09:40 di RK.02
            [
                'kelas_id' => 11608,
                'ruangan_id' => $rk02?->id ?? $rk01->id,
                'hari_id' => $rabu->id,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:40:00',
                'keterangan' => 'Kuliah Pengantar Blok',
            ],
            // Kelas 11609: Blok Biomedik 1 -> Kamis 08:00 - 10:30 di LAB.ANAT
            [
                'kelas_id' => 11609,
                'ruangan_id' => $labAnat?->id ?? $rk01->id,
                'hari_id' => $kamis->id,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:30:00',
                'keterangan' => 'Praktikum Anatomi & Biomedik',
            ],
            // 11610 sengaja tidak dijadwalkan agar berstatus belum_terjadwal
        ];

        foreach ($sampleSchedules as $item) {
            $kelas = Kelas::find($item['kelas_id']);
            if ($kelas) {
                JadwalKelas::updateOrCreate(
                    [
                        'kelas_id' => $item['kelas_id'],
                        'ruangan_id' => $item['ruangan_id'],
                        'hari_id' => $item['hari_id'],
                        'jam_mulai' => $item['jam_mulai'],
                    ],
                    [
                        'kode_tahun_akademik' => $kelas->kode_tahun_akademik,
                        'jam_selesai' => $item['jam_selesai'],
                        'keterangan' => $item['keterangan'],
                    ]
                );
            }
        }
    }
}

<?php

namespace App\Service;

use App\Models\TahunAkademik;

class ServiceSemester
{
    /**
     * Hitung semester mahasiswa (1-14) berdasarkan NIM dan TA aktif.
     *
     * NIM: "2310xxx" → angkatan 2023
     * TA: "2025/2026" semester 1 (ganjil)
     * Formula: (tahunSekarang - (2000 + angkatan)) × 2 + semesterTa
     * Contoh: (2025 - 2023) × 2 + 1 = 5
     */
    public function hitung(string $nim, ?TahunAkademik $ta = null): int
    {
        $ta = $ta ?? TahunAkademik::active()->first();

        $angkatan = (int) substr($nim, 0, 2);
        $tahunSekarang = (int) explode('/', $ta->tahun_akademik)[0];
        $semesterTa = (int) $ta->semester;

        $semester = (($tahunSekarang - (2000 + $angkatan)) * 2) + $semesterTa;

        return max(1, min(14, $semester));
    }

    /**
     * Cek apakah semester ganjil (1,3,5,7,9,11,13)
     */
    public function isGanjil(int $semester): bool
    {
        return $semester % 2 === 1;
    }

    /**
     * Cek apakah semester genap (2,4,6,8,10,12,14)
     */
    public function isGenap(int $semester): bool
    {
        return $semester % 2 === 0;
    }
}

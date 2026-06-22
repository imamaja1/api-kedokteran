<?php

namespace App\Service;

use App\Models\Grade;

class ServiceGrade
{
    /**
     * Konversi nilai_akhir → grade + score berdasarkan kode_program_studi
     */
    public function konversi(float $nilai, ?int $kodeProdi = null): ?array
    {
        $grade = Grade::cariGrade($nilai, $kodeProdi);

        if (! $grade) {
            return null;
        }

        return [
            'grade' => $grade->huruf,
            'score' => $grade->skor,
        ];
    }
}

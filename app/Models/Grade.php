<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $table = 'grade';

    protected $fillable = ['nilai_min', 'nilai_max', 'huruf', 'skor', 'kode_program_studi'];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'kode_program_studi');
    }

    /**
     * Cari grade berdasarkan nilai_akhir dan kode_program_studi
     */
    public static function cariGrade(float $nilai, ?int $kodeProdi = null): ?self
    {
        $query = static::where('nilai_min', '<=', $nilai)
            ->where('nilai_max', '>=', $nilai);

        if ($kodeProdi) {
            $grade = (clone $query)->where('kode_program_studi', $kodeProdi)->first();
            if ($grade) {
                return $grade;
            }
        }

        return $query->whereNull('kode_program_studi')->first();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentDosen extends Model
{
    protected $table = 'assessment_dosen';

    protected $fillable = [
        'template_id',
        'kelas_id',
        'status',
        'tanggal_buka',
        'tanggal_tutup',
    ];

    protected $casts = [
        'tanggal_buka'  => 'date',
        'tanggal_tutup' => 'date',
    ];

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    public function assignments()
    {
        return $this->hasMany(AssessmentDosenAssignment::class, 'assessment_dosen_id');
    }

    public function nodeValidations()
    {
        return $this->hasMany(AssessmentNodeValidation::class, 'assessment_dosen_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Cek apakah periode penilaian sedang buka.
     * Prioritas: override kelas > default TA > selalu buka.
     */
    public function isOpen(): bool
    {
        $now = now();
        $buka  = null;
        $tutup = null;

        // 1. Override di assessment_dosen (per kelas)
        if ($this->tanggal_buka && $this->tanggal_tutup) {
            $buka  = $this->tanggal_buka;
            $tutup = $this->tanggal_tutup;
        }
        // 2. Default tahun akademik
        elseif ($this->kelas?->tahunAkademik?->tanggal_buka_penilaian) {
            $buka  = $this->kelas->tahunAkademik->tanggal_buka_penilaian;
            $tutup = $this->kelas->tahunAkademik->tanggal_tutup_penilaian;
        }
        // 3. Tidak ada jadwal = selalu buka
        else {
            return true;
        }

        return $buka && $tutup && $now->gte($buka) && $now->lte($tutup);
    }
}

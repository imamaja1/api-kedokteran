<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id_matakuliah',
        'kode_kurikulum_angkatan',
        'versi',
        'structure',
        'is_active',
    ];

    protected $casts = [
        'structure' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah', 'id_matakuliah');
    }

    public function kurikulumAngkatan()
    {
        return $this->belongsTo(KurikulumAngkatan::class, 'kode_kurikulum_angkatan', 'kode_kurikulum_angkatan');
    }

    public function nodeIndexes()
    {
        return $this->hasMany(AssessmentNodeIndex::class, 'template_id');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentScore::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMatakuliah($query, $idMatakuliah)
    {
        return $query->where('id_matakuliah', $idMatakuliah);
    }

    public function scopeByKurikulumAngkatan($query, $kodeKurikulumAngkatan)
    {
        return $query->where('kode_kurikulum_angkatan', $kodeKurikulumAngkatan);
    }
}

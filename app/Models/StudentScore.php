<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentScore extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'template_id',
        'nim',
        'node_key',
        'score',
        'assessor_id',
        'notes',
        'dosen_kode_dosen',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'assessor_id' => 'integer',
        'dosen_kode_dosen' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function student()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function dosenInput()
    {
        return $this->belongsTo(Dosen::class, 'dosen_kode_dosen', 'kode_dosen');
    }

    public function scopeByStudent($query, $nim)
    {
        return $query->where('nim', $nim);
    }

    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }
}

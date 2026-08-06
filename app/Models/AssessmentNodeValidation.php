<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentNodeValidation extends Model
{
    protected $table = 'assessment_node_validations';

    protected $fillable = [
        'assessment_dosen_id',
        'node_key',
        'nim',
        'status',
        'validated_by',
        'validated_at',
        'catatan',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function assessmentDosen()
    {
        return $this->belongsTo(AssessmentDosen::class, 'assessment_dosen_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function validator()
    {
        return $this->belongsTo(Dosen::class, 'validated_by', 'kode_dosen');
    }
}

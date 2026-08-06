<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentDosenAssignment extends Model
{
    protected $table = 'assessment_dosen_assignments';

    protected $fillable = [
        'assessment_dosen_id',
        'node_key',
        'kode_dosen',
    ];

    public function assessmentDosen()
    {
        return $this->belongsTo(AssessmentDosen::class, 'assessment_dosen_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen', 'kode_dosen');
    }
}

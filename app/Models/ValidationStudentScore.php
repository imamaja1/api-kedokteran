<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidationStudentScore extends Model
{
    protected $table = 'validation_student_scores';

    protected $fillable = [
        'template_id',
        'nim',
        'status',
        'validated_by',
        'validated_at',
        'catatan_validasi',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id', 'id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function validator()
    {
        return $this->belongsTo(Dosen::class, 'validated_by', 'kode_dosen');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentScore::class, 'template_id', 'template_id')
            ->where('nim', $this->nim);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianStatus extends Model
{
    protected $table = 'penilaian_status';

    protected $fillable = [
        'kelas_id', 'nim', 'template_id', 'status',
        'dosen_input_by', 'kaprodi_validated_by', 'validated_at',
        'catatan_dosen', 'catatan_kaprodi',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function dosenInput()
    {
        return $this->belongsTo(Dosen::class, 'dosen_input_by', 'kode_dosen');
    }

    public function kaprodiValidator()
    {
        return $this->belongsTo(Dosen::class, 'kaprodi_validated_by', 'kode_dosen');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasMahasiswa extends Model
{
    protected $table = 'kelas_mahasiswa';
    protected $primaryKey = 'kelas_mahasiswa_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['kelas_mahasiswa_id', 'kode_krs_detail', 'kelas_id'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    public function krsDetail()
    {
        return $this->belongsTo(KrsDetail::class, 'kode_krs_detail', 'kode_krs_detail');
    }
}

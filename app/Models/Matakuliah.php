<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matakuliah extends Model
{
    protected $table = 'matakuliah';
    protected $primaryKey = 'id_matakuliah';

    protected $fillable = [
        'kode_matakuliah', 'nama_matakuliah', 'jenis',
        'sks_teori', 'sks_praktek', 'sks_praktikum',
        'kode_pengguna', 'kode_kompetensi', 'kode_program_studi',
        'param1', 'block',
    ];

    public function kurikulum()
    {
        return $this->hasMany(Kurikulum::class, 'id_matakuliah', 'id_matakuliah');
    }

    public function krsDetail()
    {
        return $this->hasMany(KrsDetail::class, 'id_matakuliah', 'id_matakuliah');
    }
}

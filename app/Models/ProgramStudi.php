<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class ProgramStudi extends Model
{
    use HasCode;
    protected $table = 'program_studi';
    protected $primaryKey = 'kode_program_studi';

    protected $fillable = [
        'id_jurusan', 'id_jenjang', 'nama_program_studi',
        'singkatan_program_studi', 'kode_fakultas', 'kode_prodi_univ',
        'kode_pengguna', 'kompetensi',
    ];

    public function dosen()
    {
        return $this->hasMany(Dosen::class, 'homebase', 'kode_program_studi');
    }

    public function namaKurikulum()
    {
        return $this->hasMany(NamaKurikulum::class, 'kode_program_studi', 'kode_program_studi');
    }
}

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
        'kode_pengguna', 'kompetensi', 'kode_dosen_kaprodi',
    ];

    public function dosen()
    {
        return $this->hasMany(Dosen::class, 'homebase', 'kode_program_studi');
    }

    public function kaprodi()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen_kaprodi', 'kode_dosen');
    }

    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class, 'kode_fakultas', 'kode_fakultas');
    }

    public function namaKurikulum()
    {
        return $this->hasMany(NamaKurikulum::class, 'kode_program_studi', 'kode_program_studi');
    }
}

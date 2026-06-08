<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class Kelas extends Model
{
    use HasCode;
    protected $table = 'kelas';
    protected $primaryKey = 'kelas_id';
    public $timestamps = false;

    protected $fillable = [
        'nama_kelas_id', 'semester', 'kode_tahun_akademik',
        'kode_program_studi', 'id_matakuliah',
    ];

    public function namaKelas()
    {
        return $this->belongsTo(NamaKelas::class, 'nama_kelas_id', 'nama_kelas_id');
    }

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah', 'id_matakuliah');
    }

    public function kelasMahasiswa()
    {
        return $this->hasMany(KelasMahasiswa::class, 'kelas_id', 'kelas_id');
    }

    public function mengajar()
    {
        return $this->hasMany(Mengajar::class, 'kelas_id', 'kelas_id');
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'kode_program_studi', 'kode_program_studi');
    }

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class NamaKurikulum extends Model
{
    use HasCode;
    protected $table = 'nama_kurikulum';
    protected $primaryKey = 'kode_nama_kurikulum';

    protected $fillable = [
        'nama_kurikulum', 'kode_program_studi', 'kode_pengguna',
        'angkatan1', 'ekstensi1', 'paket1', 'semester_stup_grade1',
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'kode_program_studi', 'kode_program_studi');
    }

    public function kurikulum()
    {
        return $this->hasMany(Kurikulum::class, 'kode_nama_kurikulum', 'kode_nama_kurikulum');
    }

    public function kurikulumAngkatan()
    {
        return $this->hasMany(KurikulumAngkatan::class, 'kode_nama_kurikulum', 'kode_nama_kurikulum');
    }
}

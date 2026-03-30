<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NamaKurikulum extends Model
{
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
}

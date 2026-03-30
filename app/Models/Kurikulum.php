<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    protected $table = 'kurikulum';
    protected $primaryKey = 'kode_kurikulum';

    protected $fillable = [
        'kode_nama_kurikulum', 'kode_matakuliah', 'semester',
        'kode_pengguna', 'id_matakuliah',
    ];

    public function namaKurikulum()
    {
        return $this->belongsTo(NamaKurikulum::class, 'kode_nama_kurikulum', 'kode_nama_kurikulum');
    }

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah', 'id_matakuliah');
    }
}

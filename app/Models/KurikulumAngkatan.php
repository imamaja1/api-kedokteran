<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KurikulumAngkatan extends Model
{
    protected $table = 'kurikulum_angkatan';
    protected $primaryKey = 'kode_kurikulum_angkatan';
    public $timestamps = false;

    protected $fillable = [
        'angkatan',
        'ekstensi',
        'paket',
        'kode_nama_kurikulum',
    ];

    protected $casts = [
        'ekstensi' => 'string',
        'paket'    => 'string',
    ];

    public function namaKurikulum()
    {
        return $this->belongsTo(NamaKurikulum::class, 'kode_nama_kurikulum', 'kode_nama_kurikulum');
    }
}

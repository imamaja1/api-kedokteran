<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    protected $table = 'krs';
    protected $primaryKey = 'kode_krs';

    protected $fillable = [
        'kode_tahun_akademik', 'nim', 'semester',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }

    public function detail()
    {
        return $this->hasMany(KrsDetail::class, 'kode_krs', 'kode_krs');
    }
}

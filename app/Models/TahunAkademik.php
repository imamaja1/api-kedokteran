<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class TahunAkademik extends Model
{
    use HasCode;
    protected $table = 'tahun_akademik';
    protected $primaryKey = 'kode_tahun_akademik';

    protected $fillable = [
        'tahun_akademik', 'semester', 'tanggal_mulai', 'tanggal_berakhir',
        'status', 'status_kpat', 'kode_pengguna', 'kode_institusi',
    ];

    protected $casts = [
        'tanggal_mulai'    => 'date',
        'tanggal_berakhir' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'A');
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }
}

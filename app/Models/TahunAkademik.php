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
        'tanggal_buka_krs', 'tanggal_tutup_krs',
        'status', 'status_kpat', 'kode_pengguna', 'kode_institusi',
    ];

    protected $casts = [
        'tanggal_mulai'    => 'date',
        'tanggal_berakhir' => 'date',
        'tanggal_buka_krs' => 'date',
        'tanggal_tutup_krs' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'A');
    }

    public function isKrsOpen(): bool
    {
        $now = now();

        return $this->tanggal_buka_krs && $this->tanggal_tutup_krs
            && $now->gte($this->tanggal_buka_krs) && $now->lte($this->tanggal_tutup_krs);
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }
}

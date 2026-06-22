<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasCode;
    protected $table = 'pembayaran';

    protected $fillable = [
        'nim', 'kode_tahun_akademik', 'status', 'tanggal_bayar', 'keterangan',
        'sks_override', 'sks_override_reason', 'sks_override_by', 'sks_override_at',
    ];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'sks_override_at' => 'datetime',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }

    public function overrideBy()
    {
        return $this->belongsTo(User::class, 'sks_override_by');
    }
}

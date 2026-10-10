<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalKelas extends Model
{
    use HasFactory, HasCode;

    protected $table = 'jadwal_kelas';

    protected $fillable = [
        'kelas_id',
        'ruangan_id',
        'hari_id',
        'kode_tahun_akademik',
        'jam_mulai',
        'jam_selesai',
        'keterangan',
    ];

    protected $casts = [
        'kelas_id' => 'integer',
        'ruangan_id' => 'integer',
        'hari_id' => 'integer',
        'kode_tahun_akademik' => 'integer',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function hari(): BelongsTo
    {
        return $this->belongsTo(Hari::class, 'hari_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    use HasFactory, HasCode;

    protected $table = 'ruangan';

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
        'kapasitas',
        'jenis',
        'lokasi',
        'status_aktif',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'status_aktif' => 'boolean',
    ];

    public function jadwalKelas(): HasMany
    {
        return $this->hasMany(JadwalKelas::class, 'ruangan_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hari extends Model
{
    use HasFactory, HasCode;

    protected $table = 'hari';

    protected $fillable = [
        'nama_hari',
        'urutan',
        'status_aktif',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'status_aktif' => 'boolean',
    ];

    public function jadwalKelas(): HasMany
    {
        return $this->hasMany(JadwalKelas::class, 'hari_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }
}

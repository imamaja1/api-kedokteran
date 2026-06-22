<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SksRule extends Model
{
    protected $table = 'sks_rule';

    protected $fillable = ['kode_program_studi', 'ip_min', 'ip_max', 'sks_yang_dapat_diambil'];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'kode_program_studi');
    }

    /**
     * Cari rule SKS berdasarkan IPK
     */
    public static function cariRule(int $kodeProdi, float $ip): ?self
    {
        return self::where('kode_program_studi', $kodeProdi)
            ->where('ip_min', '<=', $ip)
            ->where('ip_max', '>=', $ip)
            ->first();
    }
}

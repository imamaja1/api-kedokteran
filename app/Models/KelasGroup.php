<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KelasGroup extends Model
{
    use HasCode;

    protected $table = 'kelas_groups';

    protected $fillable = [
        'kelas_id',
        'node_key',
        'nama_group',
        'kode_dosen',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen', 'kode_dosen');
    }

    public function groupMahasiswa(): HasMany
    {
        return $this->hasMany(KelasGroupMahasiswa::class, 'kelas_group_id');
    }

    public function mahasiswa(): HasManyThrough
    {
        return $this->hasManyThrough(
            Mahasiswa::class,
            KelasGroupMahasiswa::class,
            'kelas_group_id', // Foreign key on kelas_group_mahasiswas table...
            'nim',            // Foreign key on mahasiswa table...
            'id',             // Local key on kelas_groups table...
            'nim'             // Local key on kelas_group_mahasiswas table...
        );
    }
}

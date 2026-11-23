<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasGroupMahasiswa extends Model
{
    use HasCode;

    protected $table = 'kelas_group_mahasiswas';

    protected $fillable = [
        'kelas_group_id',
        'nim',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(KelasGroup::class, 'kelas_group_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }
}

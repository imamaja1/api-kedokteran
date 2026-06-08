<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class PerwalianKrsValidasi extends Model
{
    use HasCode;
    protected $table = 'perwalian_krs_validasi';

    protected $primaryKey = 'kode_perwalian_krs_validasi';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nim',
        'kode_dosen_validator',
        'status_krs',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function dosenValidator()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen_validator', 'kode_dosen');
    }
}

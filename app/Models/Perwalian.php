<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class Perwalian extends Model
{
    use HasCode;
    protected $table = 'perwalian';

    protected $primaryKey = 'kode_perwalian';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nim',
        'kode_dosen',
        'kode_dosen_perwakilan',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen', 'kode_dosen');
    }

    public function dosenPerwakilan()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen_perwakilan', 'kode_dosen');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class KrsDetail extends Model
{
    use HasCode;
    protected $table = 'krs_detail';
    protected $primaryKey = 'kode_krs_detail';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'kode_krs_detail', 'kode_krs', 'kode_matakuliah', 'status', 'id_matakuliah',
    ];

    public function krs()
    {
        return $this->belongsTo(Krs::class, 'kode_krs', 'kode_krs');
    }

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah', 'id_matakuliah');
    }

    public function khsDetail()
    {
        return $this->hasOne(KhsDetail::class, 'kode_krs_detail', 'kode_krs_detail');
    }
}

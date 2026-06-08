<?php

namespace App\Models;

use App\Models\Traits\HasCode;
use Illuminate\Database\Eloquent\Model;

class KhsDetail extends Model
{
    use HasCode;

    protected $table = 'khs_detail';

    protected $primaryKey = 'kode_khs_detail';

    protected $fillable = [
        'kode_krs_detail', 'nilai_harian', 'nilai_uts',
        'nilai_uas', 'nilai_akhir', 'tidak_berhak',
    ];

    public function krsDetail()
    {
        return $this->belongsTo(KrsDetail::class, 'kode_krs_detail', 'kode_krs_detail');
    }
}

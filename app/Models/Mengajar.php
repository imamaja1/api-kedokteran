<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class Mengajar extends Model
{
    use HasCode;
    protected $table = 'mengajar';
    protected $primaryKey = 'mengajar_id';
    public $timestamps = false;

    protected $fillable = ['kode_dosen', 'kelas_id'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'kelas_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen', 'kode_dosen');
    }
}

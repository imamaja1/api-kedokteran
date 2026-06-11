<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCode;

class Fakultas extends Model
{
    use HasCode;

    protected $table = 'fakultas';
    protected $primaryKey = 'kode_fakultas';

    protected $fillable = [
        'nama_fakultas',
        'kode_dosen_dekan',
    ];

    public function dekan()
    {
        return $this->belongsTo(Dosen::class, 'kode_dosen_dekan', 'kode_dosen');
    }

    public function programStudi()
    {
        return $this->hasMany(ProgramStudi::class, 'kode_fakultas', 'kode_fakultas');
    }
}

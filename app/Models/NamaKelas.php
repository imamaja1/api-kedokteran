<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NamaKelas extends Model
{
    protected $table = 'nama_kelas';
    protected $primaryKey = 'nama_kelas_id';
    public $timestamps = false;

    protected $fillable = ['nama_kelas'];

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'nama_kelas_id', 'nama_kelas_id');
    }
}

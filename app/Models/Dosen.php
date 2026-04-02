<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Dosen extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'dosen';
    protected $primaryKey = 'kode_dosen';
    public $incrementing = false;

    protected $fillable = [
        'kode_dosen', 'nama_dosen', 'field_studi', 'alumni', 'nik', 'no_telp',
        'status_dosen', 'homebase', 'alamat_email', 'sandi_pengguna',
        'status_login', 'aktif', 'signature', 'chatid',
    ];

    protected $hidden = ['sandi_pengguna'];

    /**
     * Get the password for the user.
     * Dosen menggunakan field 'sandi_pengguna' bukan 'password'
     */
    public function getAuthPassword()
    {
        return $this->sandi_pengguna;
    }

    // Relasi
    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'homebase', 'kode_program_studi');
    }
}

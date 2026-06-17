<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\HasCode;

class Dosen extends Authenticatable
{
    use HasApiTokens, SoftDeletes, HasCode;

    protected $table = 'dosen';

    protected $primaryKey = 'kode_dosen';

    public $incrementing = false;

    protected $fillable = [
        'kode_dosen', 'nama_dosen', 'field_studi', 'alumni', 'nik', 'no_telp',
        'status_dosen', 'homebase', 'alamat_email', 'sandi_pengguna',
        'status_login', 'aktif', 'signature', 'chatid', 'foto',
    ];

    protected $hidden = ['sandi_pengguna'];

    protected $dates = ['deleted_at'];

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

    public function kaprodiProgramStudi()
    {
        return $this->hasOne(ProgramStudi::class, 'kode_dosen_kaprodi', 'kode_dosen');
    }

    public function dekanFakultas()
    {
        return $this->hasOne(Fakultas::class, 'kode_dosen_dekan', 'kode_dosen');
    }
}

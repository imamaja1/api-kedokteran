<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Mahasiswa extends Authenticatable
{
    use HasApiTokens,Notifiable, SoftDeletes;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'nim';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nim', 'nik', 'npm', 'nisn', 'nomor_pendaftaran', 'nomor_pendaftaran_ulang',
        'program_studi_kode', 'nama_mahasiswa', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'kota', 'propinsi', 'telepon', 'jenis_kelamin', 'agama',
        'golongan_darah', 'kewarganegaraan', 'nama_instansi', 'email',
        'nama_ayah', 'agama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'agama_ibu',
        'pekerjaan_ibu', 'alamat_orangtua', 'kota_orangtua', 'propinsi_orangtua',
        'telepon_orangtua', 'foto', 'sandi', 'status', 'status_pendaftaran', 'ta_lulus',
    ];

    protected $hidden = ['sandi'];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    /**
     * Get the password for the user.
     * Mahasiswa menggunakan field 'sandi' bukan 'password'
     */
    public function getAuthPassword()
    {
        return $this->sandi;
    }

    // Relasi
    public function krs()
    {
        return $this->hasMany(Krs::class, 'nim', 'nim');
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_kode', 'kode_program_studi');
    }
}

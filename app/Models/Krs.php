<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    protected $table = 'krs';

    protected $primaryKey = 'kode_krs';

    protected $fillable = [
        'kode_tahun_akademik', 'nim', 'semester',
    ];

    protected $hidden = ['id_matakuliah'];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'kode_tahun_akademik', 'kode_tahun_akademik');
    }

    public function detail()
    {
        return $this->hasMany(KrsDetail::class, 'kode_krs', 'kode_krs')
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->leftJoin('khs_detail', 'krs_detail.kode_krs_detail', '=', 'khs_detail.kode_krs_detail')
            ->select('krs_detail.*', 'matakuliah.kode_matakuliah', 'matakuliah.nama_matakuliah', 'matakuliah.sks_teori', 'matakuliah.sks_praktik', 'khs_detail.nilai_akhir', 'khs_detail.tidak_berhak');
    }

    public function matakuliah()
    {
        return $this->hasManyThrough(Matakuliah::class, KrsDetail::class, 'kode_krs', 'id_matakuliah', 'kode_krs', 'kode_matakuliah');
    }

    public function khsDetail()
    {
        return $this->hasManyThrough(KhsDetail::class, KrsDetail::class, 'kode_krs', 'id_krs_detail', 'kode_krs', 'id_krs_detail');
    }
}

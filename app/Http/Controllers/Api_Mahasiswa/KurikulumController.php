<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceKurikulum;

class KurikulumController extends Controller
{
    public function kurikulum(){
        $nim = auth()->user()->nim;
        $kode_prodi = auth()->user()->program_studi_kode;
        $service = new ServiceKurikulum();
        return $service->kurikulum_by_nim($nim, $kode_prodi);
    }
}

<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use App\Service\ServiceKurikulum;
use Illuminate\Support\Facades\Auth;

class KurikulumController extends Controller
{
    public function kurikulum(){
        $user = Auth::guard('mahasiswa_web')->user();
        $nim = $user->nim;
        $kode_prodi = $user->program_studi_kode;
        $service = new ServiceKurikulum();
        return $service->kurikulum_by_nim($nim, $kode_prodi);
    }
}

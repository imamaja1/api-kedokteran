<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use App\Service\ServicePetikanNilai;
use Illuminate\Support\Facades\Auth;

class PetikanNIlaiController extends Controller
{
    public function petikan_nilai(){
        $user = Auth::guard('mahasiswa_web')->user();
        $nim = $user->nim;
        $kode_prodi = $user->program_studi_kode;
        $service = new ServicePetikanNilai();
        return $service->petikan_nilai_by_nim($nim, $kode_prodi);
    }
}

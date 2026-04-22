<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServicePetikanNilai;

class PetikanNIlaiController extends Controller
{
    public function petikan_nilai(){
        $nim = auth()->user()->nim;
        $kode_prodi = auth()->user()->program_studi_kode;
        $service = new ServicePetikanNilai();
        return $service->petikan_nilai_by_nim($nim, $kode_prodi);
    }
}

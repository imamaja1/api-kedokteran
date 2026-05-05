<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceMahasiswa;
use App\Service\ServiceKurikulum;
use App\Service\ServicePetikanNilai;
use App\Service\ServiceProgramStudi;
use App\Service\ServiceTahunAngkatan;
use Illuminate\Support\Facades\Crypt;

class AkademikController extends Controller
{
    public function __construct()
    {
        //
    }

    public function program_studi()
    {
        return (new ServiceProgramStudi())->getAllProgramStudi();
    }
    public function NamaKurikulum(){
        return (new ServiceKurikulum())->nama_kurikulum();
    }

    public function KRS(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServicePetikanNilai())->getKRS($validated['nim']);
    }

    public function KHS(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServicePetikanNilai())->getKHS($validated['nim']);
    }

    public function PetikanNilai(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServicePetikanNilai())->getTranskrip($validated['nim']);
    }
}
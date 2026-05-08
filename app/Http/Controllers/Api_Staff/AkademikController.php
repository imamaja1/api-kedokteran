<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceMahasiswa;
use App\Service\ServiceKurikulum;
use App\Service\ServicePetikanNilai;
use App\Service\ServiceProgramStudi;
use App\Service\ServiceTahunAngkatan;
use App\Service\ServiceKRS;
use App\Service\ServiceKHS;
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

    public function Kurikulum(Request $request){
        $validated = $request->validate([
            'code_nama_kurikulum
            ' => ['required', 'string'],
        ]);
        $kode_nama_kurikulum = Crypt::decryptString($validated['code_nama_kurikulum']);
        return (new ServiceKurikulum())->kurikulum_by_nama_kurikulum($kode_nama_kurikulum);
    }

    public function KRS(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServiceKRS())->getAllKRS($validated['nim']);
    }

    public function KRSDetail(Request $request){
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        $kode_krs = Crypt::decryptString($validated['code_krs']);
        return (new ServiceKRS())->getKRSDetail($kode_krs);
    }

    public function KHS(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServiceKHS())->getAllKHS($validated['nim']);
    }

    public function KHSDetail(Request $request){
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        $kode_krs = Crypt::decryptString($validated['code_krs']);
        return (new ServiceKHS())->getKHSDetail($kode_krs);
    }

    public function PetikanNilai(Request $request){
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return (new ServicePetikanNilai())->getTranskrip($validated['nim']);
    }
}
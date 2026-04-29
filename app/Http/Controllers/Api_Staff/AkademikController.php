<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceMahasiswa;
use App\Service\ServiceKurikulum;
use App\Service\ServicePetikanNilai;
use App\Service\ServiceProgramStudi;
use App\Service\ServiceTahunAngkatan;

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
    public function tahun_angkatan()
    {
        return (new ServiceTahunAngkatan())->getTahunAngkatan();
    }
    public function Mahasiswa(Request $request){
        $validated = $request->validate([
            'nim'        => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'kode_prodi' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'angkatan'   => ['nullable', 'digits:4'],
        ]);
        return (new ServiceMahasiswa())->getAllMahasiswa(
            isset($validated['nim'])        ? $validated['nim'] : null,
            isset($validated['kode_prodi']) ? $validated['kode_prodi'] : null,
            isset($validated['angkatan'])   ? substr($validated['angkatan'], 2, 2) : null,
        );
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
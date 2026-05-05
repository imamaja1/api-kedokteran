<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceMahasiswa;
use App\Service\ServiceTahunAngkatan;
use Illuminate\Support\Facades\Crypt;

class DefaultController extends Controller
{
    public function Mahasiswa(Request $request){
        $validated = $request->validate([
            'nim'        => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'code' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'angkatan'   => ['nullable', 'digits:4'],
        ]);
        return (new ServiceMahasiswa())->getAllMahasiswa(
            isset($validated['nim'])  ? $validated['nim'] : null,
            isset($validated['code']) ? Crypt::decryptString($validated['code']) : null,
            isset($validated['angkatan']) ? substr($validated['angkatan'], 2, 2) : null,
        );
    }

    public function tahun_angkatan()
    {
        return (new ServiceTahunAngkatan())->getTahunAngkatan();
    }
}


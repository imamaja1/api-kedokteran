<?php

namespace App\Http\Controllers\Api_Devisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ServiceMahasiswa;

class AkademikController extends Controller
{
    public function Mahasiswa(Request $request){
        $validated = $request->validate([
            'nim'        => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'kode_prodi' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'angkatan'   => ['nullable', 'digits:4'],
        ]);

        return (new ServiceMahasiswa())->getAllMahasiswa(
            $validated['nim']        ?? null,
            $validated['kode_prodi'] ?? null,
            isset($validated['angkatan']) ? substr($validated['angkatan'], 2, 2) : null,
        );
    }
}
<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Service\ServiceKRS;
use App\Models\TahunAkademik;

class KrsController extends Controller
{
    public function krs(Request $request){
        $request->validate([
            'semester'       => 'sometimes|nullable|integer',
        ]);
        $semester = $request->query('semester');

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS())->getKRSMhs($nim, $semester);
    }
}

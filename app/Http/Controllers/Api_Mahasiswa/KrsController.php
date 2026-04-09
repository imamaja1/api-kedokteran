<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Service\ServiceKRSMahasiswa;
use App\Models\TahunAkademik;

class KrsController extends Controller
{
    public function krs(Request $request){
        $request->validate([
            'tahun_akademik' => 'sometimes|nullable|string|size:9|regex:/^\d{4}\/\d{4}$/',
            'semester'       => 'sometimes|nullable|in:1,2',
        ]);

        $ta = $request->query('tahun_akademik');
        $semester = $request->query('semester');

        if(!$ta){
            $ta = TahunAkademik::where('status', 'A')->first()->tahun_akademik;
        }else{
            $validated = TahunAkademik::where('tahun_akademik', $ta)->first();
            if(!$validated){
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun akademik tidak valid.',
                ], 400);
            }
        }
        if(!$semester){
            $semester = TahunAkademik::where('status', 'A')->first()->semester;
        }

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRSMahasiswa())->getKRSMhs($nim, $ta, $semester);
    }
}

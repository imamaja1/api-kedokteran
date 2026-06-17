<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use App\Service\ServiceKRS;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KrsController extends Controller
{
    public function krs(Request $request): JsonResponse
    {
        $request->validate([
            'semester' => 'sometimes|nullable|integer',
        ]);
        $semester = $request->query('semester');

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->getKRSMhs($nim, $semester);
    }

    public function create(Request $request): JsonResponse
    {
        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->createKRS($nim);
    }

    public function addDetail(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'kode_krs' => ['required', 'integer'],
            'id_matakuliah' => ['required', 'integer'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->addKrsDetail($nim, $validasi);
    }

    public function removeDetail(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'kode_krs_detail' => ['required', 'integer'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->removeKrsDetail($nim, (string) $validasi['kode_krs_detail']);
    }

    public function sksInfo(): JsonResponse
    {
        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->getSksInfo($nim);
    }
}

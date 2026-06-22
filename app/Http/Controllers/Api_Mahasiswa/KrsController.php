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
        $validasi = $request->validate([
            'semester' => ['nullable', 'integer', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;
        $semester = $validasi['semester'] ?? null;

        return (new ServiceKRS)->getKRSMhs($nim, $semester);
    }

    public function addDetail(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'matakuliah' => ['required', 'array', 'min:1'],
            'matakuliah.*' => ['required', 'integer'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->addKrsDetail($nim, $validasi);
    }

    public function removeDetail(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code_krs_detail' => ['required', 'string'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->removeKrsDetail($nim, $validasi['code_krs_detail']);
    }

    public function krsUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['required', 'integer', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->getKrsForEdit($nim, (int) $validated['semester']);
    }

    public function replaceDetail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['required', 'integer', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
            'matakuliah' => ['required', 'array'],
            'matakuliah.*' => ['required', 'integer'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->replaceKrsDetail($nim, (int) $validated['semester'], $validated['matakuliah']);
    }

    public function sksInfo(): JsonResponse
    {
        $nim = Auth::guard('mahasiswa_web')->user()->nim;

        return (new ServiceKRS)->getSksInfo($nim);
    }
}

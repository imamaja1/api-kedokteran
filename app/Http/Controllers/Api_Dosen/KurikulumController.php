<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Service\ServiceKurikulum;
use App\Service\ServiceMengajar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KurikulumController extends Controller
{
    public function __construct(
        private ServiceKurikulum $serviceKurikulum,
        private ServiceMengajar $serviceMengajar,
    ) {}

    private function getKodeDosen(): int
    {
        $user = Auth::guard('dosen_web')->user();
        return $user->kode_dosen;
    }

    public function getKurikulum(Request $request): JsonResponse
    {
        $request->validate([
            'code_mahasiswa' => 'required|string',
            'kode_program_studi' => 'required|string|exists:program_studi,kode_program_studi',
        ]);

        $mahasiswa = Mahasiswa::findByCode($request->input('code_mahasiswa'));
        if (! $mahasiswa) {
            return response()->json(['status' => false, 'message' => 'Mahasiswa tidak ditemukan.'], 404);
        }

        return $this->serviceKurikulum->kurikulum_by_nim(
            $mahasiswa->nim,
            $request->input('kode_program_studi')
        );
    }

    public function getKelasDosen(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        return $this->serviceMengajar->getKelasDosen(
            $this->getKodeDosen(),
            (int) $request->input('per_page', 20)
        );
    }

    public function getDetailKelas(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        return $this->serviceMengajar->getDetailKelas(
            $request->input('code'),
            $this->getKodeDosen()
        );
    }
}

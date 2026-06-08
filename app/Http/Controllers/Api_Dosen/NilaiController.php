<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Service\ServiceNilaiDosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NilaiController extends Controller
{
    public function __construct(
        private ServiceNilaiDosen $serviceNilaiDosen,
    ) {}

    private function getKodeDosen(): int
    {
        $user = Auth::guard('dosen_web')->user();
        return $user->kode_dosen;
    }

    public function getTreePenilaian(): JsonResponse
    {
        return $this->serviceNilaiDosen->getTreePenilaian(
            $this->getKodeDosen()
        );
    }

    public function getMahasiswa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        return $this->serviceNilaiDosen->getMahasiswa(
            $request->input('code'),
            $this->getKodeDosen()
        );
    }

    public function inputNilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
            'mahasiswa' => 'required|array|min:1',
            'mahasiswa.*.code_mahasiswa' => 'required|string',
            'mahasiswa.*.nilai_harian' => 'required|numeric',
            'mahasiswa.*.nilai_uts' => 'required|numeric',
            'mahasiswa.*.nilai_uas' => 'required|numeric',
            'mahasiswa.*.nilai_akhir' => 'required|numeric',
            'mahasiswa.*.tidak_berhak' => 'sometimes|boolean',
        ]);

        return $this->serviceNilaiDosen->inputNilai($validated, $this->getKodeDosen());
    }
}

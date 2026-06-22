<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Service\ServicePenilaianDosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenilaianDosenController extends Controller
{
    public function __construct(
        private ServicePenilaianDosen $servicePenilaianDosen,
    ) {}

    private function getKodeDosen(): int
    {
        $user = Auth::guard('dosen_web')->user();
        if (! $user) {
            abort(401, 'Unauthorized. Silakan login terlebih dahulu.');
        }
        return $user->kode_dosen;
    }

    public function getKelasPenilaian(): JsonResponse
    {
        return $this->servicePenilaianDosen->getKelasPenilaian(
            $this->getKodeDosen()
        );
    }

    public function getMahasiswaPenilaian(Request $request): JsonResponse
    {
        $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->servicePenilaianDosen->getMahasiswaPenilaian(
            $request->input('code_kelas'),
            $this->getKodeDosen()
        );
    }

    public function getTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->servicePenilaianDosen->getTemplateForKelas(
            $request->input('code_kelas'),
            $this->getKodeDosen()
        );
    }

    public function getDetailNilaiMahasiswa(Request $request): JsonResponse
    {
        $request->validate([
            'code_kelas' => 'required|string',
            'code_mahasiswa' => 'required|string',
        ]);

        return $this->servicePenilaianDosen->getDetailNilaiMahasiswa(
            $request->input('code_kelas'),
            $request->input('code_mahasiswa'),
            $this->getKodeDosen()
        );
    }

    public function inputNilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
            'mahasiswa' => 'required|array|min:1',
            'mahasiswa.*.code_mahasiswa' => 'required|string',
            'mahasiswa.*.scores' => 'required|array|min:1',
            'mahasiswa.*.scores.*.node_key' => 'required|string',
            'mahasiswa.*.scores.*.score' => 'required|numeric|min:0|max:100',
            'mahasiswa.*.catatan' => 'nullable|string',
        ]);

        return $this->servicePenilaianDosen->inputNilai($validated, $this->getKodeDosen());
    }

    public function updateNilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
            'mahasiswa' => 'required|array|min:1',
            'mahasiswa.*.code_mahasiswa' => 'required|string',
            'mahasiswa.*.scores' => 'required|array|min:1',
            'mahasiswa.*.scores.*.node_key' => 'required|string',
            'mahasiswa.*.scores.*.score' => 'required|numeric|min:0|max:100',
            'mahasiswa.*.catatan' => 'nullable|string',
        ]);

        return $this->servicePenilaianDosen->updateNilai($validated, $this->getKodeDosen());
    }
}

<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Service\ServicePenilaianKaprodi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenilaianKaprodiController extends Controller
{
    public function __construct(
        private ServicePenilaianKaprodi $servicePenilaianKaprodi,
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
        return $this->servicePenilaianKaprodi->getKelasPenilaian(
            $this->getKodeDosen()
        );
    }

    public function getMahasiswaPenilaian(Request $request): JsonResponse
    {
        $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->servicePenilaianKaprodi->getMahasiswaPenilaian(
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

        return $this->servicePenilaianKaprodi->getDetailNilaiMahasiswa(
            $request->input('code_kelas'),
            $request->input('code_mahasiswa'),
            $this->getKodeDosen()
        );
    }

    public function validasi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
            'code_mahasiswa' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        return $this->servicePenilaianKaprodi->validasiPenilaian(
            $validated['code_kelas'],
            $validated['code_mahasiswa'],
            $validated['catatan'] ?? null,
            $this->getKodeDosen()
        );
    }

    public function revisi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
            'code_mahasiswa' => 'required|string',
            'catatan' => 'required|string',
        ]);

        return $this->servicePenilaianKaprodi->revisiPenilaian(
            $validated['code_kelas'],
            $validated['code_mahasiswa'],
            $validated['catatan'],
            $this->getKodeDosen()
        );
    }
}

<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Perwalian;
use App\Service\ServiceKRS;
use App\Service\ServicePerwalian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerwalianController extends Controller
{
    public function __construct(
        private ServicePerwalian $servicePerwalian,
        private ServiceKRS $serviceKRS,
    ) {}

    private function getKodeDosen(): int
    {
        $user = Auth::guard('dosen_web')->user();
        if (! $user) {
            abort(401, 'Unauthorized. Silakan login terlebih dahulu.');
        }
        return $user->kode_dosen;
    }

    public function jumlahPerwalian(Request $request): JsonResponse
    {
        $request->validate([
            'semester' => 'required|integer',
        ]);

        return $this->servicePerwalian->getJumlahPerwalian(
            $this->getKodeDosen(),
            (int) $request->input('semester')
        );
    }

    public function daftarPerwalian(Request $request): JsonResponse
    {
        $request->validate([
            'semester' => 'required|integer',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        return $this->servicePerwalian->getDaftarPerwalian(
            $this->getKodeDosen(),
            (int) $request->input('semester')
        );
    }

    public function riwayatPerwalian(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        return $this->servicePerwalian->getPerwalianByDosen(
            $this->getKodeDosen()
        );
    }

    public function showKrsDetail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $krs = Krs::findByCode($request->input('code'));
        if (! $krs) {
            return response()->json(['status' => false, 'message' => 'KRS tidak ditemukan.'], 404);
        }

        return $this->serviceKRS->getKRSDetail($krs->kode_krs);
    }

    public function validasiKrs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_mahasiswa' => 'required|string',
            'status_krs' => 'required|string|in:A,N',
            'catatan' => 'nullable|string|max:500',
        ]);

        $mahasiswa = Mahasiswa::findByCode($validated['code_mahasiswa']);
        if (! $mahasiswa) {
            return response()->json(['status' => false, 'message' => 'Mahasiswa tidak ditemukan.'], 404);
        }

        $payload = [
            'nim' => $mahasiswa->nim,
            'kode_dosen_validator' => $this->getKodeDosen(),
            'status_krs' => $validated['status_krs'],
            'catatan' => $validated['catatan'] ?? null,
        ];

        return $this->servicePerwalian->storeValidasiKrs($payload);
    }

    public function revisi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_validasi_krs' => 'required|string',
            'catatan' => 'nullable|string|max:500',
        ]);

        $validasi = \App\Models\PerwalianKrsValidasi::findByCode($validated['code_validasi_krs']);
        if (! $validasi) {
            return response()->json(['status' => false, 'message' => 'Validasi KRS tidak ditemukan.'], 404);
        }

        $payload = [
            'kode_perwalian_krs_validasi' => $validasi->kode_perwalian_krs_validasi,
            'catatan' => $validated['catatan'] ?? null,
        ];

        return $this->servicePerwalian->revisiValidasiKrs($payload);
    }

    public function batalPerwalian(Request $request): JsonResponse
    {
        $request->validate([
            'code_perwalian' => 'required|string',
        ]);

        $perwalian = Perwalian::findByCode($request->input('code_perwalian'));
        if (! $perwalian) {
            return response()->json(['status' => false, 'message' => 'Perwalian tidak ditemukan.'], 404);
        }

        return $this->servicePerwalian->batalPerwalian(
            $perwalian->kode_perwalian,
            $this->getKodeDosen()
        );
    }
}

<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Service\ServiceAssessmentDosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentDosenController extends Controller
{
    public function __construct(
        private readonly ServiceAssessmentDosen $service,
    ) {}

    private function getKodeDosen(): int
    {
        $user = Auth::guard('dosen_web')->user();
        if (! $user) {
            abort(401, 'Unauthorized.');
        }
        return $user->kode_dosen;
    }

    /**
     * GET /api/dosen/assessment-dosen/my-nodes
     */
    public function myNodes(): JsonResponse
    {
        return $this->service->getMyNodes($this->getKodeDosen());
    }

    /**
     * GET /api/dosen/assessment-dosen/mahasiswa
     */
    public function mahasiswaByNode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer',
            'node_key'            => 'required|string',
        ]);

        return $this->service->getMahasiswaByNode(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
            $this->getKodeDosen()
        );
    }

    /**
     * GET /api/dosen/assessment-dosen/detail
     */
    public function detailMahasiswa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer',
            'node_key'            => 'required|string',
            'code_mahasiswa'      => 'required|string',
        ]);

        return $this->service->getDetailMahasiswaNode(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
            $validated['code_mahasiswa'],
            $this->getKodeDosen()
        );
    }

    /**
     * POST /api/dosen/assessment-dosen/validasi
     */
    public function validasi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer',
            'node_key'            => 'required|string',
            'code_mahasiswa'      => 'required|string',
            'catatan'             => 'nullable|string',
        ]);

        return $this->service->validasiNode(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
            $validated['code_mahasiswa'],
            $this->getKodeDosen(),
            $validated['catatan'] ?? null
        );
    }

    /**
     * POST /api/dosen/assessment-dosen/revisi
     */
    public function revisi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer',
            'node_key'            => 'required|string',
            'code_mahasiswa'      => 'required|string',
            'catatan'             => 'required|string',
        ]);

        return $this->service->revisiNode(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
            $validated['code_mahasiswa'],
            $this->getKodeDosen(),
            $validated['catatan']
        );
    }
}

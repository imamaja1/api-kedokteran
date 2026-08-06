<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceAssessmentDosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentDosenController extends Controller
{
    public function __construct(
        private readonly ServiceAssessmentDosen $service,
    ) {}

    /**
     * POST /api/staff/assessment/dosen
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->service->createAssessment($validated['code_kelas']);
    }

    /**
     * PUT /api/staff/assessment/dosen/assign
     */
    public function assignDosen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer|exists:assessment_dosen,id',
            'assignments'         => 'required|array|min:1',
            'assignments.*.node_key'   => 'required|string',
            'assignments.*.kode_dosen' => 'required|integer|exists:dosen,kode_dosen',
        ]);

        return $this->service->assignDosen(
            $validated['assessment_dosen_id'],
            $validated['assignments']
        );
    }

    /**
     * GET /api/staff/assessment/dosen/assignments?code_kelas=
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->service->getAssignments($validated['code_kelas']);
    }
}

<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\Mahasiswa;
use App\Models\StudentScore;
use App\Service\Assessment\ScoreCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AssessmentScoreController extends Controller
{
    public function __construct(
        private readonly ScoreCalculationService $scoreService,
    ) {}

    /**
     * POST /staff/assessment/scores
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code_template' => 'required|string',
                'code' => 'required|string',
                'node_key' => 'required|string',
                'score' => 'required|numeric|min:0|max:100',
                'notes' => 'nullable|string',
            ]);

            $templateId = Crypt::decryptString($validated['code_template']);
            $nim = Crypt::decryptString($validated['code']);

            $score = StudentScore::updateOrCreate(
                [
                    'template_id' => $templateId,
                    'nim' => $nim,
                    'node_key' => $validated['node_key'],
                ],
                [
                    'score' => $validated['score'],
                    'notes' => $validated['notes'] ?? null,
                    'assessor_id' => auth()->id(),
                ],
            );

            return ApiResponse::success([
                'id' => 1,
                'code' => Crypt::encryptString($nim),
                'code_template' => Crypt::encryptString($templateId),
                'node_key' => $score->node_key,
                'score' => $score->score,
                'notes' => $score->notes,
                'assessor_id' => $score->assessor_id,
            ], 'Score saved successfully.', 201);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        } catch (\Exception $e) {
            return ApiResponse::error('Error: ' . $e->getMessage(), 422);
        }
    }

    /**
     * GET /staff/assessment/students/score?code=<nim>&template_code=<template>
     */
    public function getStudentScore(Request $request): JsonResponse
    {
        try {
            $code = $request->query('code');
            $templateCode = $request->query('template_code');

            if (!$code || !$templateCode) {
                return ApiResponse::validation(['code' => 'code and template_code are required']);
            }

            $nim = Crypt::decryptString($code);
            $templateId = Crypt::decryptString($templateCode);
            $template = AssessmentTemplate::findOrFail($templateId);
            $allScores = $this->scoreService->getAllScoresForStudent($template, $nim);

            return ApiResponse::success([
                'code' => Crypt::encryptString($nim),
                'code_template' => Crypt::encryptString($templateId),
                'scores' => $allScores,
                'final_score' => $this->scoreService->calculateFinalScore($template, $nim),
            ], 'Student scores retrieved successfully.');
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Template or student not found');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Error: ' . $e->getMessage());
        }
    }

    /**
     * GET /staff/assessment/students/score/breakdown?code=<nim>&template_code=<template>&node_key=<key>
     */
    public function getScoreBreakdown(Request $request): JsonResponse
    {
        try {
            $code = $request->query('code');
            $templateCode = $request->query('template_code');
            $nodeKey = $request->query('node_key');

            if (!$code || !$templateCode || !$nodeKey) {
                return ApiResponse::validation(['code' => 'code, template_code, and node_key are required']);
            }

            $nim = Crypt::decryptString($code);
            $templateId = Crypt::decryptString($templateCode);
            $template = AssessmentTemplate::findOrFail($templateId);
            Mahasiswa::findOrFail($nim);

            $breakdown = $this->scoreService->getCalculationBreakdown($template, $nim, $nodeKey);

            if (empty($breakdown)) {
                return ApiResponse::notFound('Node key not found in template');
            }

            return ApiResponse::success([
                'code' => Crypt::encryptString($nim),
                'code_template' => Crypt::encryptString($templateId),
                'breakdown' => $breakdown,
            ], 'Breakdown retrieved successfully.');
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Template or student not found');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Error: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\AuditFeederService;
use App\Service\FeederService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuditFeederController extends Controller
{
    protected FeederService $feederService;
    protected AuditFeederService $auditService;

    public function __construct(FeederService $feederService, AuditFeederService $auditService)
    {
        $this->feederService = $feederService;
        $this->auditService = $auditService;
    }

    /**
     * 1. Cek status konektivitas ke Web Service Feeder dan token aktif.
     * GET /api/staff/feeder/status
     */
    public function status(): JsonResponse
    {
        try {
            $status = $this->feederService->checkStatus();
            return ApiResponse::success($status, $status['message']);
        } catch (Throwable $e) {
            return ApiResponse::error('Gagal memeriksa status Feeder: '.$e->getMessage(), 500);
        }
    }

    /**
     * 2. Tarik data nilai kelas dari Feeder dan simpan di cache.
     * GET /api/staff/feeder/pull/kelas/{code}
     */
    public function pullKelas(Request $request, ?string $code = null): JsonResponse
    {
        try {
            $targetCode = $code ?? $request->query('code') ?? $request->input('code');
            if (($targetCode === '{code}' || empty($targetCode)) && ($request->filled('code') || $request->filled('code_kelas'))) {
                $targetCode = (string) ($request->query('code') ?? $request->input('code') ?? $request->query('code_kelas') ?? $request->input('code_kelas'));
            }
            if (empty($targetCode) || $targetCode === '{code}') {
                return ApiResponse::validation(['code' => 'Parameter code kelas wajib diisi.']);
            }
            $result = $this->auditService->pullKelasData($targetCode, true);
            return ApiResponse::success($result, 'Data nilai kelas berhasil ditarik dari Feeder dan disimpan di cache.');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 3. Audit komparasi nilai per kelas kuliah di memori (Audit_kelas).
     * GET /api/staff/feeder/audit/kelas/{code}
     */
    public function auditKelas(Request $request, ?string $code = null): JsonResponse
    {
        try {
            $targetCode = $code ?? $request->query('code') ?? $request->input('code');
            if (($targetCode === '{code}' || empty($targetCode)) && ($request->filled('code') || $request->filled('code_kelas'))) {
                $targetCode = (string) ($request->query('code') ?? $request->input('code') ?? $request->query('code_kelas') ?? $request->input('code_kelas'));
            }
            if (empty($targetCode) || $targetCode === '{code}') {
                return ApiResponse::validation(['code' => 'Parameter code kelas wajib diisi.']);
            }
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->auditService->auditKelas($targetCode, $forceRefresh);
            return ApiResponse::success($result, 'Audit perbandingan nilai kelas berhasil diproses.');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 4. Tarik data transkrip mahasiswa dari Feeder dan simpan di cache.
     * GET /api/staff/feeder/pull/mahasiswa/{nim}
     */
    public function pullMahasiswa(Request $request, ?string $nim = null): JsonResponse
    {
        try {
            $targetNim = $nim ?? $request->query('nim') ?? $request->query('code') ?? $request->input('nim') ?? $request->input('code');
            if (($targetNim === '{nim}' || empty($targetNim)) && ($request->filled('nim') || $request->filled('code'))) {
                $targetNim = (string) ($request->query('nim') ?? $request->query('code') ?? $request->input('nim') ?? $request->input('code'));
            }
            if (empty($targetNim) || $targetNim === '{nim}') {
                return ApiResponse::validation(['nim' => 'Parameter nim atau code wajib diisi.']);
            }
            $result = $this->auditService->pullMahasiswaData($targetNim, true);
            return ApiResponse::success($result, 'Data transkrip mahasiswa berhasil ditarik dari Feeder dan disimpan di cache.');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 5. Audit petikan transkrip per mahasiswa di memori (Audit_petikan).
     * GET /api/staff/feeder/audit/mahasiswa/{nim}
     */
    public function auditMahasiswa(Request $request, ?string $nim = null): JsonResponse
    {
        try {
            $targetNim = $nim ?? $request->query('nim') ?? $request->query('code') ?? $request->input('nim') ?? $request->input('code');
            if (($targetNim === '{nim}' || empty($targetNim)) && ($request->filled('nim') || $request->filled('code'))) {
                $targetNim = (string) ($request->query('nim') ?? $request->query('code') ?? $request->input('nim') ?? $request->input('code'));
            }
            if (empty($targetNim) || $targetNim === '{nim}') {
                return ApiResponse::validation(['nim' => 'Parameter nim atau code wajib diisi.']);
            }
            $forceRefresh = $request->boolean('refresh', false);
            $result = $this->auditService->auditMahasiswa($targetNim, $forceRefresh);
            return ApiResponse::success($result, 'Audit petikan transkrip mahasiswa berhasil diproses.');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}

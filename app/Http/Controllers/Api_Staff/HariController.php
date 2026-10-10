<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceHari;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class HariController extends Controller
{
    public function __construct(
        private readonly ServiceHari $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->service->index();
    }

    public function show(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->show($resolvedId);
    }

    public function update(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        $validated = $request->validate([
            'status_aktif' => ['required', 'boolean'],
        ]);

        return $this->service->update($resolvedId, $validated);
    }

    public function toggleStatus(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->toggleStatus($resolvedId);
    }

    private function resolveId(mixed $value, string $field = 'code'): int|JsonResponse
    {
        if ($value === null || $value === '') {
            return ApiResponse::validation([$field => "Parameter {$field} wajib diisi."]);
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        try {
            return (int) Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return ApiResponse::validation([$field => "Format {$field} tidak valid."]);
        }
    }
}

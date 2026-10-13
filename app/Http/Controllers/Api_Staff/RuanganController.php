<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceRuangan;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class RuanganController extends Controller
{
    public function __construct(
        private readonly ServiceRuangan $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status_aktif' => ['nullable'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->service->index($validated);
    }

    public function show(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->show($resolvedId);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_ruangan' => ['required', 'string', 'max:50', 'unique:ruangan,kode_ruangan'],
            'nama_ruangan' => ['required', 'string', 'max:100'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        return $this->service->store($validated);
    }

    public function update(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        $validated = $request->validate([
            'kode_ruangan' => ['sometimes', 'string', 'max:50', Rule::unique('ruangan', 'kode_ruangan')->ignore($resolvedId)],
            'nama_ruangan' => ['sometimes', 'string', 'max:100'],
            'kapasitas' => ['sometimes', 'integer', 'min:1'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        return $this->service->update($resolvedId, $validated);
    }

    public function toggleStatus(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code') ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->toggleStatus($resolvedId);
    }

    public function destroy(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code') ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->destroy($resolvedId);
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

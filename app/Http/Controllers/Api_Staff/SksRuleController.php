<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceSksRule;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SksRuleController extends Controller
{
    public function __construct(
        private readonly ServiceSksRule $service,
    ) {}

    /**
     * GET /staff/sks-rule
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'kode_program_studi' => 'nullable|integer',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $paginator = $this->service->index($validated);

            $paginator->getCollection()->transform(fn ($rule, $index) => [
                'id' => $index + 1,
                'code' => Crypt::encryptString((string) $rule->id),
                'kode_program_studi' => $rule->kode_program_studi,
                'nama_program_studi' => $rule->programStudi?->nama_program_studi,
                'ip_min' => $rule->ip_min,
                'ip_max' => $rule->ip_max,
                'sks_yang_dapat_diambil' => $rule->sks_yang_dapat_diambil,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ]);

            return ApiResponse::paginated($paginator, 'Daftar SKS rule berhasil diambil.');
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil data SKS rule. Silakan coba lagi.', 500);
        }
    }

    /**
     * POST /staff/sks-rule
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'kode_program_studi' => 'required|integer|exists:program_studi,kode_program_studi',
                'ip_min' => 'required|numeric|min:0|max:4',
                'ip_max' => 'required|numeric|min:0|max:4|gte:ip_min',
                'sks_yang_dapat_diambil' => 'required|integer|min:1|max:32',
            ]);

            if ($this->service->isOverlap($validated['kode_program_studi'], $validated['ip_min'], $validated['ip_max'])) {
                return ApiResponse::validation(
                    ['ip_min' => ['Range IP overlap dengan rule lain di prodi ini.']],
                    'Range IP tidak valid.'
                );
            }

            $rule = $this->service->store($validated);

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $rule->id),
                'kode_program_studi' => $rule->kode_program_studi,
                'nama_program_studi' => $rule->programStudi?->nama_program_studi,
                'ip_min' => $rule->ip_min,
                'ip_max' => $rule->ip_max,
                'sks_yang_dapat_diambil' => $rule->sks_yang_dapat_diambil,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ], 'SKS rule berhasil dibuat.', 201);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['sks_rule' => ['SKS rule untuk prodi ini sudah ada.']],
                    'SKS rule sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat membuat SKS rule. Silakan coba lagi.', 500);
        }
    }

    /**
     * GET /staff/sks-rule/show?code=<encrypted>
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
            ]);

            $id = (int) Crypt::decryptString($validated['code']);
            $rule = $this->service->show($id);

            if (! $rule) {
                return ApiResponse::notFound('SKS rule tidak ditemukan.');
            }

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $rule->id),
                'kode_program_studi' => $rule->kode_program_studi,
                'nama_program_studi' => $rule->programStudi?->nama_program_studi,
                'ip_min' => $rule->ip_min,
                'ip_max' => $rule->ip_max,
                'sks_yang_dapat_diambil' => $rule->sks_yang_dapat_diambil,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ], 'SKS rule berhasil diambil.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil SKS rule. Silakan coba lagi.', 500);
        }
    }

    /**
     * PUT /staff/sks-rule/update?code=<encrypted>
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
                'kode_program_studi' => 'nullable|integer|exists:program_studi,kode_program_studi',
                'ip_min' => 'nullable|numeric|min:0|max:4',
                'ip_max' => 'nullable|numeric|min:0|max:4|gte:ip_min',
                'sks_yang_dapat_diambil' => 'nullable|integer|min:1|max:32',
            ]);

            $id = (int) Crypt::decryptString($validated['code']);
            unset($validated['code']);

            $rule = $this->service->show($id);
            if (! $rule) {
                return ApiResponse::notFound('SKS rule tidak ditemukan.');
            }

            $checkProdi = $validated['kode_program_studi'] ?? $rule->kode_program_studi;
            $checkMin = $validated['ip_min'] ?? $rule->ip_min;
            $checkMax = $validated['ip_max'] ?? $rule->ip_max;

            if ($this->service->isOverlap($checkProdi, $checkMin, $checkMax, $id)) {
                return ApiResponse::validation(
                    ['ip_min' => ['Range IP overlap dengan rule lain di prodi ini.']],
                    'Range IP tidak valid.'
                );
            }

            $rule = $this->service->update($id, $validated);

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $rule->id),
                'kode_program_studi' => $rule->kode_program_studi,
                'nama_program_studi' => $rule->programStudi?->nama_program_studi,
                'ip_min' => $rule->ip_min,
                'ip_max' => $rule->ip_max,
                'sks_yang_dapat_diambil' => $rule->sks_yang_dapat_diambil,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ], 'SKS rule berhasil diperbarui.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['sks_rule' => ['SKS rule untuk prodi ini sudah ada.']],
                    'SKS rule sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat memperbarui SKS rule. Silakan coba lagi.', 500);
        }
    }

    /**
     * DELETE /staff/sks-rule/{code}
     */
    public function destroy(string $code): JsonResponse
    {
        try {
            $id = (int) Crypt::decryptString($code);
            $deleted = $this->service->destroy($id);

            if (! $deleted) {
                return ApiResponse::notFound('SKS rule tidak ditemukan.');
            }

            return ApiResponse::success(null, 'SKS rule berhasil dihapus.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['sks_rule' => ['SKS rule tidak dapat dihapus karena masih digunakan oleh data lain.']],
                    'SKS rule masih digunakan.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat menghapus SKS rule. Silakan coba lagi.', 500);
        }
    }
}

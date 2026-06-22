<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProgramStudi;
use App\Service\ServiceGradeCRUD;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AssessmentGradeController extends Controller
{
    public function __construct(
        private readonly ServiceGradeCRUD $service,
    ) {}

    /**
     * GET /staff/assessment/grade
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'kode_program_studi' => 'nullable|integer',
                'huruf' => 'nullable|string|max:2',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $paginator = $this->service->index($validated);

            $paginator->getCollection()->transform(fn ($grade, $index) => [
                'id' => $index + 1,
                'code' => Crypt::encryptString((string) $grade->id),
                'kode_program_studi' => $grade->kode_program_studi,
                'nama_program_studi' => $grade->programStudi?->nama_program_studi,
                'nilai_min' => $grade->nilai_min,
                'nilai_max' => $grade->nilai_max,
                'huruf' => $grade->huruf,
                'skor' => $grade->skor,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
            ]);

            return ApiResponse::paginated($paginator, 'Daftar grade berhasil diambil.');
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil data grade. Silakan coba lagi.', 500);
        }
    }

    /**
     * POST /staff/assessment/grade
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'kode_program_studi' => 'required|integer|exists:program_studi,kode_program_studi',
                'nilai_min' => 'required|numeric|min:0|max:100',
                'nilai_max' => 'required|numeric|min:0|max:100|gte:nilai_min',
                'huruf' => 'required|string|max:2',
                'skor' => 'required|numeric|min:0|max:4',
            ]);

            if ($this->service->isOverlap($validated['kode_program_studi'], $validated['nilai_min'], $validated['nilai_max'])) {
                return ApiResponse::validation(
                    ['nilai_min' => ['Range grade overlap dengan grade lain di prodi ini.']],
                    'Range grade tidak valid.'
                );
            }

            $grade = $this->service->store($validated);

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $grade->id),
                'kode_program_studi' => $grade->kode_program_studi,
                'nama_program_studi' => $grade->programStudi?->nama_program_studi,
                'nilai_min' => $grade->nilai_min,
                'nilai_max' => $grade->nilai_max,
                'huruf' => $grade->huruf,
                'skor' => $grade->skor,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
            ], 'Grade berhasil dibuat.', 201);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['grade' => ['Grade untuk huruf ini sudah ada di prodi ini.']],
                    'Grade sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat membuat grade. Silakan coba lagi.', 500);
        }
    }

    /**
     * GET /staff/assessment/grade/show?code=<encrypted>
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
            ]);

            $id = (int) Crypt::decryptString($validated['code']);
            $grade = $this->service->show($id);

            if (! $grade) {
                return ApiResponse::notFound('Grade tidak ditemukan.');
            }

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $grade->id),
                'kode_program_studi' => $grade->kode_program_studi,
                'nama_program_studi' => $grade->programStudi?->nama_program_studi,
                'nilai_min' => $grade->nilai_min,
                'nilai_max' => $grade->nilai_max,
                'huruf' => $grade->huruf,
                'skor' => $grade->skor,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
            ], 'Grade berhasil diambil.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil grade. Silakan coba lagi.', 500);
        }
    }

    /**
     * PUT /staff/assessment/grade/update?code=<encrypted>
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
                'kode_program_studi' => 'nullable|integer|exists:program_studi,kode_program_studi',
                'nilai_min' => 'nullable|numeric|min:0|max:100',
                'nilai_max' => 'nullable|numeric|min:0|max:100|gte:nilai_min',
                'huruf' => 'nullable|string|max:2',
                'skor' => 'nullable|numeric|min:0|max:4',
            ]);

            $id = (int) Crypt::decryptString($validated['code']);
            unset($validated['code']);

            $grade = $this->service->show($id);
            if (! $grade) {
                return ApiResponse::notFound('Grade tidak ditemukan.');
            }

            $checkProdi = $validated['kode_program_studi'] ?? $grade->kode_program_studi;
            $checkMin = $validated['nilai_min'] ?? $grade->nilai_min;
            $checkMax = $validated['nilai_max'] ?? $grade->nilai_max;

            if ($this->service->isOverlap($checkProdi, $checkMin, $checkMax, $id)) {
                return ApiResponse::validation(
                    ['nilai_min' => ['Range grade overlap dengan grade lain di prodi ini.']],
                    'Range grade tidak valid.'
                );
            }

            $grade = $this->service->update($id, $validated);

            return ApiResponse::success([
                'code' => Crypt::encryptString((string) $grade->id),
                'kode_program_studi' => $grade->kode_program_studi,
                'nama_program_studi' => $grade->programStudi?->nama_program_studi,
                'nilai_min' => $grade->nilai_min,
                'nilai_max' => $grade->nilai_max,
                'huruf' => $grade->huruf,
                'skor' => $grade->skor,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
            ], 'Grade berhasil diperbarui.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['grade' => ['Grade untuk huruf ini sudah ada di prodi ini.']],
                    'Grade sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat memperbarui grade. Silakan coba lagi.', 500);
        }
    }

    /**
     * DELETE /staff/assessment/grade/{code}
     */
    public function destroy(string $code): JsonResponse
    {
        try {
            $id = (int) Crypt::decryptString($code);
            $deleted = $this->service->destroy($id);

            if (! $deleted) {
                return ApiResponse::notFound('Grade tidak ditemukan.');
            }

            return ApiResponse::success(null, 'Grade berhasil dihapus.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['grade' => ['Grade tidak dapat dihapus karena masih digunakan oleh data lain.']],
                    'Grade masih digunakan.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat menghapus grade. Silakan coba lagi.', 500);
        }
    }
}

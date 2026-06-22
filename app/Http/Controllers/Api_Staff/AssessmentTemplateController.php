<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\KurikulumAngkatan;
use App\Models\Matakuliah;
use App\Service\Assessment\TemplateBuilderService;
use App\Service\Assessment\TreeTraversalService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AssessmentTemplateController extends Controller
{
    public function __construct(
        private readonly TemplateBuilderService $builderService,
        private readonly TreeTraversalService $treeService,
    ) {}

    /**
     * GET /staff/assessment/templates
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'angkatan' => 'nullable|integer',
                'versi' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = AssessmentTemplate::select(
                'id',
                'versi',
                'id_matakuliah',
                'kode_kurikulum_angkatan',
                'created_at',
                'updated_at',
            )
                ->with('matakuliah:id_matakuliah,nama_matakuliah')
                ->with('kurikulumAngkatan')
                ->where('is_active', true);

            if ($validated['angkatan'] ?? null) {
                $query->whereHas('kurikulumAngkatan', function ($q) use ($validated) {
                    $q->where('angkatan', $validated['angkatan']);
                });
            }

            $perPage = $validated['per_page'] ?? 15;
            $paginator = $query->orderByDesc('created_at')->paginate($perPage);

            $paginator->getCollection()->transform(function ($template, $index) {
                return [
                    'id' => $index + 1,
                    'code' => Crypt::encryptString($template->id),
                    'versi' => $template->versi,
                    'matakuliah' => $template->matakuliah?->nama_matakuliah,
                    'kurikulum_angkatan' => $template->kurikulumAngkatan?->angkatan,
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ];
            });

            return ApiResponse::paginated($paginator, 'Daftar template berhasil diambil.');
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil data template. Silakan coba lagi.', 500);
        }
    }

    /**
     * POST /staff/assessment/templates
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code_matakuliah' => 'required|string',
                'code_kurikulum_angkatan' => 'required|string',
                'structure' => 'required|array',
            ]);

            try {
                $id_matakuliah = (int) Crypt::decryptString($validated['code_matakuliah']);
                $kode_kurikulum_angkatan = (int) Crypt::decryptString($validated['code_kurikulum_angkatan']);
            } catch (DecryptException) {
                return ApiResponse::validation([
                    'code_matakuliah' => ['Format kode matakuliah tidak valid.'],
                    'code_kurikulum_angkatan' => ['Format kode kurikulum angkatan tidak valid.'],
                ]);
            }

            $errors = [];
            if (! Matakuliah::where('id_matakuliah', $id_matakuliah)->exists()) {
                $errors['code_matakuliah'] = ['Matakuliah tidak ditemukan.'];
            }
            if (! KurikulumAngkatan::where('kode_kurikulum_angkatan', $kode_kurikulum_angkatan)->exists()) {
                $errors['code_kurikulum_angkatan'] = ['Kurikulum angkatan tidak ditemukan.'];
            }
            if (! empty($errors)) {
                return ApiResponse::validation($errors, 'Referensi data tidak valid.');
            }

            $template = $this->builderService->createTemplate(
                $id_matakuliah,
                $kode_kurikulum_angkatan,
                $validated['structure'],
            );

            $template->load('matakuliah:id_matakuliah,nama_matakuliah', 'kurikulumAngkatan');

            return ApiResponse::success([
                'id' => 1,
                'code' => Crypt::encryptString($template->id),
                'code_matakuliah' => Crypt::encryptString((string) $template->id_matakuliah),
                'code_kurikulum_angkatan' => Crypt::encryptString((string) $template->kode_kurikulum_angkatan),
                'versi' => $template->versi,
                'is_active' => $template->is_active,
                'matakuliah' => $template->matakuliah?->nama_matakuliah,
                'kurikulum_angkatan' => $template->kurikulumAngkatan?->angkatan,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ], 'Template berhasil dibuat.', 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::validation(['structure' => [$e->getMessage()]], 'Struktur tidak valid.');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['template' => ['Template untuk matakuliah dan kurikulum angkatan ini sudah ada.']],
                    'Template sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat membuat template. Silakan coba lagi.', 500);
        }
    }

    /**
     * GET /staff/assessment/templates/show?code=<encrypted>
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
            ]);

            $id = Crypt::decryptString($validated['code']);

            $template = AssessmentTemplate::with(
                'matakuliah:id_matakuliah,nama_matakuliah',
                'kurikulumAngkatan',
            )->find($id);

            if (! $template) {
                return ApiResponse::notFound('Template tidak ditemukan.');
            }

            return ApiResponse::success([
                'template' => [
                    'id' => 1,
                    'code' => Crypt::encryptString($template->id),
                    'versi' => $template->versi,
                    'is_active' => $template->is_active,
                    'matakuliah' => $template->matakuliah?->nama_matakuliah,
                    'kurikulum_angkatan' => $template->kurikulumAngkatan?->angkatan,
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ],
                'structure' => $template->structure,
                'tree' => $this->treeService->buildTree($template),
                'leaf_nodes' => $this->treeService->getLeafNodes($template),
            ], 'Template berhasil diambil.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat mengambil template. Silakan coba lagi.', 500);
        }
    }

    /**
     * PUT /staff/assessment/templates/update?code=<encrypted>
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string'],
                'structure' => 'required|array',
            ]);

            $id = Crypt::decryptString($validated['code']);

            $template = AssessmentTemplate::find($id);

            if (! $template) {
                return ApiResponse::notFound('Template tidak ditemukan.');
            }

            $newTemplate = $this->builderService->updateTemplate(
                $template,
                $validated['structure'],
            );

            $newTemplate->load('matakuliah:id_matakuliah,nama_matakuliah', 'kurikulumAngkatan');

            return ApiResponse::success([
                'id' => 1,
                'code' => Crypt::encryptString($newTemplate->id),
                'code_matakuliah' => Crypt::encryptString((string) $newTemplate->id_matakuliah),
                'code_kurikulum_angkatan' => Crypt::encryptString((string) $newTemplate->kode_kurikulum_angkatan),
                'versi' => $newTemplate->versi,
                'is_active' => $newTemplate->is_active,
                'matakuliah' => $newTemplate->matakuliah?->nama_matakuliah,
                'kurikulum_angkatan' => $newTemplate->kurikulumAngkatan?->angkatan,
                'created_at' => $newTemplate->created_at,
                'updated_at' => $newTemplate->updated_at,
            ], 'Versi baru berhasil dibuat.');
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => ['Format kode tidak valid.']]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::validation(['structure' => [$e->getMessage()]], 'Struktur tidak valid.');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return ApiResponse::validation(
                    ['template' => ['Template versi baru untuk matakuliah dan kurikulum angkatan ini gagal dibuat. Silakan cek kembali data yang ada.']],
                    'Template sudah terdaftar.'
                );
            }

            return ApiResponse::error('Terjadi kesalahan database. Silakan coba lagi.', 500);
        } catch (\Exception $e) {
            report($e);
            return ApiResponse::error('Terjadi kesalahan saat memperbarui template. Silakan coba lagi.', 500);
        }
    }
}

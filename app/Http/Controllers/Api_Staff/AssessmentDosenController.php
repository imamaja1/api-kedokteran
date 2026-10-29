<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDosen;
use App\Models\Kelas;
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
        $this->ensureCodeKelasInput($request);

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
        $this->normalizeAssignInput($request);

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
     * GET /api/staff/assessment/dosen/check?code_kelas=
     * GET /api/staff/assessment/dosen/status?code_kelas=
     * GET /api/staff/assessment/dosen/check/{code}
     */
    public function checkStatus(Request $request, ?string $code = null): JsonResponse
    {
        if ($code && ! $request->filled('code_kelas')) {
            $request->merge(['code_kelas' => $code]);
        }

        $this->ensureCodeKelasInput($request);

        $validated = $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->service->checkStatus($validated['code_kelas']);
    }

    /**
     * GET /api/staff/assessment/dosen/assignments?code_kelas=
     * GET /api/staff/assessment/dosen/show?code=
     * GET /api/staff/assessment/dosen/{code}
     */
    public function getAssignments(Request $request, ?string $code = null): JsonResponse
    {
        if ($code && ! $request->filled('code_kelas')) {
            $request->merge(['code_kelas' => $code]);
        }

        $this->ensureCodeKelasInput($request);

        $validated = $request->validate([
            'code_kelas' => 'required|string',
        ]);

        return $this->service->getAssignments($validated['code_kelas']);
    }

    /**
     * Normalisasi payload assign dosen (mendukung camelCase & resolusi via code_kelas)
     */
    private function normalizeAssignInput(Request $request): void
    {
        // 1. Normalisasi assessment_dosen_id (camelCase / alias)
        if (! $request->filled('assessment_dosen_id')) {
            $assessmentId = $request->input('assessmentDosenId')
                ?? $request->input('assessment_id')
                ?? $request->input('assessmentId');

            // Fallback: Jika frontend mengirim code_kelas, cari assessment_dosen_id aktif kelas tsb
            if (! $assessmentId) {
                $codeKelas = $request->input('code_kelas')
                    ?? $request->input('codeKelas')
                    ?? $request->input('code');

                if ($codeKelas) {
                    $kelas = Kelas::findByCode($codeKelas);
                    if ($kelas) {
                        $assessmentId = AssessmentDosen::where('kelas_id', $kelas->kelas_id)
                            ->active()
                            ->orderByDesc('id')
                            ->value('id');
                    }
                }
            }

            if ($assessmentId) {
                $request->merge(['assessment_dosen_id' => (int) $assessmentId]);
            }
        }

        // 2. Normalisasi elemen array assignments (nodeKey -> node_key, kodeDosen -> kode_dosen)
        $assignments = $request->input('assignments');
        if (is_array($assignments)) {
            $normalized = [];
            foreach ($assignments as $item) {
                if (! is_array($item)) {
                    $normalized[] = $item;
                    continue;
                }
                $normalized[] = [
                    'node_key' => $item['node_key'] ?? $item['nodeKey'] ?? $item['key'] ?? null,
                    'kode_dosen' => $item['kode_dosen'] ?? $item['kodeDosen'] ?? $item['dosen_id'] ?? null,
                ];
            }
            $request->merge(['assignments' => $normalized]);
        }
    }

    /**
     * Normalisasi dan deteksi code_kelas dari berbagai kemungkinan:
     * 1. Case-insensitive & alias: code_kelas, codeKelas, Code_Kelas, code, kelas_id
     * 2. Fallback REQUEST_URI jika Nginx try_files tidak menyertakan $query_string
     */
    private function ensureCodeKelasInput(Request $request): void
    {
        if ($request->filled('code_kelas')) {
            return;
        }

        $candidates = ['code_kelas', 'codekelas', 'code', 'kelas_id', 'code_class'];

        // Cek semua input (query string maupun body) secara case-insensitive
        foreach ($request->all() as $key => $value) {
            $normalizedKey = strtolower(str_replace(['-', ' '], '_', (string) $key));
            if (in_array($normalizedKey, $candidates, true) && ! empty($value) && is_string($value)) {
                $request->merge(['code_kelas' => $value]);
                return;
            }
        }

        // Fallback: Parse query string langsung dari server REQUEST_URI
        // (mengatasi Nginx server yang salah konfigurasi `try_files $uri $uri/ /index.php;`)
        $requestUri = (string) (
            ($_SERVER['REQUEST_URI'] ?? '')
            ?: $request->server('REQUEST_URI')
            ?: $request->getRequestUri()
        );
        $rawQuery = parse_url($requestUri, PHP_URL_QUERY);
        if (! $rawQuery && str_contains($requestUri, '?')) {
            $rawQuery = substr($requestUri, strpos($requestUri, '?') + 1);
        }

        if ($rawQuery) {
            parse_str($rawQuery, $parsedQuery);
            foreach ($parsedQuery as $key => $value) {
                $normalizedKey = strtolower(str_replace(['-', ' '], '_', (string) $key));
                if (in_array($normalizedKey, $candidates, true) && ! empty($value) && is_string($value)) {
                    $request->merge(['code_kelas' => $value]);
                    return;
                }
            }
        }
    }
}

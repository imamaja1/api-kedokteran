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
     * POST /api/dosen/assessment-dosen/input-score
     */
    public function inputScore(Request $request): JsonResponse
    {
        // Normalisasi format batch (1 nilai/scores untuk banyak mahasiswa sekaligus)
        $this->normalizeBatchInputScoreRequest($request);

        $validated = $request->validate([
            'assessment_dosen_id'           => 'required|integer',
            'node_key'                      => 'nullable|string',
            'mahasiswa'                     => 'required|array|min:1',
            'mahasiswa.*.code_mahasiswa'    => 'required|string',
            'mahasiswa.*.scores'            => 'required|array|min:1',
            'mahasiswa.*.scores.*.node_key' => 'required|string',
            'mahasiswa.*.scores.*.score'    => 'required|numeric|min:0|max:100',
            'mahasiswa.*.scores.*.notes'    => 'nullable|string',
            'mahasiswa.*.scores.*.catatan'  => 'nullable|string',
            'mahasiswa.*.catatan'           => 'nullable|string',
        ]);

        return $this->service->inputScore(
            $validated['assessment_dosen_id'],
            $validated['node_key'] ?? null,
            $validated['mahasiswa'],
            $this->getKodeDosen()
        );
    }

    /**
     * Normalisasi payload request agar mendukung:
     * 1. Multi-component per mahasiswa (Best Practice): { assessment_dosen_id, mahasiswa: [ { code_mahasiswa, scores: [{node_key, score}], catatan } ] }
     * 2. Key-Value Map scores: { assessment_dosen_id, mahasiswa: [ { code_mahasiswa, scores: { "tugas": 85, "uts": 80 } } ] }
     * 3. Flat Format per mahasiswa: { assessment_dosen_id, node_key, mahasiswa: [ { code_mahasiswa, score, notes } ] }
     * 4. Self-Contained Item Format: { mahasiswa: [ { assessment_dosen_id, node_key, code_mahasiswa, score } ] }
     * 5. Shared Batch Format: { assessment_dosen_id, node_key, mahasiswa_codes: [...], score: 85 }
     * 6. Code Kelas Fallback: { code_kelas, ... } -> otomatis resolve assessment_dosen_id
     */
    private function normalizeBatchInputScoreRequest(Request $request): void
    {
        // 1. Fallback & Hoist assessment_dosen_id jika belum ada di root
        if (! $request->has('assessment_dosen_id') || empty($request->input('assessment_dosen_id'))) {
            $firstMhs = $request->input('mahasiswa.0');
            if (is_array($firstMhs) && ! empty($firstMhs['assessment_dosen_id'])) {
                $request->merge(['assessment_dosen_id' => (int) $firstMhs['assessment_dosen_id']]);
            } elseif ($request->has('code_kelas') && ! empty($request->input('code_kelas'))) {
                $kelas = \App\Models\Kelas::findByCode($request->input('code_kelas'));
                if ($kelas) {
                    $assess = \App\Models\AssessmentDosen::where('kelas_id', $kelas->kelas_id)->active()->first();
                    if ($assess) {
                        $request->merge(['assessment_dosen_id' => $assess->id]);
                    }
                }
            }
        }

        // 2. Hoist node_key dari elemen mahasiswa jika di root belum ada
        if (! $request->has('node_key') || empty($request->input('node_key'))) {
            $firstMhs = $request->input('mahasiswa.0');
            if (is_array($firstMhs) && ! empty($firstMhs['node_key'])) {
                $request->merge(['node_key' => (string) $firstMhs['node_key']]);
            }
        }

        $rootNodeKey = $request->input('node_key');

        // Kasus A: Format per mahasiswa (Nested, Dictionary Map, atau Flat)
        if ($request->has('mahasiswa') && is_array($request->input('mahasiswa'))) {
            $firstItem = $request->input('mahasiswa.0');
            if (is_array($firstItem)) {
                $mahasiswa = $request->input('mahasiswa');
                $modified = false;

                foreach ($mahasiswa as &$item) {
                    if (is_array($item)) {
                        // Sub-kasus A1: scores berupa Dictionary/Map { "tugas": 85, "uts": 80 }
                        if (isset($item['scores']) && is_array($item['scores']) && ! array_is_list($item['scores'])) {
                            $convertedScores = [];
                            foreach ($item['scores'] as $nKey => $sVal) {
                                if (is_array($sVal)) {
                                    $convertedScores[] = [
                                        'node_key' => $sVal['node_key'] ?? (string) $nKey,
                                        'score'    => $sVal['score'] ?? 0,
                                        'notes'    => $sVal['notes'] ?? $sVal['catatan'] ?? null,
                                    ];
                                } else {
                                    $convertedScores[] = [
                                        'node_key' => (string) $nKey,
                                        'score'    => $sVal,
                                        'notes'    => null,
                                    ];
                                }
                            }
                            $item['scores'] = $convertedScores;
                            $modified = true;
                        }
                        // Sub-kasus A2: Flat score langsung di object mahasiswa { code_mahasiswa, score, notes/catatan }
                        elseif (isset($item['score']) && (! isset($item['scores']) || ! is_array($item['scores']))) {
                            $item['scores'] = [
                                [
                                    'node_key' => $item['node_key'] ?? $rootNodeKey,
                                    'score'    => $item['score'],
                                    'notes'    => $item['notes'] ?? $item['catatan'] ?? null,
                                ],
                            ];
                            $modified = true;
                        }
                        // Sub-kasus A3: scores sudah array list of objects, normalisasi notes/catatan dan default node_key
                        elseif (isset($item['scores']) && is_array($item['scores'])) {
                            foreach ($item['scores'] as &$s) {
                                if (is_array($s)) {
                                    if (empty($s['node_key']) && ! empty($rootNodeKey)) {
                                        $s['node_key'] = $rootNodeKey;
                                        $modified = true;
                                    }
                                    if (isset($s['catatan']) && (! isset($s['notes']) || $s['notes'] === null)) {
                                        $s['notes'] = $s['catatan'];
                                        $modified = true;
                                    }
                                }
                            }
                            unset($s);
                        }
                    }
                }
                unset($item);

                if ($modified) {
                    $request->merge(['mahasiswa' => $mahasiswa]);
                }

                return;
            }
        }

        // Kasus B: Shared Batch Format (1 nilai seragam untuk daftar banyak mahasiswa)
        $rawCodes = $request->input('mahasiswa_codes')
            ?? $request->input('code_mahasiswa_list')
            ?? (is_array($request->input('code_mahasiswa')) ? $request->input('code_mahasiswa') : null);

        if (! $rawCodes && $request->has('mahasiswa') && is_array($request->input('mahasiswa'))) {
            $firstItem = $request->input('mahasiswa.0');
            if (is_string($firstItem)) {
                $rawCodes = $request->input('mahasiswa');
            }
        }

        if (empty($rawCodes)) {
            return;
        }

        $codes = is_array($rawCodes) ? $rawCodes : [$rawCodes];

        $scores = null;
        if ($request->has('scores') && is_array($request->input('scores'))) {
            $scores = $request->input('scores');
        } elseif ($request->has('score')) {
            $scores = [
                [
                    'node_key' => $rootNodeKey,
                    'score'    => $request->input('score'),
                    'notes'    => $request->input('notes') ?? $request->input('catatan'),
                ],
            ];
        }

        if ($scores !== null) {
            foreach ($scores as &$sItem) {
                if (is_array($sItem)) {
                    if (empty($sItem['node_key']) && ! empty($rootNodeKey)) {
                        $sItem['node_key'] = $rootNodeKey;
                    }
                    if (isset($sItem['catatan']) && (! isset($sItem['notes']) || $sItem['notes'] === null)) {
                        $sItem['notes'] = $sItem['catatan'];
                    }
                }
            }
            unset($sItem);

            $mahasiswa = [];
            foreach ($codes as $code) {
                if (is_string($code) && trim($code) !== '') {
                    $mahasiswa[] = [
                        'code_mahasiswa' => trim($code),
                        'scores'         => $scores,
                    ];
                }
            }

            if (! empty($mahasiswa)) {
                $request->merge(['mahasiswa' => $mahasiswa]);
            }
        }
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
     * POST /api/dosen/assessment-dosen/validasi-bulk
     */
    public function validasiBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_dosen_id' => 'required|integer',
            'node_key'            => 'required|string',
            'catatan'             => 'nullable|string',
        ]);

        return $this->service->validasiBulk(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
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

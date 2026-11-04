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
            'node_key'                      => 'required|string',
            'mahasiswa'                     => 'required|array|min:1',
            'mahasiswa.*.code_mahasiswa'    => 'required|string',
            'mahasiswa.*.scores'            => 'required|array|min:1',
            'mahasiswa.*.scores.*.node_key' => 'required|string',
            'mahasiswa.*.scores.*.score'    => 'required|numeric|min:0|max:100',
            'mahasiswa.*.scores.*.notes'    => 'nullable|string',
        ]);

        return $this->service->inputScore(
            $validated['assessment_dosen_id'],
            $validated['node_key'],
            $validated['mahasiswa'],
            $this->getKodeDosen()
        );
    }

    /**
     * Normalisasi payload request agar mendukung:
     * 1. Flat Format per mahasiswa: { mahasiswa: [ { code_mahasiswa, score, notes } ] }
     * 2. Shared Batch Format: { mahasiswa_codes: [...], score: 85 } atau { mahasiswa: ["code1", "code2"], score: 85 }
     * 3. Nested Format asli (backward-compatible): { mahasiswa: [ { code_mahasiswa, scores: [...] } ] }
     */
    private function normalizeBatchInputScoreRequest(Request $request): void
    {
        $rootNodeKey = $request->input('node_key');

        // Kasus A: Flat format per mahasiswa di mana setiap mahasiswa memiliki 'score' sendiri langsung di objeknya
        if ($request->has('mahasiswa') && is_array($request->input('mahasiswa'))) {
            $firstItem = $request->input('mahasiswa.0');
            // Jika elemen mahasiswa berbentuk array/object (bukan array string)
            if (is_array($firstItem)) {
                $mahasiswa = $request->input('mahasiswa');
                $modified = false;

                foreach ($mahasiswa as &$item) {
                    if (is_array($item)) {
                        // Jika mahasiswa memiliki field 'score' langsung tanpa pembungkus 'scores'
                        if (isset($item['score']) && (! isset($item['scores']) || ! is_array($item['scores']))) {
                            $item['scores'] = [
                                [
                                    'node_key' => $item['node_key'] ?? $rootNodeKey,
                                    'score'    => $item['score'],
                                    'notes'    => $item['notes'] ?? null,
                                ],
                            ];
                            $modified = true;
                        } elseif (isset($item['scores']) && is_array($item['scores'])) {
                            // Lengkapi node_key jika di dalam item scores kosong
                            foreach ($item['scores'] as &$s) {
                                if (is_array($s) && empty($s['node_key']) && ! empty($rootNodeKey)) {
                                    $s['node_key'] = $rootNodeKey;
                                    $modified = true;
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

                // Format per-mahasiswa sudah dinormalisasi, selesai
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

        // Ambil scores seragam (bisa dari array 'scores' atau field tunggal 'score' di root)
        $scores = null;
        if ($request->has('scores') && is_array($request->input('scores'))) {
            $scores = $request->input('scores');
        } elseif ($request->has('score')) {
            $scores = [
                [
                    'node_key' => $rootNodeKey,
                    'score'    => $request->input('score'),
                    'notes'    => $request->input('notes'),
                ],
            ];
        }

        if ($scores !== null) {
            foreach ($scores as &$sItem) {
                if (is_array($sItem) && empty($sItem['node_key']) && ! empty($rootNodeKey)) {
                    $sItem['node_key'] = $rootNodeKey;
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

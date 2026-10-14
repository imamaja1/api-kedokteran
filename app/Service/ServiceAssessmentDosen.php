<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentDosen;
use App\Models\AssessmentDosenAssignment;
use App\Models\AssessmentNodeValidation;
use App\Models\AssessmentTemplate;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KhsDetail;
use App\Models\Mahasiswa;
use App\Models\Mengajar;
use App\Models\PenilaianStatus;
use App\Models\StudentScore;
use App\Models\ValidationStudentScore;
use App\Service\Assessment\AssessmentTreeBuilderService;
use App\Service\Assessment\ScoreCalculationService;
use App\Service\Assessment\TreeTraversalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceAssessmentDosen
{
    public function __construct(
        private readonly TreeTraversalService $treeService,
        private readonly ScoreCalculationService $scoreService,
        private readonly AssessmentTreeBuilderService $treeBuilder,
    ) {}

    // ==================== STAFF: Setup ====================

    public function createAssessment(string $codeKelas): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->orderByDesc('versi')
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template assessment untuk matakuliah ini belum tersedia.');
        }

        $exists = AssessmentDosen::where('template_id', $template->id)
            ->where('kelas_id', $kelas->kelas_id)
            ->active()
            ->first();

        if ($exists) {
            return ApiResponse::error('AssessmentDosen untuk kelas ini sudah ada.', 422, [
                'id'                  => $exists->id,
                'assessment_dosen_id' => $exists->id,
                'code_kelas'          => $kelas->toCode(),
            ]);
        }

        $assessment = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id'    => $kelas->kelas_id,
            'status'      => 'aktif',
        ]);

        return ApiResponse::success([
            'id'                  => $assessment->id,
            'assessment_dosen_id' => $assessment->id,
            'code_kelas'          => $kelas->toCode(),
            'nama_kelas'          => $kelas->namaKelas?->nama_kelas,
            'matakuliah'          => $kelas->matakuliah?->nama_matakuliah,
        ], 'AssessmentDosen berhasil dibuat.', 201);
    }

    public function assignDosen(int $assessmentId, array $assignments): JsonResponse
    {
        $assessment = AssessmentDosen::find($assessmentId);
        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen tidak ditemukan.');
        }

        $template = $assessment->template;

        $assigned = [];
        $errors   = [];

        // De-duplicate incoming assignments by node_key (latest entry takes precedence)
        $uniqueAssignments = [];
        foreach ($assignments as $assignment) {
            $uniqueAssignments[$assignment['node_key']] = $assignment['kode_dosen'];
        }

        foreach ($uniqueAssignments as $nodeKey => $kodeDosen) {
            $node = $this->treeService->findNode($template, $nodeKey);
            if (! $node) {
                $errors[] = "Node '{$nodeKey}' tidak ditemukan di template.";
                continue;
            }

            if (($node['type'] ?? null) === 'input') {
                $errors[] = "Node '{$nodeKey}' adalah input node, tidak bisa ditugaskan dosen.";
                continue;
            }

            $mengajar = Mengajar::where('kelas_id', $assessment->kelas_id)
                ->where('kode_dosen', $kodeDosen)
                ->whereHas('dosen')
                ->exists();
            if (! $mengajar) {
                $errors[] = "Dosen {$kodeDosen} tidak mengajar di kelas ini atau tidak aktif.";
                continue;
            }

            AssessmentDosenAssignment::updateOrCreate(
                [
                    'assessment_dosen_id' => $assessment->id,
                    'node_key'            => $nodeKey,
                ],
                ['kode_dosen' => $kodeDosen]
            );

            $assigned[] = ['node_key' => $nodeKey, 'kode_dosen' => $kodeDosen];
        }

        if (! empty($errors)) {
            return ApiResponse::error('Beberapa assignment gagal: ' . implode('; ', $errors), 422);
        }

        return ApiResponse::success($assigned, 'Dosen berhasil ditugaskan ke node.');
    }

    public function getAssignments(string $codeKelas): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $assessment = AssessmentDosen::with(['template', 'assignments.dosen'])
            ->where('kelas_id', $kelas->kelas_id)
            ->active()
            ->orderByDesc('id')
            ->first();

        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen belum dibuat untuk kelas ini.');
        }

        $assignedMap = [];
        $assignments = $assessment->assignments->map(function ($a) use (&$assignedMap) {
            $item = [
                'id'            => $a->id,
                'node_key'      => $a->node_key,
                'kode_dosen'    => $a->kode_dosen,
                'nama_dosen'    => $a->dosen?->nama_dosen,
                'alamat_email'  => $a->dosen?->alamat_email,
            ];
            $assignedMap[$a->node_key] = [
                'kode_dosen'   => $a->kode_dosen,
                'nama_dosen'   => $a->dosen?->nama_dosen,
                'alamat_email' => $a->dosen?->alamat_email,
            ];
            return $item;
        })->values();

        $dosenPengajar = Mengajar::where('kelas_id', $kelas->kelas_id)
            ->whereHas('dosen')
            ->with('dosen:kode_dosen,nama_dosen,nik,alamat_email')
            ->get()
            ->map(fn ($m) => [
                'kode_dosen'   => $m->dosen->kode_dosen,
                'nama_dosen'   => $m->dosen->nama_dosen,
                'nik'          => $m->dosen->nik,
                'alamat_email' => $m->dosen->alamat_email,
            ])
            ->unique('kode_dosen')
            ->values();

        $availableNodes = [];
        $treeView       = null;
        if ($assessment->template) {
            $availableNodes = $this->treeService->getAssignableNodes($assessment->template, $assignedMap);
            $treeView       = $this->treeService->buildTreeWithAssignments($assessment->template, $assignedMap);
        }

        $sampleNodeKey = null;
        if (!empty($availableNodes)) {
            $categoryNode = collect($availableNodes)->firstWhere('type', 'category');
            $sampleNodeKey = $categoryNode['node_key'] ?? $availableNodes[0]['node_key'];
        }

        $sampleKodeDosen = $dosenPengajar->first()['kode_dosen'] ?? 0;

        return ApiResponse::success([
            'assessment_dosen_id' => $assessment->id,
            'kelas' => [
                'code_kelas'      => $kelas->toCode(),
                'nama_kelas'      => $kelas->namaKelas?->nama_kelas,
                'semester'        => $kelas->semester,
                'matakuliah'      => $kelas->matakuliah?->nama_matakuliah,
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
            ],
            'template' => [
                'template_id'     => $assessment->template?->id,
                'versi'           => $assessment->template?->versi,
                'root_key'        => $assessment->template?->structure['key'] ?? null,
                'root_name'       => $assessment->template?->structure['name'] ?? null,
            ],
            'dosen_pengajar'      => $dosenPengajar,
            'available_nodes'     => $availableNodes,
            'assignments'         => $assignments,
            'tree_view'           => $treeView,
            'payload_sample_put'  => [
                'assessment_dosen_id' => $assessment->id,
                'assignments'         => [
                    [
                        'node_key'   => $sampleNodeKey ?? 'kompetensi_inti',
                        'kode_dosen' => $sampleKodeDosen,
                    ],
                ],
            ],
        ], 'Daftar assignment berhasil diambil.');
    }

    // ==================== DOSEN: Monitoring & Validasi ====================

    public function getMyNodes(int $kodeDosen): JsonResponse
    {
        $assignments = AssessmentDosenAssignment::with([
            'assessmentDosen.kelas.namaKelas',
            'assessmentDosen.kelas.matakuliah',
            'assessmentDosen.template',
        ])
            ->where('kode_dosen', $kodeDosen)
            ->whereHas('assessmentDosen', fn ($q) => $q->where('status', 'aktif'))
            ->get();

        if ($assignments->isEmpty()) {
            return ApiResponse::success([], 'Anda tidak memiliki tugas validasi.');
        }

        $data = $assignments->map(function ($a) {
            $kelas = $a->assessmentDosen->kelas;
            $assessment = $a->assessmentDosen;
            $template = $assessment->template;
            $node = $this->treeService->findNode($template, $a->node_key);

            $buka = $assessment->tanggal_buka ?? $kelas->tahunAkademik?->tanggal_buka_penilaian;
            $tutup = $assessment->tanggal_tutup ?? $kelas->tahunAkademik?->tanggal_tutup_penilaian;
            $isOpen = $assessment->isOpen();
            $sisaHari = null;
            if ($tutup) {
                $now = now()->startOfDay();
                $end = \Carbon\Carbon::parse($tutup)->startOfDay();
                $sisaHari = (int) $now->diffInDays($end, false);
            }

            return [
                'assessment_dosen_id' => $a->assessment_dosen_id,
                'code_kelas'          => $kelas->toCode(),
                'nama_kelas'          => $kelas->namaKelas?->nama_kelas,
                'matakuliah'          => $kelas->matakuliah?->nama_matakuliah,
                'node_key'            => $a->node_key,
                'node_name'           => $node['name'] ?? null,
                'node_type'           => $node['type'] ?? 'category',
                'status_kelas'        => $assessment->status,
                'is_open'             => $isOpen,
                'tanggal_buka'        => $buka ? \Carbon\Carbon::parse($buka)->toDateString() : null,
                'tanggal_tutup'       => $tutup ? \Carbon\Carbon::parse($tutup)->toDateString() : null,
                'sisa_hari'           => $sisaHari,
            ];
        });

        return ApiResponse::success($data, 'Daftar node validasi berhasil diambil.');
    }

    public function getMahasiswaByNode(int $assessmentId, string $nodeKey, int $kodeDosen): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $assessment = AssessmentDosen::with(['kelas.tahunAkademik'])->find($assessmentId);
        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen tidak ditemukan.');
        }
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }

        $template = $assessment->template;
        $node = $this->treeService->findNode($template, $nodeKey);

        $kelasMahasiswa = KelasMahasiswa::with([
            'krsDetail.krs.mahasiswa'
        ])->where('kelas_id', $assessment->kelas_id)->get();

        $validations = AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->get()
            ->keyBy('nim');

        $childKeys = $this->getChildNodeKeys($node);

        $scores = StudentScore::where('template_id', $template->id)
            ->whereIn('nim', $kelasMahasiswa->pluck('krsDetail.krs.nim')->filter())
            ->whereIn('node_key', $childKeys)
            ->get()
            ->groupBy('nim');

        $totalInputNodes = count(array_filter($childKeys, fn ($k) => $this->isInputNode($template, $k)));

        $data = $kelasMahasiswa->map(function ($km) use ($assessmentId, $nodeKey, $validations, $scores, $totalInputNodes) {
            $mahasiswa = $km->krsDetail?->krs?->mahasiswa;
            if (! $mahasiswa) {
                return null;
            }

            $validation = $validations->get($mahasiswa->nim);
            $studentScores = $scores->get($mahasiswa->nim, collect());
            $filledCount = $studentScores->whereNotNull('score')->count();

            return [
                'code_mahasiswa' => $mahasiswa->toCode(),
                'nim'            => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'status_node'    => $validation?->status ?? 'belum_input',
                'catatan'        => $validation?->catatan,
                'filled_nodes'   => $filledCount,
                'total_nodes'    => $totalInputNodes,
            ];
        })->filter()->values()->toArray();

        $buka = $assessment->tanggal_buka ?? $assessment->kelas?->tahunAkademik?->tanggal_buka_penilaian;
        $tutup = $assessment->tanggal_tutup ?? $assessment->kelas?->tahunAkademik?->tanggal_tutup_penilaian;
        $sisaHari = null;
        if ($tutup) {
            $now = now()->startOfDay();
            $end = \Carbon\Carbon::parse($tutup)->startOfDay();
            $sisaHari = (int) $now->diffInDays($end, false);
        }

        return ApiResponse::success([
            'assessment_dosen_id' => $assessmentId,
            'node_key'   => $nodeKey,
            'node_name'  => $node['name'],
            'schedule'   => [
                'is_open'       => $assessment->isOpen(),
                'tanggal_buka'  => $buka ? \Carbon\Carbon::parse($buka)->toDateString() : null,
                'tanggal_tutup' => $tutup ? \Carbon\Carbon::parse($tutup)->toDateString() : null,
                'sisa_hari'     => $sisaHari,
            ],
            'mahasiswa'  => $data,
        ], 'Data mahasiswa berhasil diambil.');
    }

    public function getDetailMahasiswaNode(int $assessmentId, string $nodeKey, string $codeMahasiswa, int $kodeDosen): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $mahasiswa = Mahasiswa::findByCode($codeMahasiswa);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan.');
        }

        $assessment = AssessmentDosen::find($assessmentId);
        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen tidak ditemukan.');
        }
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }
        $template   = $assessment->template;

        $scores = StudentScore::where('template_id', $template->id)
            ->where('nim', $mahasiswa->nim)
            ->get()
            ->keyBy('node_key');

        $validation = AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('nim', $mahasiswa->nim)
            ->first();

        $node = $this->treeService->findNode($template, $nodeKey);
        $subTree = $this->treeBuilder->buildTreeWithScores($template, $scores);
        $filteredTree = $this->filterSubTree($subTree, $nodeKey);

        $finalScore = $this->scoreService->calculateNodeScoreFromMap(
            $template,
            $scores->pluck('score', 'node_key')->toArray(),
            $nodeKey
        );

        return ApiResponse::success([
            'code_mahasiswa' => $mahasiswa->toCode(),
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            'node_key'       => $nodeKey,
            'node_name'      => $node['name'],
            'status_node'    => $validation?->status ?? 'belum_input',
            'catatan'        => $validation?->catatan,
            'final_score'    => $finalScore,
            'tree'           => $filteredTree,
        ], 'Detail nilai berhasil diambil.');
    }

    public function inputScore(int $assessmentId, string $nodeKey, array $mahasiswaList, int $kodeDosen): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $assessment = AssessmentDosen::with('kelas')->find($assessmentId);
        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen tidak ditemukan.');
        }
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }

        $template = $assessment->template;
        $assignedNode = $this->treeService->findNode($template, $nodeKey);
        if (! $assignedNode) {
            return ApiResponse::notFound("Node '{$nodeKey}' tidak ditemukan di template.");
        }

        // Ambil semua valid input node keys di bawah cabang node yang ditugaskan kepada dosen ini
        $allowedInputKeys = $this->getChildNodeKeys($assignedNode);

        $results = [];
        $savedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($mahasiswaList as $mhsItem) {
                $codeMahasiswa = $mhsItem['code_mahasiswa'] ?? null;
                $mahasiswa = Mahasiswa::findByCode($codeMahasiswa);
                if (! $mahasiswa) {
                    $results[] = [
                        'code_mahasiswa' => $codeMahasiswa,
                        'status'         => 'gagal',
                        'message'        => 'Mahasiswa tidak ditemukan',
                    ];
                    continue;
                }

                // Cek apakah mahasiswa terdaftar di kelas ini
                $isRegistered = KelasMahasiswa::where('kelas_id', $assessment->kelas_id)
                    ->whereHas('krsDetail.krs', fn ($q) => $q->where('nim', $mahasiswa->nim))
                    ->exists();

                if (! $isRegistered) {
                    $results[] = [
                        'code_mahasiswa' => $codeMahasiswa,
                        'nim'            => $mahasiswa->nim,
                        'status'         => 'gagal',
                        'message'        => 'Mahasiswa tidak terdaftar di kelas ini',
                    ];
                    continue;
                }

                // Cek apakah node ini sudah pernah divalidasi
                $existingVal = AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
                    ->where('node_key', $nodeKey)
                    ->where('nim', $mahasiswa->nim)
                    ->first();

                if ($existingVal && $existingVal->status === 'validasi') {
                    $results[] = [
                        'code_mahasiswa' => $codeMahasiswa,
                        'nim'            => $mahasiswa->nim,
                        'status'         => 'gagal',
                        'message'        => 'Node ini sudah divalidasi, tidak bisa diubah nilainya kecuali direvisi.',
                    ];
                    continue;
                }

                $scoresInput = $mhsItem['scores'] ?? [];
                $scoresSaved = 0;

                foreach ($scoresInput as $sItem) {
                    $targetKey = $sItem['node_key'] ?? null;
                    $scoreVal  = $sItem['score'] ?? null;

                    if (! in_array($targetKey, $allowedInputKeys, true)) {
                        continue; // Lewati jika di luar wewenang node dosen
                    }

                    StudentScore::updateOrCreate(
                        [
                            'template_id' => $template->id,
                            'nim'         => $mahasiswa->nim,
                            'node_key'    => $targetKey,
                        ],
                        [
                            'score'            => $scoreVal,
                            'notes'            => $sItem['notes'] ?? null,
                            'dosen_kode_dosen' => $kodeDosen,
                        ]
                    );
                    $scoresSaved++;
                }

                if ($scoresSaved > 0) {
                    // Update status validation ke 'proses' jika belum validasi
                    if (! $existingVal || in_array($existingVal->status, ['belum_input', 'revisi'], true)) {
                        AssessmentNodeValidation::updateOrCreate(
                            [
                                'assessment_dosen_id' => $assessmentId,
                                'node_key'            => $nodeKey,
                                'nim'                 => $mahasiswa->nim,
                            ],
                            [
                                'status' => 'proses',
                            ]
                        );
                    }

                    PenilaianStatus::updateOrCreate(
                        [
                            'kelas_id' => $assessment->kelas_id,
                            'nim'      => $mahasiswa->nim,
                        ],
                        [
                            'template_id'    => $template->id,
                            'status'         => 'proses',
                            'dosen_input_by' => $kodeDosen,
                        ]
                    );

                    $savedCount++;
                    $results[] = [
                        'code_mahasiswa' => $codeMahasiswa,
                        'nim'            => $mahasiswa->nim,
                        'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                        'scores_saved'   => $scoresSaved,
                        'status'         => 'berhasil',
                    ];
                }
            }

            DB::commit();

            return ApiResponse::success([
                'node_key'      => $nodeKey,
                'total_diproses'=> count($mahasiswaList),
                'total_berhasil'=> $savedCount,
                'detail'        => $results,
            ], 'Input nilai berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal menyimpan nilai: ' . $e->getMessage());
        }
    }

    public function validasiNode(int $assessmentId, string $nodeKey, string $codeMahasiswa, int $kodeDosen, ?string $catatan): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $mahasiswa = Mahasiswa::findByCode($codeMahasiswa);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan.');
        }

        $assessment = AssessmentDosen::find($assessmentId);
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }
        $template   = $assessment->template;

        $node = $this->treeService->findNode($template, $nodeKey);
        $canValidate = $this->canValidateNode($assessmentId, $node, $mahasiswa->nim, $template);

        if (! $canValidate['can']) {
            return ApiResponse::error($canValidate['reason'], 422);
        }

        DB::beginTransaction();
        try {
            AssessmentNodeValidation::updateOrCreate(
                [
                    'assessment_dosen_id' => $assessmentId,
                    'node_key'            => $nodeKey,
                    'nim'                 => $mahasiswa->nim,
                ],
                [
                    'status'       => 'validasi',
                    'validated_by' => $kodeDosen,
                    'validated_at' => now(),
                    'catatan'      => $catatan,
                ]
            );

            if ($nodeKey === $template->structure['key']) {
                $this->finalizeGrade($assessment, $mahasiswa, $template, $kodeDosen);
            }

            DB::commit();

            return ApiResponse::success([
                'code_mahasiswa' => $mahasiswa->toCode(),
                'node_key'       => $nodeKey,
                'status'         => 'validasi',
                'validated_at'   => now()->toIso8601String(),
            ], 'Node berhasil divalidasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal memvalidasi: ' . $e->getMessage());
        }
    }

    public function validasiBulk(int $assessmentId, string $nodeKey, int $kodeDosen, ?string $catatan): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $assessment = AssessmentDosen::find($assessmentId);
        if (! $assessment) {
            return ApiResponse::notFound('AssessmentDosen tidak ditemukan.');
        }
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }
        $template = $assessment->template;
        $node = $this->treeService->findNode($template, $nodeKey);
        if (! $node) {
            return ApiResponse::notFound("Node '{$nodeKey}' tidak ditemukan.");
        }

        $kelasMahasiswa = KelasMahasiswa::with(['krsDetail.krs.mahasiswa'])
            ->where('kelas_id', $assessment->kelas_id)
            ->get();

        $berhasil = [];
        $dilewati = [];

        DB::beginTransaction();
        try {
            foreach ($kelasMahasiswa as $km) {
                $mahasiswa = $km->krsDetail?->krs?->mahasiswa;
                if (! $mahasiswa) {
                    continue;
                }

                $canValidate = $this->canValidateNode($assessmentId, $node, $mahasiswa->nim, $template);
                if (! $canValidate['can']) {
                    $dilewati[] = [
                        'code_mahasiswa' => $mahasiswa->toCode(),
                        'nim'            => $mahasiswa->nim,
                        'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                        'alasan'         => $canValidate['reason'],
                    ];
                    continue;
                }

                AssessmentNodeValidation::updateOrCreate(
                    [
                        'assessment_dosen_id' => $assessmentId,
                        'node_key'            => $nodeKey,
                        'nim'                 => $mahasiswa->nim,
                    ],
                    [
                        'status'       => 'validasi',
                        'validated_by' => $kodeDosen,
                        'validated_at' => now(),
                        'catatan'      => $catatan,
                    ]
                );

                if ($nodeKey === $template->structure['key']) {
                    $this->finalizeGrade($assessment, $mahasiswa, $template, $kodeDosen);
                }

                $berhasil[] = [
                    'code_mahasiswa' => $mahasiswa->toCode(),
                    'nim'            => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                ];
            }

            DB::commit();

            return ApiResponse::success([
                'node_key'          => $nodeKey,
                'total_mahasiswa'   => count($kelasMahasiswa),
                'berhasil_validasi' => count($berhasil),
                'dilewati'          => count($dilewati),
                'detail_dilewati'   => $dilewati,
            ], 'Validasi massal selesai diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal memvalidasi massal: ' . $e->getMessage());
        }
    }

    public function revisiNode(int $assessmentId, string $nodeKey, string $codeMahasiswa, int $kodeDosen, string $catatan): JsonResponse
    {
        $assignment = AssessmentDosenAssignment::where('assessment_dosen_id', $assessmentId)
            ->where('node_key', $nodeKey)
            ->where('kode_dosen', $kodeDosen)
            ->first();

        if (! $assignment) {
            return ApiResponse::error('Anda tidak ditugaskan di node ini.', 403);
        }

        $mahasiswa = Mahasiswa::findByCode($codeMahasiswa);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan.');
        }

        $assessment = AssessmentDosen::find($assessmentId);
        if (! $assessment->isOpen()) {
            return ApiResponse::error('Periode penilaian sudah ditutup.', 422);
        }
        $template   = $assessment->template;

        DB::beginTransaction();
        try {
            $this->setNodeAndChildrenStatus($assessmentId, $nodeKey, $mahasiswa->nim, 'revisi', $kodeDosen, $catatan, $template);

            // Reset ancestor validations + KHS if root was validated
            $rootKey = $template->structure['key'];
            $rootValidation = AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
                ->where('node_key', $rootKey)
                ->where('nim', $mahasiswa->nim)
                ->where('status', 'validasi')
                ->first();

            if ($rootValidation) {
                $rootValidation->update([
                    'status'       => 'revisi',
                    'validated_by' => null,
                    'validated_at' => null,
                    'catatan'      => "Direvisi dari node '{$nodeKey}': {$catatan}",
                ]);

                $this->resetKhsForStudent($assessment, $mahasiswa);

                ValidationStudentScore::where('template_id', $template->id)
                    ->where('nim', $mahasiswa->nim)
                    ->update(['status' => 'revisi', 'validated_by' => null, 'validated_at' => null]);

                PenilaianStatus::where('kelas_id', $assessment->kelas_id)
                    ->where('nim', $mahasiswa->nim)
                    ->update(['status' => 'revisi']);
            }

            DB::commit();

            return ApiResponse::success([
                'code_mahasiswa' => $mahasiswa->toCode(),
                'node_key'       => $nodeKey,
                'status'         => 'revisi',
                'catatan'        => $catatan,
            ], 'Node dikembalikan untuk revisi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal merevisi: ' . $e->getMessage());
        }
    }

    // ==================== Helpers ====================

    private function getChildNodeKeys(array $node): array
    {
        $keys = [];

        if (($node['type'] ?? null) === 'input') {
            return [$node['key']];
        }

        if (! empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $keys = array_merge($keys, $this->getChildNodeKeys($child));
            }
        }

        return $keys;
    }

    private function isInputNode(AssessmentTemplate $template, string $nodeKey): bool
    {
        $node = $this->treeService->findNode($template, $nodeKey);
        return ($node['type'] ?? null) === 'input';
    }

    private function filterSubTree(array $tree, string $targetKey): ?array
    {
        if ($tree['key'] === $targetKey) {
            return $tree;
        }

        if (! empty($tree['children'])) {
            foreach ($tree['children'] as $child) {
                $result = $this->filterSubTree($child, $targetKey);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function canValidateNode(int $assessmentId, array $node, string $nim, AssessmentTemplate $template): array
    {
        if (($node['type'] ?? null) === 'input') {
            return ['can' => false, 'reason' => 'Input node tidak dapat divalidasi secara terpisah.'];
        }

        if (! empty($node['children'])) {
            foreach ($node['children'] as $child) {
                if (($child['type'] ?? null) === 'input') {
                    $score = StudentScore::where('template_id', $template->id)
                        ->where('nim', $nim)
                        ->where('node_key', $child['key'])
                        ->whereNotNull('score')
                        ->exists();

                    if (! $score) {
                        return ['can' => false, 'reason' => "Nilai untuk '{$child['name']}' belum diinput."];
                    }
                } else {
                    $childValidation = AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
                        ->where('node_key', $child['key'])
                        ->where('nim', $nim)
                        ->first();

                    if (! $childValidation || $childValidation->status !== 'validasi') {
                        return ['can' => false, 'reason' => "Node '{$child['name']}' belum divalidasi."];
                    }
                }
            }
        }

        return ['can' => true, 'reason' => ''];
    }

    private function setNodeAndChildrenStatus(int $assessmentId, string $nodeKey, string $nim, string $status, int $by, string $catatan, AssessmentTemplate $template): void
    {
        AssessmentNodeValidation::updateOrCreate(
            [
                'assessment_dosen_id' => $assessmentId,
                'node_key'            => $nodeKey,
                'nim'                 => $nim,
            ],
            [
                'status'       => $status,
                'validated_by' => $status === 'validasi' ? $by : null,
                'validated_at' => $status === 'validasi' ? now() : null,
                'catatan'      => $catatan,
            ]
        );

        // Cascade ke atas: reset ancestor yang sudah validasi
        if ($status === 'revisi') {
            $path = $this->treeService->getPath($template, $nodeKey);
            foreach ($path as $ancestor) {
                if ($ancestor['key'] === $nodeKey) {
                    continue;
                }
                AssessmentNodeValidation::where('assessment_dosen_id', $assessmentId)
                    ->where('node_key', $ancestor['key'])
                    ->where('nim', $nim)
                    ->update([
                        'status'       => 'revisi',
                        'validated_by' => null,
                        'validated_at' => null,
                    ]);
            }
        }

        // Cascade ke bawah
        $node = $this->treeService->findNode($template, $nodeKey);

        if ($node && ! empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->setNodeAndChildrenStatus($assessmentId, $child['key'], $nim, $status, $by, $catatan, $template);
            }
        }
    }

    private function resetKhsForStudent(AssessmentDosen $assessment, Mahasiswa $mahasiswa): void
    {
        $km = KelasMahasiswa::where('kelas_id', $assessment->kelas_id)
            ->whereHas('krsDetail.krs', fn ($q) => $q->where('nim', $mahasiswa->nim))
            ->first();

        if ($km?->krsDetail) {
            KhsDetail::where('kode_krs_detail', $km->krsDetail->kode_krs_detail)
                ->update([
                    'nilai_akhir'  => null,
                    'grade'        => null,
                    'score'        => null,
                    'tidak_berhak' => 'N',
                ]);
        }
    }

    private function finalizeGrade(AssessmentDosen $assessment, Mahasiswa $mahasiswa, AssessmentTemplate $template, int $kodeDosen): void
    {
        $nilaiAkhir = $this->scoreService->calculateFinalScore($template, $mahasiswa->nim);

        $serviceGrade = new ServiceGrade();
        $gradeData = $serviceGrade->konversi($nilaiAkhir, $assessment->kelas->kode_program_studi);

        $km = KelasMahasiswa::where('kelas_id', $assessment->kelas_id)
            ->whereHas('krsDetail.krs', fn ($q) => $q->where('nim', $mahasiswa->nim))
            ->first();

        if ($km?->krsDetail) {
            $krsDetail = $km->krsDetail;

            KhsDetail::updateOrCreate(
                ['kode_krs_detail' => $krsDetail->kode_krs_detail],
                [
                    'nilai_akhir'  => $nilaiAkhir,
                    'grade'        => $gradeData['grade'] ?? null,
                    'score'        => $gradeData['score'] ?? null,
                    'tidak_berhak' => 'A',
                ]
            );
        }

        ValidationStudentScore::updateOrCreate(
            ['template_id' => $template->id, 'nim' => $mahasiswa->nim],
            [
                'status'       => 'validasi',
                'validated_by' => $kodeDosen,
                'validated_at' => now(),
            ]
        );

        PenilaianStatus::updateOrCreate(
            ['kelas_id' => $assessment->kelas_id, 'nim' => $mahasiswa->nim],
            [
                'template_id'          => $template->id,
                'status'               => 'validasi',
                'dosen_input_by'       => $kodeDosen,
                'kaprodi_validated_by' => $kodeDosen,
                'validated_at'         => now(),
            ]
        );

        $totalMhs = KelasMahasiswa::where('kelas_id', $assessment->kelas_id)->count();
        $validatedMhs = AssessmentNodeValidation::where('assessment_dosen_id', $assessment->id)
            ->where('node_key', $template->structure['key'])
            ->where('status', 'validasi')
            ->count();

        if ($validatedMhs >= $totalMhs) {
            $assessment->update(['status' => 'selesai']);
        }
    }
}

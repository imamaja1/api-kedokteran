<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Mahasiswa;
use App\Models\Mengajar;
use App\Models\PenilaianStatus;
use App\Models\StudentScore;
use App\Models\ValidationStudentScore;
use App\Service\Assessment\ScoreCalculationService;
use App\Service\Assessment\TreeTraversalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServicePenilaianDosen
{
    public function __construct(
        private readonly TreeTraversalService $treeService,
        private readonly ScoreCalculationService $scoreCalculationService,
    ) {}

    public function getKelasPenilaian(int $kodeDosen): JsonResponse
    {
        $activeTA = \App\Models\TahunAkademik::active()->first();

        $query = Mengajar::where('kode_dosen', $kodeDosen)
            ->with([
                'kelas:kelas_id,nama_kelas_id,semester,kode_tahun_akademik,kode_program_studi,id_matakuliah',
                'kelas.namaKelas:nama_kelas_id,nama_kelas',
                'kelas.matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block',
                'kelas.tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'kelas.programStudi:kode_program_studi,nama_program_studi',
            ]);

        if ($activeTA) {
            $query->whereHas('kelas', function ($q) use ($activeTA) {
                $q->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik);
            });
        }

        $kelasList = $query->orderBy('kelas_id', 'desc')->get();

        $data = $kelasList->map(function ($item) {
            $kelas = $item->kelas;
            $mk = $kelas?->matakuliah;

            $totalMhs = KelasMahasiswa::where('kelas_id', $kelas?->kelas_id)->count();

            $sudahInput = PenilaianStatus::where('kelas_id', $kelas?->kelas_id)
                ->where('status', 'proses')
                ->count();

            $sudahValidasi = PenilaianStatus::where('kelas_id', $kelas?->kelas_id)
                ->where('status', 'validasi')
                ->count();

            return [
                'code_kelas' => $kelas?->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas?->semester,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
                'nama_program_studi' => $kelas->programStudi?->nama_program_studi,
                'kode_matakuliah' => $mk?->kode_matakuliah,
                'nama_matakuliah' => $mk?->nama_matakuliah,
                'block' => (bool) ($mk?->block ?? false),
                'jumlah_mahasiswa' => $totalMhs,
                'sudah_input' => $sudahInput,
                'sudah_validasi' => $sudahValidasi,
                'belum_input' => $totalMhs - $sudahInput - $sudahValidasi,
            ];
        })->values()->toArray();

        return ApiResponse::success($data, 'Kelas penilaian retrieved successfully.');
    }

    public function getMahasiswaPenilaian(string $codeKelas, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $kelasMahasiswa = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
            ->with([
                'krsDetail' => function ($q) {
                    $q->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah')
                        ->with('khsDetail');
                    $q->with(['krs' => function ($q2) {
                        $q2->with('mahasiswa:nim,nama_mahasiswa');
                    }]);
                },
            ])
            ->get();

        $data = $kelasMahasiswa->map(function ($km) use ($kelas, $template) {
            $krsDetail = $km->krsDetail;
            $khs = $krsDetail?->khsDetail;
            $krs = $krsDetail?->krs;
            $mahasiswa = $krs?->mahasiswa;

            $penilaian = PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                ->where('nim', $mahasiswa?->nim)
                ->first();

            $status = 'belum_input';
            if ($penilaian) {
                $status = $penilaian->status;
            }

            $sudahDiisi = 0;
            $totalNodes = 0;
            if ($penilaian && $template) {
                $leafNodes = $this->treeService->getLeafNodes($template);
                $totalNodes = count($leafNodes);

                $sudahDiisi = StudentScore::where('template_id', $template->id)
                    ->where('nim', $mahasiswa?->nim)
                    ->whereNotNull('score')
                    ->count();
            }

            return [
                'code_mahasiswa' => $mahasiswa?->toCode(),
                'nim' => $mahasiswa?->nim,
                'nama_mahasiswa' => $mahasiswa?->nama_mahasiswa,
                'status_mahasiswa' => $khs?->tidak_berhak == 'N' ? 'Tidak Berhak' : 'Aktif',
                'status_penilaian' => $status,
                'nilai_akhir' => $khs?->nilai_akhir,
                'catatan_dosen' => $penilaian?->catatan_dosen,
                'catatan_kaprodi' => $penilaian?->catatan_kaprodi,
                'sudah_diisi' => $sudahDiisi,
                'total_nodes' => $totalNodes,
            ];
        })->filter()->values()->toArray();

        return ApiResponse::success($data, 'Daftar mahasiswa penilaian retrieved successfully.');
    }

    public function getTemplateForKelas(string $codeKelas, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $leafNodes = $this->treeService->getLeafNodes($template);

        $data = [
            'template_id' => $template->id,
            'id_matakuliah' => $template->id_matakuliah,
            'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
            'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah,
            'structure' => $template->structure,
            'leaf_nodes' => array_map(fn ($node) => [
                'key' => $node['key'],
                'name' => $node['name'],
                'weight' => $node['weight'],
                'type' => $node['type'] ?? 'category',
            ], $leafNodes),
        ];

        return ApiResponse::success($data, 'Template penilaian retrieved successfully.');
    }

    public function inputNilai(array $payload, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($payload['code_kelas']);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $leafNodes = $this->treeService->getLeafNodes($template);
        $validNodeKeys = array_column($leafNodes, 'key');

        $results = [];

        DB::beginTransaction();

        try {
            foreach ($payload['mahasiswa'] as $item) {
                $mahasiswa = Mahasiswa::findByCode($item['code_mahasiswa']);
                if (! $mahasiswa) {
                    $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Mahasiswa tidak ditemukan'];

                    continue;
                }

                $km = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
                    ->whereHas('krsDetail.krs', function ($q) use ($mahasiswa) {
                        $q->where('nim', $mahasiswa->nim);
                    })
                    ->first();

                if (! $km) {
                    $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Mahasiswa tidak terdaftar di kelas ini'];

                    continue;
                }

                $catatanDosen = $item['catatan'] ?? null;

                $existingValidation = ValidationStudentScore::where('template_id', $template->id)
                    ->where('nim', $mahasiswa->nim)
                    ->first();

                if ($existingValidation && $existingValidation->status === 'validasi') {
                    $results[] = ['code_mahasiswa' => $mahasiswa->toCode(), 'nim' => $mahasiswa->nim, 'status' => 'Gagal', 'message' => 'Penilaian sudah divalidasi kaprodi'];

                    continue;
                }

                foreach ($item['scores'] as $scoreItem) {
                    $nodeKey = $scoreItem['node_key'];
                    $score = $scoreItem['score'];

                    if (! in_array($nodeKey, $validNodeKeys)) {
                        $results[] = ['code_mahasiswa' => $mahasiswa->toCode(), 'nim' => $mahasiswa->nim, 'status' => 'Gagal', 'message' => "Node key '{$nodeKey}' tidak valid"];

                        continue;
                    }

                    StudentScore::updateOrCreate(
                        [
                            'template_id' => $template->id,
                            'nim' => $mahasiswa->nim,
                            'node_key' => $nodeKey,
                        ],
                        [
                            'score' => $score,
                            'dosen_kode_dosen' => $kodeDosen,
                        ]
                    );
                }

                ValidationStudentScore::updateOrCreate(
                    [
                        'template_id' => $template->id,
                        'nim' => $mahasiswa->nim,
                    ],
                    [
                        'status' => 'proses',
                    ]
                );

                PenilaianStatus::updateOrCreate(
                    [
                        'kelas_id' => $kelas->kelas_id,
                        'nim' => $mahasiswa->nim,
                    ],
                    [
                        'template_id' => $template->id,
                        'status' => 'proses',
                        'dosen_input_by' => $kodeDosen,
                        'catatan_dosen' => $catatanDosen,
                        'kaprodi_validated_by' => null,
                        'validated_at' => null,
                        'catatan_kaprodi' => null,
                    ]
                );

                $results[] = [
                    'code_mahasiswa' => $mahasiswa->toCode(),
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'status' => 'Berhasil',
                ];
            }

            DB::commit();

            return ApiResponse::success($results, 'Input nilai selesai diproses.');
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function updateNilai(array $payload, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($payload['code_kelas']);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $leafNodes = $this->treeService->getLeafNodes($template);
        $validNodeKeys = array_column($leafNodes, 'key');

        $results = [];

        DB::beginTransaction();

        try {
            foreach ($payload['mahasiswa'] as $item) {
                $mahasiswa = Mahasiswa::findByCode($item['code_mahasiswa']);
                if (! $mahasiswa) {
                    $results[] = ['code_mahasiswa' => $item['code_mahasiswa'], 'status' => 'Gagal', 'message' => 'Mahasiswa tidak ditemukan'];

                    continue;
                }

                $validation = ValidationStudentScore::where('template_id', $template->id)
                    ->where('nim', $mahasiswa->nim)
                    ->first();

                if (! $validation || !in_array($validation->status, ['proses', 'revisi'])) {
                    $results[] = ['code_mahasiswa' => $mahasiswa->toCode(), 'nim' => $mahasiswa->nim, 'status' => 'Gagal', 'message' => 'Penilaian tidak dapat diupdate (sudah divalidasi atau belum diinput)'];

                    continue;
                }

                foreach ($item['scores'] as $scoreItem) {
                    $nodeKey = $scoreItem['node_key'];
                    $score = $scoreItem['score'];

                    if (! in_array($nodeKey, $validNodeKeys)) {
                        $results[] = ['code_mahasiswa' => $mahasiswa->toCode(), 'nim' => $mahasiswa->nim, 'status' => 'Gagal', 'message' => "Node key '{$nodeKey}' tidak valid"];

                        continue;
                    }

                    StudentScore::where('template_id', $template->id)
                        ->where('nim', $mahasiswa->nim)
                        ->where('node_key', $nodeKey)
                        ->update([
                            'score' => $score,
                            'dosen_kode_dosen' => $kodeDosen,
                        ]);
                }

                if (isset($item['catatan'])) {
                    $penilaian = PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                        ->where('nim', $mahasiswa->nim)
                        ->first();
                    if ($penilaian) {
                        $penilaian->update(['catatan_dosen' => $item['catatan']]);
                    }
                }

                if ($validation->status === 'revisi') {
                    $validation->update(['status' => 'proses']);

                    PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                        ->where('nim', $mahasiswa->nim)
                        ->update(['status' => 'proses']);
                }

                $results[] = [
                    'code_mahasiswa' => $mahasiswa->toCode(),
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'status' => 'Berhasil',
                ];
            }

            DB::commit();

            return ApiResponse::success($results, 'Update nilai selesai diproses.');
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function getDetailNilaiMahasiswa(string $codeKelas, string $codeMahasiswa, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $isPengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->where('kelas_id', $kelas->kelas_id)
            ->exists();

        if (! $isPengajar) {
            return ApiResponse::error('Anda tidak terdaftar sebagai pengajar di kelas ini.', 403);
        }

        $mahasiswa = Mahasiswa::findByCode($codeMahasiswa);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        // Get all scores for this student
        $scores = StudentScore::where('template_id', $template->id)
            ->where('nim', $mahasiswa->nim)
            ->get()
            ->keyBy('node_key');

        // Build tree with scores
        $data = [
            'code_mahasiswa' => $mahasiswa->toCode(),
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            'template_id' => $template->id,
            'structure' => $this->buildTreeWithScores($template, $scores),
        ];

        return ApiResponse::success($data, 'Detail nilai mahasiswa retrieved successfully.');
    }

    private function buildTreeWithScores(AssessmentTemplate $template, Collection $scores): array
    {
        return $this->attachScoresToNode($template->structure, $scores);
    }

    private function attachScoresToNode(array $node, Collection $scores): array
    {
        $result = [
            'key' => $node['key'],
            'name' => $node['name'],
            'weight' => $node['weight'],
            'type' => $node['type'] ?? 'category',
        ];

        // Jika leaf node (input type), tambahkan score
        if (($node['type'] ?? null) === 'input') {
            $score = $scores->get($node['key']);
            $result['score'] = $score?->score ?? null;
            $result['dosen_kode_dosen'] = $score?->dosen_kode_dosen;
        }

        // Jika ada children, proses rekursif
        if (! empty($node['children'])) {
            $result['children'] = array_map(
                fn ($child) => $this->attachScoresToNode($child, $scores),
                $node['children']
            );

            // Hitung score kategori jika semua children sudah input
            if (($node['type'] ?? null) !== 'input') {
                $leafScores = $this->extractLeafScoresFromChildren($result['children']);
                $calculatedScore = $this->calculateCategoryScore($result['children']);
                $result['calculated_score'] = $calculatedScore;
                $result['filled_count'] = count(array_filter($leafScores, fn ($s) => $s !== null));
                $result['total_nodes'] = count($leafScores);
            }
        }

        return $result;
    }

    private function extractLeafScoresFromChildren(array $children): array
    {
        $scores = [];

        foreach ($children as $child) {
            if (($child['type'] ?? null) === 'input') {
                $scores[] = $child['score'] ?? null;
            } elseif (! empty($child['children'])) {
                $scores = array_merge($scores, $this->extractLeafScoresFromChildren($child['children']));
            }
        }

        return $scores;
    }

    private function calculateCategoryScore(array $children): ?float
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($children as $child) {
            if (isset($child['score']) && $child['score'] !== null) {
                $totalScore += $child['score'] * ($child['weight'] / 100);
                $totalWeight += $child['weight'];
            } elseif (isset($child['calculated_score']) && $child['calculated_score'] !== null) {
                $totalScore += $child['calculated_score'] * ($child['weight'] / 100);
                $totalWeight += $child['weight'];
            }
        }

        return $totalWeight > 0 ? round($totalScore, 1) : null;
    }
}

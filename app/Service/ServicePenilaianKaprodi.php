<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KhsDetail;
use App\Models\Mahasiswa;
use App\Models\PenilaianStatus;
use App\Models\ProgramStudi;
use App\Models\StudentScore;
use App\Models\ValidationStudentScore;
use App\Service\Assessment\AssessmentTreeBuilderService;
use App\Service\Assessment\ScoreCalculationService;
use App\Service\Assessment\TreeTraversalService;
use App\Service\ServiceGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicePenilaianKaprodi
{
    public function __construct(
        private readonly TreeTraversalService $treeService,
        private readonly ScoreCalculationService $scoreCalculationService,
        private readonly AssessmentTreeBuilderService $treeBuilderService,
    ) {}

    public function getKelasPenilaian(int $kodeDosen): JsonResponse
    {
        $programStudi = ProgramStudi::where('kode_dosen_kaprodi', $kodeDosen)->first();

        if (! $programStudi) {
            return ApiResponse::error('Anda bukan Kaprodi untuk program studi manapun.', 403);
        }

        $activeTA = \App\Models\TahunAkademik::active()->first();

        $query = Kelas::where('kode_program_studi', $programStudi->kode_program_studi)
            ->with([
                'namaKelas:nama_kelas_id,nama_kelas',
                'matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah',
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            ]);

        if ($activeTA) {
            $query->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik);
        }

        $kelasList = $query->orderBy('kelas_id', 'desc')->get();

        $data = $kelasList->map(function ($kelas) use ($programStudi) {
            $totalMhs = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)->count();

            $proses = PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                ->where('status', 'proses')
                ->count();

            $validasi = PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                ->where('status', 'validasi')
                ->count();

            return [
                'code_kelas' => $kelas->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas,
                'semester' => $kelas->semester,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik,
                'nama_program_studi' => $programStudi->nama_program_studi,
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
                'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah,
                'jumlah_mahasiswa' => $totalMhs,
                'proses' => $proses,
                'validasi' => $validasi,
            ];
        })->values()->toArray();

        return ApiResponse::success($data, 'Kelas penilaian kaprodi retrieved successfully.');
    }

    public function getMahasiswaPenilaian(string $codeKelas, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $programStudi = ProgramStudi::where('kode_dosen_kaprodi', $kodeDosen)
            ->where('kode_program_studi', $kelas->kode_program_studi)
            ->first();

        if (! $programStudi) {
            return ApiResponse::error('Anda bukan Kaprodi untuk program studi kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

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

        // Eager load validation, penilaian status, and student scores to avoid N+1
        $nimList = $kelasMahasiswa->map(function ($km) {
            return $km->krsDetail?->krs?->mahasiswa?->nim;
        })->filter()->unique()->values()->toArray();

        $validations = ValidationStudentScore::where('template_id', $template->id)
            ->whereIn('nim', $nimList)
            ->get()
            ->keyBy('nim');

        $penilaianStatuses = PenilaianStatus::where('kelas_id', $kelas->kelas_id)
            ->whereIn('nim', $nimList)
            ->get()
            ->keyBy('nim');

        $scoreCounts = StudentScore::where('template_id', $template->id)
            ->whereIn('nim', $nimList)
            ->whereNotNull('score')
            ->groupBy('nim')
            ->selectRaw('nim, COUNT(*) as count')
            ->get()
            ->pluck('count', 'nim');

        $leafNodes = $template ? $this->treeService->getLeafNodes($template) : [];
        $totalNodes = count($leafNodes);

        $data = $kelasMahasiswa->map(function ($km) use ($template, $kelas, $validations, $penilaianStatuses, $scoreCounts, $totalNodes) {
            $krsDetail = $km->krsDetail;
            $khs = $krsDetail?->khsDetail;
            $krs = $krsDetail?->krs;
            $mahasiswa = $krs?->mahasiswa;

            if (! $mahasiswa) {
                return null;
            }

            $validation = $validations->get($mahasiswa->nim);
            $penilaian = $penilaianStatuses->get($mahasiswa->nim);

            $status = 'belum_input';
            if ($validation) {
                $status = $validation->status;
            }

            $sudahDiisi = $scoreCounts->get($mahasiswa->nim, 0);

            return [
                'code_mahasiswa' => $mahasiswa->toCode(),
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'status_penilaian' => $status,
                'nilai_akhir' => $khs?->nilai_akhir,
                'catatan_dosen' => $penilaian?->catatan_dosen,
                'catatan_kaprodi' => $validation?->catatan_validasi,
                'validated_by' => $validation?->validator?->nama_dosen,
                'validated_at' => $validation?->validated_at?->toIso8601String(),
                'sudah_diisi' => $sudahDiisi,
                'total_nodes' => $totalNodes,
            ];
        })->filter()->values()->toArray();

        return ApiResponse::success($data, 'Daftar mahasiswa penilaian kaprodi retrieved successfully.');
    }

    public function validasiPenilaian(string $codeKelas, string $codeMhs, ?string $catatan, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $mahasiswa = Mahasiswa::findByCode($codeMhs);

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $programStudi = ProgramStudi::where('kode_dosen_kaprodi', $kodeDosen)
            ->where('kode_program_studi', $kelas->kode_program_studi)
            ->first();

        if (! $programStudi) {
            return ApiResponse::error('Anda bukan Kaprodi untuk program studi kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $validation = ValidationStudentScore::where('template_id', $template->id)
            ->where('nim', $mahasiswa->nim)
            ->first();

        if (! $validation) {
            return ApiResponse::notFound('Data penilaian untuk mahasiswa ini tidak ditemukan.');
        }

        if ($validation->status !== 'proses') {
            return ApiResponse::error('Penilaian tidak dapat divalidasi (sudah divalidasi atau sedang direvisi).', 422);
        }

        DB::beginTransaction();

        try {
            $validation->update([
                'status' => 'validasi',
                'validated_by' => $kodeDosen,
                'validated_at' => now(),
                'catatan_validasi' => $catatan,
            ]);

            PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                ->where('nim', $mahasiswa->nim)
                ->update([
                    'status' => 'validasi',
                    'kaprodi_validated_by' => $kodeDosen,
                    'validated_at' => now(),
                    'catatan_kaprodi' => $catatan,
                ]);

            $nilaiAkhir = $this->scoreCalculationService->calculateFinalScore($template, $mahasiswa->nim);

            // Konversi nilai_akhir → grade + score
            $serviceGrade = new ServiceGrade();
            $gradeData = $serviceGrade->konversi($nilaiAkhir, $kelas->kode_program_studi);

            $km = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
                ->whereHas('krsDetail.krs', function ($q) use ($mahasiswa) {
                    $q->where('nim', $mahasiswa->nim);
                })
                ->first();

            $krsDetail = $km?->krsDetail;

            if ($krsDetail) {
                $khsDetail = KhsDetail::where('kode_krs_detail', $krsDetail->kode_krs_detail)->first();

                if ($khsDetail) {
                    $khsDetail->update([
                        'nilai_akhir' => $nilaiAkhir,
                        'grade' => $gradeData['grade'] ?? null,
                        'score' => $gradeData['score'] ?? null,
                        'tidak_berhak' => 'A',
                    ]);
                } else {
                    KhsDetail::create([
                        'kode_krs_detail' => $krsDetail->kode_krs_detail,
                        'nilai_akhir' => $nilaiAkhir,
                        'grade' => $gradeData['grade'] ?? null,
                        'score' => $gradeData['score'] ?? null,
                        'tidak_berhak' => 'A',
                    ]);
                }
            }

            DB::commit();

            return ApiResponse::success([
                'code_mahasiswa' => $mahasiswa->toCode(),
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'status' => 'validasi',
                'nilai_akhir' => $nilaiAkhir,
                'validated_at' => now()->toIso8601String(),
            ], 'Penilaian berhasil divalidasi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Terjadi kesalahan saat memproses penilaian.');
        }
    }

    public function getDetailNilaiMahasiswa(string $codeKelas, string $codeMahasiswa, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $programStudi = ProgramStudi::where('kode_dosen_kaprodi', $kodeDosen)
            ->where('kode_program_studi', $kelas->kode_program_studi)
            ->first();

        if (! $programStudi) {
            return ApiResponse::error('Anda bukan Kaprodi untuk program studi kelas ini.', 403);
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
            'structure' => $this->treeBuilderService->buildTreeWithScores($template, $scores),
        ];

        return ApiResponse::success($data, 'Detail nilai mahasiswa retrieved successfully.');
    }

    public function revisiPenilaian(string $codeKelas, string $codeMhs, string $catatan, int $kodeDosen): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);

        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan');
        }

        $mahasiswa = Mahasiswa::findByCode($codeMhs);

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $programStudi = ProgramStudi::where('kode_dosen_kaprodi', $kodeDosen)
            ->where('kode_program_studi', $kelas->kode_program_studi)
            ->first();

        if (! $programStudi) {
            return ApiResponse::error('Anda bukan Kaprodi untuk program studi kelas ini.', 403);
        }

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template penilaian untuk matakuliah ini belum tersedia.');
        }

        $validation = ValidationStudentScore::where('template_id', $template->id)
            ->where('nim', $mahasiswa->nim)
            ->first();

        if (! $validation) {
            return ApiResponse::notFound('Data penilaian untuk mahasiswa ini tidak ditemukan.');
        }

        if ($validation->status !== 'validasi') {
            return ApiResponse::error('Penilaian tidak dapat direvisi (belum divalidasi).', 422);
        }

        DB::beginTransaction();

        try {
            $validation->update([
                'status' => 'revisi',
                'catatan_validasi' => $catatan,
            ]);

            PenilaianStatus::where('kelas_id', $kelas->kelas_id)
                ->where('nim', $mahasiswa->nim)
                ->update([
                    'status' => 'revisi',
                    'catatan_kaprodi' => $catatan,
                ]);

            $km = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
                ->whereHas('krsDetail.krs', function ($q) use ($mahasiswa) {
                    $q->where('nim', $mahasiswa->nim);
                })
                ->first();

            $krsDetail = $km?->krsDetail;

            if ($krsDetail) {
                $khsDetail = KhsDetail::where('kode_krs_detail', $krsDetail->kode_krs_detail)->first();

                if ($khsDetail) {
                    $khsDetail->update([
                        'nilai_akhir' => null,
                        'grade' => null,
                        'score' => null,
                        'tidak_berhak' => 'N',
                    ]);
                }
            }

            DB::commit();

            return ApiResponse::success([
                'code_mahasiswa' => $mahasiswa->toCode(),
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'status' => 'revisi',
                'catatan_kaprodi' => $catatan,
            ], 'Penilaian berhasil direvisi dan dikembalikan ke dosen untuk diperbaiki.');
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Terjadi kesalahan saat memproses penilaian.');
        }
    }
}

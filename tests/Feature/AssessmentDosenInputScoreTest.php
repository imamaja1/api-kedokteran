<?php

namespace Tests\Feature;

use App\Models\AssessmentDosen;
use App\Models\AssessmentDosenAssignment;
use App\Models\AssessmentTemplate;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Mengajar;
use App\Models\NamaKelas;
use App\Models\PenilaianStatus;
use App\Models\StudentScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;
use Tests\TestCase;

class AssessmentDosenInputScoreTest extends TestCase
{
    use RefreshDatabase;

    private Dosen $dosen;
    private Kelas $kelas;
    private AssessmentDosen $assessment;
    private Mahasiswa $mhs1;
    private Mahasiswa $mhs2;

    protected function setUp(): void
    {
        parent::setUp();

        $ta = TestDataHelper::createTahunAkademik();
        $prodi = TestDataHelper::createProgramStudi();
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);

        $mk = Matakuliah::create([
            'kode_matakuliah'    => 'BLOK-TEST',
            'nama_matakuliah'    => 'Blok Kardiovaskular',
            'jenis'              => 1,
            'sks_teori'          => 4,
            'sks_praktik'        => 0,
            'block'              => 1,
            'kode_program_studi' => $prodi->kode_program_studi,
        ]);

        $this->kelas = Kelas::create([
            'nama_kelas_id'       => $namaKelas->nama_kelas_id,
            'semester'            => '1',
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'kode_program_studi'  => $prodi->kode_program_studi,
            'id_matakuliah'       => $mk->id_matakuliah,
        ]);

        $this->dosen = TestDataHelper::createDosen();

        Mengajar::create([
            'kelas_id'   => $this->kelas->kelas_id,
            'kode_dosen' => $this->dosen->kode_dosen,
        ]);

        $nk = \App\Models\NamaKurikulum::create([
            'nama_kurikulum'     => 'Kurikulum 2024',
            'kode_program_studi' => $prodi->kode_program_studi,
        ]);
        $ka = \App\Models\KurikulumAngkatan::create([
            'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
            'tahun'               => '2024',
        ]);

        $template = AssessmentTemplate::create([
            'id_matakuliah'           => $mk->id_matakuliah,
            'kode_nama_kurikulum'     => $nk->kode_nama_kurikulum,
            'kode_tahun_akademik'     => $ta->kode_tahun_akademik,
            'kode_kurikulum_angkatan' => $ka->kode_kurikulum_angkatan,
            'versi'                   => 1,
            'status'                  => 'aktif',
            'is_active'               => true,
            'structure'               => [
                'key'      => 'root_blok',
                'name'     => 'Nilai Blok',
                'weight'   => 100,
                'children' => [
                    [
                        'key'      => 'tugas',
                        'name'     => 'Tugas',
                        'type'     => 'input',
                        'weight'   => 20,
                    ],
                    [
                        'key'      => 'uts',
                        'name'     => 'UTS',
                        'type'     => 'input',
                        'weight'   => 40,
                    ],
                    [
                        'key'      => 'uas',
                        'name'     => 'UAS',
                        'type'     => 'input',
                        'weight'   => 40,
                    ],
                ],
            ],
        ]);

        $this->assessment = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id'    => $this->kelas->kelas_id,
            'status'      => 'aktif',
        ]);

        $this->mhs1 = TestDataHelper::createMahasiswa(['nim' => 'MHS001', 'nama_mahasiswa' => 'Mahasiswa Satu']);
        $this->mhs2 = TestDataHelper::createMahasiswa(['nim' => 'MHS002', 'nama_mahasiswa' => 'Mahasiswa Dua']);

        foreach ([$this->mhs1, $this->mhs2] as $idx => $mhs) {
            $krs = Krs::create([
                'nim'                 => $mhs->nim,
                'kode_tahun_akademik' => $ta->kode_tahun_akademik,
                'semester'            => 1,
            ]);

            $krsDetail = KrsDetail::create([
                'kode_krs_detail' => 100 + $idx,
                'kode_krs'        => $krs->kode_krs,
                'id_matakuliah'   => $mk->id_matakuliah,
            ]);

            KelasMahasiswa::create([
                'kelas_id'        => $this->kelas->kelas_id,
                'kode_krs_detail' => $krsDetail->kode_krs_detail,
            ]);
        }
    }

    public function test_input_score_multi_components_per_mahasiswa_single_penilai(): void
    {
        $payload = [
            'assessment_dosen_id' => $this->assessment->id,
            'mahasiswa'           => [
                [
                    'code_mahasiswa' => $this->mhs1->toCode(),
                    'scores'         => [
                        ['node_key' => 'tugas', 'score' => 85],
                        ['node_key' => 'uts', 'score' => 80],
                        ['node_key' => 'uas', 'score' => 88],
                    ],
                    'catatan'        => 'Catatan evaluasi umum',
                ],
                [
                    'code_mahasiswa' => $this->mhs2->toCode(),
                    'scores'         => [
                        ['node_key' => 'tugas', 'score' => 90],
                        ['node_key' => 'uts', 'score' => 85],
                        ['node_key' => 'uas', 'score' => 92],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.total_berhasil', 2);

        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'tugas',
            'score'    => 85,
        ]);
        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'uts',
            'score'    => 80,
        ]);
        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'uas',
            'score'    => 88,
        ]);

        $this->assertDatabaseHas('penilaian_status', [
            'kelas_id'      => $this->kelas->kelas_id,
            'nim'           => 'MHS001',
            'status'        => 'proses',
            'catatan_dosen' => 'Catatan evaluasi umum',
        ]);
    }

    public function test_input_score_self_contained_item_format(): void
    {
        $payload = [
            'mahasiswa' => [
                [
                    'assessment_dosen_id' => $this->assessment->id,
                    'node_key'            => 'uts',
                    'code_mahasiswa'      => $this->mhs1->toCode(),
                    'score'               => '85',
                    'notes'               => 'Catatan evaluasi',
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.total_berhasil', 1);

        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'uts',
            'score'    => 85,
        ]);
    }

    public function test_input_score_map_dictionary_format(): void
    {
        $payload = [
            'assessment_dosen_id' => $this->assessment->id,
            'mahasiswa'           => [
                [
                    'code_mahasiswa' => $this->mhs1->toCode(),
                    'scores'         => [
                        'tugas' => 88,
                        'uts'   => 92,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'tugas',
            'score'    => 88,
        ]);
        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'uts',
            'score'    => 92,
        ]);
    }

    public function test_input_score_using_code_kelas_fallback(): void
    {
        $payload = [
            'code_kelas' => $this->kelas->toCode(),
            'mahasiswa'  => [
                [
                    'code_mahasiswa' => $this->mhs1->toCode(),
                    'scores'         => [
                        ['node_key' => 'uas', 'score' => 95],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $this->assertDatabaseHas('student_scores', [
            'nim'      => 'MHS001',
            'node_key' => 'uas',
            'score'    => 95,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AssessmentDosen;
use App\Models\AssessmentTemplate;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\Mengajar;
use App\Models\NamaKelas;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;
use Tests\TestCase;

class StaffAssessmentDosenTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Kelas $kelas;
    private AssessmentTemplate $template;
    private Dosen $dosen;
    private AssessmentDosen $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = TestDataHelper::createStaff();

        $ta = TestDataHelper::createTahunAkademik();
        $prodi = TestDataHelper::createProgramStudi();

        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);

        $mk = Matakuliah::create([
            'kode_matakuliah'    => 'BLOK-01',
            'nama_matakuliah'    => 'Kardiovaskular',
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

        $this->dosen = TestDataHelper::createDosen([
            'nama_dosen' => 'dr. Penguji Spesialis, Sp.JP',
        ]);

        Mengajar::create([
            'kode_dosen' => $this->dosen->kode_dosen,
            'kelas_id'   => $this->kelas->kelas_id,
        ]);

        $nk = \App\Models\NamaKurikulum::create([
            'nama_kurikulum'     => 'Kurikulum 2024',
            'kode_program_studi' => $prodi->kode_program_studi,
        ]);
        $ka = \App\Models\KurikulumAngkatan::create([
            'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
            'tahun'               => '2024',
        ]);

        $this->template = AssessmentTemplate::create([
            'id_matakuliah'           => $mk->id_matakuliah,
            'kode_nama_kurikulum'     => $nk->kode_nama_kurikulum,
            'kode_tahun_akademik'     => $ta->kode_tahun_akademik,
            'kode_kurikulum_angkatan' => $ka->kode_kurikulum_angkatan,
            'versi'                   => 1,
            'is_active'               => true,
            'structure'               => [
                'key'      => 'blok_kardio',
                'name'     => 'Penilaian Kardiovaskular',
                'weight'   => 100,
                'children' => [
                    [
                        'key'      => 'cbt',
                        'name'     => 'Ujian CBT',
                        'type'     => 'category',
                        'weight'   => 40,
                        'children' => [
                            [
                                'key'    => 'cbt_mcq',
                                'name'   => 'MCQ Exam',
                                'type'   => 'input',
                                'weight' => 100,
                            ],
                        ],
                    ],
                    [
                        'key'      => 'osce',
                        'name'     => 'Ujian OSCE',
                        'type'     => 'category',
                        'weight'   => 60,
                        'children' => [
                            [
                                'key'    => 'osce_stase_1',
                                'name'   => 'Stase 1 - Anamnesis',
                                'type'   => 'input',
                                'weight' => 50,
                            ],
                            [
                                'key'    => 'osce_stase_2',
                                'name'   => 'Stase 2 - Resusitasi',
                                'type'   => 'input',
                                'weight' => 50,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assessment = AssessmentDosen::create([
            'template_id' => $this->template->id,
            'kelas_id'    => $this->kelas->kelas_id,
            'status'      => 'aktif',
        ]);
    }

    public function test_get_assignments_returns_rich_view_with_available_nodes_and_dosen_pengajar(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments?code_kelas=' . $this->kelas->toCode());

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.assessment_dosen_id', $this->assessment->id)
            ->assertJsonPath('data.kelas.matakuliah', 'Kardiovaskular')
            ->assertJsonPath('data.template.root_name', 'Penilaian Kardiovaskular')
            ->assertJsonCount(1, 'data.dosen_pengajar')
            ->assertJsonPath('data.dosen_pengajar.0.kode_dosen', $this->dosen->kode_dosen)
            ->assertJsonPath('data.dosen_pengajar.0.nama_dosen', 'dr. Penguji Spesialis, Sp.JP')
            ->assertJsonCount(3, 'data.available_nodes') // Root + 2 categories
            ->assertJsonPath('data.available_nodes.1.node_key', 'cbt')
            ->assertJsonPath('data.available_nodes.1.is_assigned', false)
            ->assertJsonPath('data.available_nodes.1.sub_components.0.key', 'cbt_mcq')
            ->assertJsonPath('data.payload_sample_put.assessment_dosen_id', $this->assessment->id);
    }

    public function test_assign_dosen_and_verify_reflection_in_assignments_view(): void
    {
        // 1. Assign Dosen to Category 'cbt'
        $assignResponse = $this->actingAs($this->staff, 'staff_web')
            ->putJson('/api/staff/assessment/dosen/assign', [
                'assessment_dosen_id' => $this->assessment->id,
                'assignments'         => [
                    [
                        'node_key'   => 'cbt',
                        'kode_dosen' => $this->dosen->kode_dosen,
                    ],
                ],
            ]);

        $assignResponse->assertStatus(200)
            ->assertJsonPath('status', true);

        // 2. Query GET /assignments to check that cbt is now marked as assigned
        $getResponse = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments?code_kelas=' . $this->kelas->toCode());

        $getResponse->assertStatus(200)
            ->assertJsonPath('data.available_nodes.1.node_key', 'cbt')
            ->assertJsonPath('data.available_nodes.1.is_assigned', true)
            ->assertJsonPath('data.available_nodes.1.assigned_dosen.kode_dosen', $this->dosen->kode_dosen)
            ->assertJsonPath('data.available_nodes.1.assigned_dosen.nama_dosen', 'dr. Penguji Spesialis, Sp.JP')
            ->assertJsonCount(1, 'data.assignments')
            ->assertJsonPath('data.assignments.0.node_key', 'cbt');
    }

    public function test_duplicate_assessment_dosen_returns_422_with_existing_id(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->postJson('/api/staff/assessment/dosen', [
                'code_kelas' => $this->kelas->toCode(),
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('errors.assessment_dosen_id', $this->assessment->id);
    }

    public function test_assign_dosen_deduplicates_payload_for_same_node_key(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->putJson('/api/staff/assessment/dosen/assign', [
                'assessment_dosen_id' => $this->assessment->id,
                'assignments'         => [
                    ['node_key' => 'osce', 'kode_dosen' => $this->dosen->kode_dosen],
                    ['node_key' => 'osce', 'kode_dosen' => $this->dosen->kode_dosen],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_dosen_pengajar_is_unique_even_if_recorded_multiple_times_in_mengajar(): void
    {
        // Add a duplicate mengajar row for the same dosen in the same class
        Mengajar::create([
            'kode_dosen' => $this->dosen->kode_dosen,
            'kelas_id'   => $this->kelas->kelas_id,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments?code_kelas=' . $this->kelas->toCode());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.dosen_pengajar');
    }

    public function test_cannot_assign_soft_deleted_dosen(): void
    {
        $deletedDosen = TestDataHelper::createDosen(['nama_dosen' => 'dr. Nonaktif']);
        Mengajar::create([
            'kode_dosen' => $deletedDosen->kode_dosen,
            'kelas_id'   => $this->kelas->kelas_id,
        ]);
        $deletedDosen->delete(); // Soft delete

        $response = $this->actingAs($this->staff, 'staff_web')
            ->putJson('/api/staff/assessment/dosen/assign', [
                'assessment_dosen_id' => $this->assessment->id,
                'assignments'         => [
                    ['node_key' => 'osce', 'kode_dosen' => $deletedDosen->kode_dosen],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_get_assignments_accepts_camel_case_code_kelas(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments?codeKelas=' . $this->kelas->toCode());

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.assessment_dosen_id', $this->assessment->id);
    }

    public function test_get_assignments_accepts_shorthand_code(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments?code=' . $this->kelas->toCode());

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.assessment_dosen_id', $this->assessment->id);
    }

    public function test_get_assignments_accepts_fallback_from_server_request_uri(): void
    {
        // Simulate Nginx dropping query_string from PHP $_GET but retaining original REQUEST_URI
        $token = $this->kelas->toCode();
        $_SERVER['REQUEST_URI'] = '/api/staff/assessment/dosen/assignments?code_kelas=' . $token;

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/assessment/dosen/assignments');

        unset($_SERVER['REQUEST_URI']);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.assessment_dosen_id', $this->assessment->id);
    }

    public function test_get_assignments_returns_422_when_code_kelas_is_truly_missing(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->withServerVariables([
                'REQUEST_URI' => '/api/staff/assessment/dosen/assignments',
                'QUERY_STRING' => '',
            ])
            ->getJson('/api/staff/assessment/dosen/assignments');

        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonValidationErrors(['code_kelas']);
    }

    public function test_assign_dosen_accepts_camel_case_and_update_route_alias(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->putJson('/api/staff/assessment/dosen/update', [
                'assessmentDosenId' => $this->assessment->id,
                'assignments'       => [
                    [
                        'nodeKey'   => 'cbt',
                        'kodeDosen' => $this->dosen->kode_dosen,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true);
    }

    public function test_assign_dosen_resolves_id_from_code_kelas(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->putJson('/api/staff/assessment/dosen/assign', [
                'code_kelas'  => $this->kelas->toCode(),
                'assignments' => [
                    [
                        'node_key'   => 'cbt',
                        'kode_dosen' => $this->dosen->kode_dosen,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true);
    }
}

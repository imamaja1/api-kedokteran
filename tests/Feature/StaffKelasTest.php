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
use App\Models\KurikulumAngkatan;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Mengajar;
use App\Models\NamaKelas;
use App\Models\NamaKurikulum;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;
use Tests\TestCase;

class StaffKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private ProgramStudi $prodi;
    private TahunAkademik $ta;
    private Matakuliah $mk;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = TestDataHelper::createStaff();
        $this->prodi = TestDataHelper::createProgramStudi();
        $this->ta = TestDataHelper::createTahunAkademik(['status' => 'A']);
        $this->dosen = TestDataHelper::createDosen();

        $this->mk = Matakuliah::create([
            'kode_matakuliah' => 'MK101',
            'nama_matakuliah' => 'Biomedik',
            'jenis' => 1,
            'sks_teori' => 2,
            'sks_praktik' => 1,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'block' => 1,
            'tipe' => 'W',
        ]);
    }

    public function test_get_kelas_index_unauthenticated(): void
    {
        $response = $this->getJson('/api/staff/kelas');
        $response->assertStatus(401);
    }

    public function test_get_kelas_index_success(): void
    {
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/kelas');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Daftar kelas berhasil diambil.')
            ->assertJsonCount(1, 'data');
    }

    public function test_store_kelas_success(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->postJson('/api/staff/kelas', [
                'code_matakuliah' => $this->mk->toCode(),
                'nama_kelas' => 'A',
                'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
                'semester' => '1',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.nama_kelas', 'A')
            ->assertJsonPath('data.kode_matakuliah', 'MK101');
    }

    public function test_store_kelas_duplicate_returns_422(): void
    {
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->postJson('/api/staff/kelas', [
                'code_matakuliah' => $this->mk->toCode(),
                'nama_kelas' => 'A',
                'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_show_kelas_success(): void
    {
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/kelas/show?kelas_id=' . $kelas->kelas_id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.kelas.kelas_id', $kelas->kelas_id);
    }

    public function test_store_and_destroy_dosen_kelas(): void
    {
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        // Add Dosen
        $response = $this->actingAs($this->staff, 'staff_web')
            ->postJson('/api/staff/kelas/dosen', [
                'kelas_id' => $kelas->kelas_id,
                'code_dosen' => $this->dosen->toCode(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.kelas_id', $kelas->kelas_id);

        $mengajarId = $response->json('data.mengajar_id');

        // Delete Dosen
        $delResponse = $this->actingAs($this->staff, 'staff_web')
            ->deleteJson('/api/staff/kelas/dosen', [
                'mengajar_id' => $mengajarId,
            ]);

        $delResponse->assertStatus(200)
            ->assertJsonPath('status', true);
    }

    public function test_destroy_kelas_success(): void
    {
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->deleteJson('/api/staff/kelas', [
                'kelas_id' => $kelas->kelas_id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true);

        $this->assertDatabaseMissing('kelas', ['kelas_id' => $kelas->kelas_id]);
    }

    private function createAssessmentTemplate(): AssessmentTemplate
    {
        $nk = NamaKurikulum::create([
            'nama_kurikulum' => 'Kurikulum 2024',
            'kode_program_studi' => $this->prodi->kode_program_studi,
        ]);
        $ka = KurikulumAngkatan::create([
            'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
            'tahun' => '2024',
        ]);

        return AssessmentTemplate::create([
            'id_matakuliah' => $this->mk->id_matakuliah,
            'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_kurikulum_angkatan' => $ka->kode_kurikulum_angkatan,
            'versi' => 1,
            'is_active' => true,
            'structure' => [
                'key' => 'root',
                'name' => 'Penilaian',
                'children' => [
                    [
                        'key' => 'tugas_1',
                        'name' => 'Tugas 1',
                        'type' => 'input',
                        'weight' => 100,
                    ],
                ],
            ],
        ]);
    }

    public function test_get_kelas_index_template_and_setup_assignments_status(): void
    {
        $template = $this->createAssessmentTemplate();

        $namaA = NamaKelas::create(['nama_kelas' => 'A']);
        $namaB = NamaKelas::create(['nama_kelas' => 'B']);
        $namaC = NamaKelas::create(['nama_kelas' => 'C']);

        // Kelas 1: No template
        $kelas1 = Kelas::create([
            'nama_kelas_id' => $namaA->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        // Kelas 2: Has template, no assignment setup
        $kelas2 = Kelas::create([
            'nama_kelas_id' => $namaB->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);
        AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id' => $kelas2->kelas_id,
            'status' => 'aktif',
        ]);

        // Kelas 3: Has template AND assignment setup
        $kelas3 = Kelas::create([
            'nama_kelas_id' => $namaC->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);
        $assessment3 = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id' => $kelas3->kelas_id,
            'status' => 'aktif',
        ]);
        AssessmentDosenAssignment::create([
            'assessment_dosen_id' => $assessment3->id,
            'node_key' => 'tugas_1',
            'kode_dosen' => $this->dosen->kode_dosen,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/kelas');

        $response->assertStatus(200);

        $items = collect($response->json('data'))->keyBy('kelas_id');

        $this->assertFalse($items[$kelas1->kelas_id]['template_assignments']);
        $this->assertFalse($items[$kelas1->kelas_id]['setup_assignments']);

        $this->assertTrue($items[$kelas2->kelas_id]['template_assignments']);
        $this->assertFalse($items[$kelas2->kelas_id]['setup_assignments']);

        $this->assertTrue($items[$kelas3->kelas_id]['template_assignments']);
        $this->assertTrue($items[$kelas3->kelas_id]['setup_assignments']);
    }

    public function test_show_kelas_includes_template_and_setup_assignments(): void
    {
        $template = $this->createAssessmentTemplate();
        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $assessment = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id' => $kelas->kelas_id,
            'status' => 'aktif',
        ]);
        AssessmentDosenAssignment::create([
            'assessment_dosen_id' => $assessment->id,
            'node_key' => 'tugas_1',
            'kode_dosen' => $this->dosen->kode_dosen,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/kelas/show?kelas_id=' . $kelas->kelas_id);

        $response->assertStatus(200)
            ->assertJsonPath('data.kelas.template_assignments', true)
            ->assertJsonPath('data.kelas.setup_assignments', true);
    }

    public function test_matakuliah_konfigurasi_includes_template_and_setup_assignments(): void
    {
        $template = $this->createAssessmentTemplate();
        $mhs = TestDataHelper::createMahasiswa(['program_studi_kode' => $this->prodi->kode_program_studi]);

        $krs = Krs::create([
            'nim' => $mhs->nim,
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'semester' => '1',
        ]);
        KrsDetail::create([
            'kode_krs' => $krs->kode_krs,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
        $kelas = Kelas::create([
            'nama_kelas_id' => $namaKelas->nama_kelas_id,
            'semester' => '1',
            'kode_tahun_akademik' => $this->ta->kode_tahun_akademik,
            'kode_program_studi' => $this->prodi->kode_program_studi,
            'id_matakuliah' => $this->mk->id_matakuliah,
        ]);

        $assessment = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id' => $kelas->kelas_id,
            'status' => 'aktif',
        ]);
        AssessmentDosenAssignment::create([
            'assessment_dosen_id' => $assessment->id,
            'node_key' => 'tugas_1',
            'kode_dosen' => $this->dosen->kode_dosen,
        ]);

        $response = $this->actingAs($this->staff, 'staff_web')
            ->getJson('/api/staff/kelas/matakuliah-konfigurasi?kode_tahun_akademik=' . $this->ta->kode_tahun_akademik);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.kelas.0.template_assignments', true)
            ->assertJsonPath('data.0.kelas.0.setup_assignments', true);
    }
}

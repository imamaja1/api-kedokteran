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

class KaprodiAssessmentValidationTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodi;
    private Dosen $dosenKaprodi;
    private Dosen $dosenBiasa;
    private User $staff;
    private Kelas $kelas;
    private AssessmentDosen $assessment;
    private Mahasiswa $mhs;
    private KrsDetail $krsDetail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = TestDataHelper::createStaff();
        $ta = TestDataHelper::createTahunAkademik();

        $this->dosenKaprodi = TestDataHelper::createDosen(['nama_dosen' => 'dr. Kaprodi Sp.A']);
        $this->dosenBiasa = TestDataHelper::createDosen(['nama_dosen' => 'dr. Pengajar Biasa']);

        $this->prodi = TestDataHelper::createProgramStudi([
            'kode_dosen_kaprodi' => $this->dosenKaprodi->kode_dosen,
        ]);

        $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);

        $mk = Matakuliah::create([
            'kode_matakuliah'    => 'BLOK-TEST',
            'nama_matakuliah'    => 'Blok Kardiovaskular',
            'jenis'              => 1,
            'sks_teori'          => 4,
            'sks_praktik'        => 0,
            'block'              => 1,
            'kode_program_studi' => $this->prodi->kode_program_studi,
        ]);

        $this->kelas = Kelas::create([
            'nama_kelas_id'       => $namaKelas->nama_kelas_id,
            'semester'            => '1',
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'kode_program_studi'  => $this->prodi->kode_program_studi,
            'id_matakuliah'       => $mk->id_matakuliah,
        ]);

        Mengajar::create([
            'kelas_id'   => $this->kelas->kelas_id,
            'kode_dosen' => $this->dosenBiasa->kode_dosen,
        ]);

        $nk = NamaKurikulum::create([
            'nama_kurikulum'     => 'Kurikulum 2024',
            'kode_program_studi' => $this->prodi->kode_program_studi,
        ]);
        $ka = KurikulumAngkatan::create([
            'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
            'tahun'               => '2024',
        ]);

        $template = AssessmentTemplate::create([
            'id_matakuliah'           => $mk->id_matakuliah,
            'kode_nama_kurikulum'     => $nk->kode_nama_kurikulum,
            'kode_tahun_akademik'     => $ta->kode_tahun_akademik,
            'kode_kurikulum_angkatan' => $ka->kode_kurikulum_angkatan,
            'nama_template'           => 'Template Ujian Blok',
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
                        'weight'   => 40,
                    ],
                    [
                        'key'      => 'uts',
                        'name'     => 'UTS',
                        'type'     => 'input',
                        'weight'   => 60,
                    ],
                ],
            ],
        ]);

        $this->assessment = AssessmentDosen::create([
            'template_id' => $template->id,
            'kelas_id'    => $this->kelas->kelas_id,
            'status'      => 'aktif',
        ]);

        $this->mhs = TestDataHelper::createMahasiswa(['nim' => 'MHS001', 'nama_mahasiswa' => 'Mahasiswa Satu']);

        $krs = Krs::create([
            'nim'                 => $this->mhs->nim,
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'semester'            => 1,
        ]);

        $this->krsDetail = KrsDetail::create([
            'kode_krs_detail' => 101,
            'kode_krs'        => $krs->kode_krs,
            'id_matakuliah'   => $mk->id_matakuliah,
        ]);

        KelasMahasiswa::create([
            'kelas_id'        => $this->kelas->kelas_id,
            'kode_krs_detail' => $this->krsDetail->kode_krs_detail,
        ]);
    }

    public function test_non_kaprodi_cannot_validate_root_node(): void
    {
        // Even if non-kaprodi is assigned to root node
        AssessmentDosenAssignment::create([
            'assessment_dosen_id' => $this->assessment->id,
            'node_key'            => 'root_blok',
            'kode_dosen'          => $this->dosenBiasa->kode_dosen,
        ]);

        $response = $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Dosen biasa mencoba validasi root',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Hanya Ketua Program Studi (Kaprodi) yang berwenang mengesahkan nilai akhir ke KHS.');
    }

    public function test_non_kaprodi_cannot_bulk_validate_root_node(): void
    {
        $response = $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi-bulk', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'catatan'             => 'Dosen biasa mencoba validasi massal root',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Hanya Ketua Program Studi (Kaprodi) yang berwenang mengesahkan nilai akhir ke KHS.');
    }

    public function test_kaprodi_can_validate_and_finalize_grade_to_khs(): void
    {
        // Input nilai terlebih dahulu oleh dosen
        $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', [
                'assessment_dosen_id' => $this->assessment->id,
                'mahasiswa'           => [
                    [
                        'code_mahasiswa' => $this->mhs->toCode(),
                        'scores'         => [
                            ['node_key' => 'tugas', 'score' => 90],
                            ['node_key' => 'uts', 'score' => 80],
                        ],
                    ],
                ],
            ])->assertStatus(200);

        // Kaprodi memvalidasi via endpoint resmi kaprodi
        $response = $this->actingAs($this->dosenKaprodi, 'dosen_web')
            ->postJson('/api/dosen/kaprodi/assessment-dosen/validasi', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Disetujui Kaprodi',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'validasi');

        // Pastikan nilai masuk ke khs_detail
        $this->assertDatabaseHas('khs_detail', [
            'kode_krs_detail' => $this->krsDetail->kode_krs_detail,
            'nilai_akhir'     => 84, // (90*0.4) + (80*0.6) = 36 + 48 = 84
            'tidak_berhak'    => 'A',
        ]);
    }

    public function test_staff_direct_input_khs_bypasses_validation(): void
    {
        $response = $this->actingAs($this->staff, 'staff_web')
            ->postJson('/api/staff/akademik/khs/direct-input', [
                'code_krs_detail' => $this->krsDetail->toCode(),
                'nilai_akhir'     => 95,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        // Nilai langsung terbit di khs_detail tanpa melalui approval kaprodi
        $this->assertDatabaseHas('khs_detail', [
            'kode_krs_detail' => $this->krsDetail->kode_krs_detail,
            'nilai_akhir'     => 95,
            'tidak_berhak'    => 'A',
        ]);
    }
}

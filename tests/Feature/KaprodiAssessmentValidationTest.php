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

    public function test_kaprodi_assessment_dosen_routes_are_removed(): void
    {
        $postRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes()->get('POST'))
            ->map(fn ($route) => $route->uri())
            ->all();

        $this->assertNotContains('api/dosen/kaprodi/assessment-dosen/validasi', $postRoutes);
        $this->assertNotContains('api/dosen/kaprodi/assessment-dosen/validasi-bulk', $postRoutes);
        $this->assertNotContains('api/dosen/kaprodi/assessment-dosen/revisi', $postRoutes);

        foreach ([
            '/api/dosen/kaprodi/assessment-dosen/validasi' => [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
            ],
            '/api/dosen/kaprodi/assessment-dosen/validasi-bulk' => [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
            ],
            '/api/dosen/kaprodi/assessment-dosen/revisi' => [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Revisi',
            ],
        ] as $uri => $payload) {
            $status = $this->actingAs($this->dosenKaprodi, 'dosen_web')
                ->postJson($uri, $payload)
                ->status();

            $this->assertContains($status, [404, 405]);
        }
    }

    public function test_unassigned_dosen_cannot_validate_assessment_node(): void
    {
        $dosenLain = TestDataHelper::createDosen(['nama_dosen' => 'dr. Dosen Luar Kelas']);

        $this->actingAs($dosenLain, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Dosen luar mencoba validasi',
            ])
            ->assertStatus(403);

        $this->actingAs($dosenLain, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi-bulk', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'catatan'             => 'Dosen luar mencoba validasi massal',
            ])
            ->assertStatus(403);
    }

    public function test_dosen_can_validate_assessment_node_without_finalizing_khs_and_kaprodi_finalizes_to_khs(): void
    {
        // 1. Input nilai oleh dosen pengajar
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

        // 2. Dosen mengesahkan node assessment mahasiswa di kelas (cukup sampai kelas, belum masuk KHS)
        $dosenRes = $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Sudah direview dosen kelas',
            ]);

        $dosenRes->assertStatus(200)
            ->assertJsonPath('data.status', 'validasi');

        $this->assertDatabaseHas('assessment_node_validations', [
            'assessment_dosen_id' => $this->assessment->id,
            'node_key'            => 'root_blok',
            'nim'                 => $this->mhs->nim,
            'status'              => 'validasi',
        ]);

        // Nilai BELUM masuk ke khs_detail sebelum Kaprodi mengesahkan lewat endpoint khusus Kaprodi
        $this->assertDatabaseMissing('khs_detail', [
            'kode_krs_detail' => $this->krsDetail->kode_krs_detail,
            'tidak_berhak'    => 'A',
        ]);

        // 3. Kaprodi melakukan pengesahan khusus agar nilai masuk ke KHS
        $kaprodiRes = $this->actingAs($this->dosenKaprodi, 'dosen_web')
            ->postJson('/api/dosen/kaprodi/penilaian/validasi', [
                'code_kelas'     => $this->kelas->toCode(),
                'code_mahasiswa' => $this->mhs->toCode(),
                'catatan'        => 'Disetujui Kaprodi masuk KHS',
            ]);

        $kaprodiRes->assertStatus(200)
            ->assertJsonPath('data.status', 'validasi');

        $this->assertDatabaseHas('khs_detail', [
            'kode_krs_detail' => $this->krsDetail->kode_krs_detail,
            'nilai_akhir'     => 84, // (90*0.4) + (80*0.6) = 36 + 48 = 84
            'tidak_berhak'    => 'A',
        ]);
    }

    public function test_dosen_can_bulk_validate_and_revisi_assessment_node(): void
    {
        $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/input-score', [
                'assessment_dosen_id' => $this->assessment->id,
                'mahasiswa'           => [
                    [
                        'code_mahasiswa' => $this->mhs->toCode(),
                        'scores'         => [
                            ['node_key' => 'tugas', 'score' => 85],
                            ['node_key' => 'uts', 'score' => 75],
                        ],
                    ],
                ],
            ])->assertStatus(200);

        $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/validasi-bulk', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'catatan'             => 'Validasi massal kelas',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.berhasil_validasi', 1);

        $this->actingAs($this->dosenBiasa, 'dosen_web')
            ->postJson('/api/dosen/assessment-dosen/revisi', [
                'assessment_dosen_id' => $this->assessment->id,
                'node_key'            => 'root_blok',
                'code_mahasiswa'      => $this->mhs->toCode(),
                'catatan'             => 'Cek ulang komponen uts',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'revisi');

        $this->assertDatabaseHas('assessment_node_validations', [
            'assessment_dosen_id' => $this->assessment->id,
            'node_key'            => 'root_blok',
            'nim'                 => $this->mhs->nim,
            'status'              => 'revisi',
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

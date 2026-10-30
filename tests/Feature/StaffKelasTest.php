<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Mengajar;
use App\Models\NamaKelas;
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
}

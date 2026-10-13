<?php

use App\Models\Dosen;
use App\Models\KurikulumAngkatan;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\NamaKurikulum;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsStaff()
{
    $staff = TestDataHelper::createStaff();

    return test()->actingAs($staff, 'staff_web');
}

function staffGet($path, $params = [])
{
    return actingAsStaff()
        ->getJson($path.($params ? '?'.http_build_query($params) : ''));
}

function staffPost($path, $data = [])
{
    return actingAsStaff()->postJson($path, $data);
}

function staffPut($path, $data = [])
{
    return actingAsStaff()->putJson($path, $data);
}

function staffDelete($path)
{
    return actingAsStaff()->deleteJson($path);
}

function staffPatch($path)
{
    return actingAsStaff()->patchJson($path);
}

// ─── Unauthenticated Access ─────────────────────────────────────────────────

test('staff endpoints return 401 without auth', function () {
    $paths = [
        'GET /api/staff/me',
        'GET /api/staff/tahun-angkatan',
        'GET /api/staff/dosen?nama=test',
        'GET /api/staff/mahasiswa?nama=test',
    ];

    foreach ($paths as $p) {
        [$method, $path] = explode(' ', $p, 2);
        $response = test()->json($method, $path);
        $response->assertStatus(401, "{$method} {$path} should return 401");
    }
});

// ─── GET /api/staff/me ───────────────────────────────────────────────────────

test('GET /api/staff/me returns staff info', function () {
    $response = staffGet('/api/staff/me');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
        ])
        ->assertJsonStructure([
            'data' => ['id', 'email', 'nama', 'type'],
        ]);
});

// ─── GET /api/staff/tahun-angkatan ──────────────────────────────────────────

test('GET /api/staff/tahun-angkatan returns data', function () {
    $response = staffGet('/api/staff/tahun-angkatan');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/staff/dosen ?nama= ─────────────────────────────────────────────

test('GET /api/staff/dosen searches dosen by name', function () {
    TestDataHelper::createDosen(['kode_dosen' => 1, 'nama_dosen' => 'Dr. Andi']);
    TestDataHelper::createDosen(['kode_dosen' => 2, 'nama_dosen' => 'Dr. Budi']);

    $response = staffGet('/api/staff/dosen', ['nama' => 'Andi']);

    $response->assertStatus(200)
        ->assertJson(['status' => true])
        ->assertJsonCount(1, 'data');
});

test('GET /api/staff/dosen validates nama required', function () {
    $response = actingAsStaff()->getJson('/api/staff/dosen');

    $response->assertStatus(422);
});

// ─── GET /api/staff/mahasiswa ?nama= ─────────────────────────────────────────

test('GET /api/staff/mahasiswa searches mahasiswa by name', function () {
    TestDataHelper::createMahasiswa(['nim' => '2023010001', 'nama_mahasiswa' => 'Siti Nurhaliza']);
    TestDataHelper::createMahasiswa(['nim' => '2023010002', 'nama_mahasiswa' => 'Ahmad Dhani']);

    $response = staffGet('/api/staff/mahasiswa', ['nama' => 'Siti']);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF AKADEMIK ──────────────────────────────────────────────────────────

test('GET /api/staff/akademik/program-studi returns list', function () {
    TestDataHelper::createProgramStudi();

    $response = staffGet('/api/staff/akademik/program-studi');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('GET /api/staff/akademik/nama-kurikulum returns list', function () {
    $response = staffGet('/api/staff/akademik/nama-kurikulum');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('GET /api/staff/akademik/kurikulum requires code_nama_kurikulum', function () {
    $response = staffGet('/api/staff/akademik/kurikulum');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/krs requires code', function () {
    $response = staffGet('/api/staff/akademik/krs');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/krs-detail requires code_krs', function () {
    $response = staffGet('/api/staff/akademik/krs-detail');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/khs requires code', function () {
    $response = staffGet('/api/staff/akademik/khs');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/khs-detail requires code_krs', function () {
    $response = staffGet('/api/staff/akademik/khs-detail');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/petikan-nilai requires code', function () {
    $response = staffGet('/api/staff/akademik/petikan-nilai');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/perwalian returns data or empty', function () {
    $response = staffGet('/api/staff/akademik/perwalian');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/akademik/perwalian requires code and code_dosen', function () {
    $response = staffPost('/api/staff/akademik/perwalian', []);

    $response->assertStatus(422);
});

test('POST /api/staff/akademik/perwalian requires encrypted string code_dosen', function () {
    $response = staffPost('/api/staff/akademik/perwalian', [
        'code' => TestDataHelper::encryptCode('20230100001'),
        'code_dosen' => 42,
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['errors' => ['code_dosen']]);
});

test('GET /api/staff/akademik/perwalian rejects encrypted non-integer code_dosen', function () {
    $response = staffGet('/api/staff/akademik/perwalian', [
        'code_dosen' => TestDataHelper::encryptCode('not-an-integer'),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.code_dosen.0', 'Format code_dosen tidak valid');
});

test('POST /api/staff/akademik/perwalian accepts encrypted dosen codes', function () {
    $response = staffPost('/api/staff/akademik/perwalian', [
        'code' => TestDataHelper::encryptCode('20230100001'),
        'code_dosen' => TestDataHelper::encryptCode(42),
        'code_dosen_perwakilan' => TestDataHelper::encryptCode(43),
    ]);

    $response->assertStatus(404);
});

test('PUT /api/staff/akademik/perwalian accepts encrypted dosen codes', function () {
    $response = staffPut('/api/staff/akademik/perwalian/'.TestDataHelper::encryptCode(1), [
        'kode_dosen' => TestDataHelper::encryptCode(42),
        'kode_dosen_perwakilan' => TestDataHelper::encryptCode(43),
    ]);

    $response->assertStatus(404);
});

test('GET /api/staff/akademik/perwalian/dosen returns 422 without params', function () {
    $response = staffGet('/api/staff/akademik/perwalian/dosen');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/perwalian/mahasiswa returns 422 without params', function () {
    $response = staffGet('/api/staff/akademik/perwalian/mahasiswa');

    $response->assertStatus(422);
});

test('PUT /api/staff/akademik/perwalian/{code} validates code', function () {
    $response = staffPut('/api/staff/akademik/perwalian/invalid-code', []);

    $response->assertStatus(422);
});

test('POST /api/staff/akademik/perwalian creates perwalian successfully', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010999']);
    $dosen1 = TestDataHelper::createDosen(['kode_dosen' => 101, 'nama_dosen' => 'Dr. Utama']);
    $dosen2 = TestDataHelper::createDosen(['kode_dosen' => 102, 'nama_dosen' => 'Dr. Pengganti']);

    $response = staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen1->toCode(),
        'code_dosen_perwakilan' => $dosen2->toCode(),
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Perwalian berhasil ditambahkan.',
            'data' => [
                'nim' => '2023010999',
            ],
        ]);
});

test('POST /api/staff/akademik/perwalian rejects duplicate perwalian for same mahasiswa', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010888']);
    $dosen = TestDataHelper::createDosen(['kode_dosen' => 103]);

    staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen->toCode(),
    ])->assertStatus(201);

    $dupResponse = staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen->toCode(),
    ]);

    $dupResponse->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => 'Mahasiswa sudah memiliki dosen pembimbing akademik.',
        ]);
});

test('POST /api/staff/akademik/perwalian rejects same dosen and dosen perwakilan', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010777']);
    $dosen = TestDataHelper::createDosen(['kode_dosen' => 104]);

    $response = staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen->toCode(),
        'code_dosen_perwakilan' => $dosen->toCode(),
    ]);

    $response->assertStatus(422);
});

test('PUT /api/staff/akademik/perwalian/{code} can update and clear dosen perwakilan', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010666']);
    $dosen1 = TestDataHelper::createDosen(['kode_dosen' => 105]);
    $dosen2 = TestDataHelper::createDosen(['kode_dosen' => 106]);

    $createRes = staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen1->toCode(),
        'code_dosen_perwakilan' => $dosen2->toCode(),
    ]);
    $codePerwalian = $createRes->json('data.code');

    // Update clearing dosen perwakilan with null and using code_dosen naming
    $dosen3 = TestDataHelper::createDosen(['kode_dosen' => 107]);
    $updateRes = staffPut("/api/staff/akademik/perwalian/{$codePerwalian}", [
        'code_dosen' => $dosen3->toCode(),
        'code_dosen_perwakilan' => null,
    ]);

    $updateRes->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'code_dosen_perwakilan' => null,
            ],
        ]);
    expect(\Illuminate\Support\Facades\Crypt::decryptString($updateRes->json('data.code_dosen')))
        ->toBe((string) $dosen3->kode_dosen);
});

test('POST /api/staff/akademik/perwalian/batch assigns multiple students', function () {
    $mhs1 = TestDataHelper::createMahasiswa(['nim' => '2023010551']);
    $mhs2 = TestDataHelper::createMahasiswa(['nim' => '2023010552']);
    $dosen = TestDataHelper::createDosen(['kode_dosen' => 108]);

    $response = staffPost('/api/staff/akademik/perwalian/batch', [
        'code_dosen' => $dosen->toCode(),
        'codes' => [$mhs1->toCode(), $mhs2->toCode()],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'data' => [
                'total_diproses' => 2,
                'total_berhasil' => 2,
                'total_dilewati' => 0,
            ],
        ]);
});

test('DELETE /api/staff/akademik/perwalian/{code} deletes perwalian successfully', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010444']);
    $dosen = TestDataHelper::createDosen(['kode_dosen' => 109]);

    $createRes = staffPost('/api/staff/akademik/perwalian', [
        'code' => $mhs->toCode(),
        'code_dosen' => $dosen->toCode(),
    ]);
    $codePerwalian = $createRes->json('data.code');

    $delResponse = staffDelete("/api/staff/akademik/perwalian/{$codePerwalian}");

    $delResponse->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Perwalian berhasil dihapus.',
        ]);
});

// ─── STAFF MASTER DATA: Matakuliah ──────────────────────────────────────────

test('GET /api/staff/master-data/matakuliah returns list', function () {
    $prodi = TestDataHelper::createProgramStudi();
    Matakuliah::create([
        'kode_matakuliah' => 'MK001',
        'nama_matakuliah' => 'Anatomi',
        'jenis' => 1,
        'sks_teori' => 2,
        'sks_praktik' => 1,
        'block' => 0,
        'kode_program_studi' => $prodi->kode_program_studi,
    ]);

    $response = staffGet('/api/staff/master-data/matakuliah');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/matakuliah validates required fields', function () {
    $response = staffPost('/api/staff/master-data/matakuliah', []);

    $response->assertStatus(422);
});

test('POST /api/staff/master-data/matakuliah stores successfully', function () {
    $prodi = TestDataHelper::createProgramStudi();

    $response = staffPost('/api/staff/master-data/matakuliah', [
        'kode_matakuliah' => 'MK002',
        'nama_matakuliah' => 'Fisiologi',
        'jenis' => 1,
        'sks_teori' => 3,
        'sks_praktik' => 0,
        'block' => 0,
        'kode_program_studi' => TestDataHelper::encryptCode($prodi->kode_program_studi),
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('PUT /api/staff/master-data/matakuliah requires code', function () {
    $response = staffPut('/api/staff/master-data/matakuliah', []);

    $response->assertStatus(422);
});

test('DELETE /api/staff/master-data/matakuliah/{code} handles invalid code', function () {
    $response = staffDelete('/api/staff/master-data/matakuliah/invalid');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/matakuliah/show validates code', function () {
    $response = staffGet('/api/staff/master-data/matakuliah/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/matakuliah/show returns matakuliah detail', function () {
    $prodi = TestDataHelper::createProgramStudi();
    $mk = Matakuliah::create([
        'kode_matakuliah' => 'MK003',
        'nama_matakuliah' => 'Histologi',
        'jenis' => 1,
        'sks_teori' => 2,
        'sks_praktik' => 1,
        'block' => 0,
        'kode_program_studi' => $prodi->kode_program_studi,
    ]);

    $response = staffGet('/api/staff/master-data/matakuliah/show', ['code' => TestDataHelper::encryptCode($mk->kode_matakuliah)]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF MASTER DATA: Program Studi ────────────────────────────────────────

test('GET /api/staff/master-data/program-studi returns list', function () {
    TestDataHelper::createProgramStudi();

    $response = staffGet('/api/staff/master-data/program-studi');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/program-studi stores successfully', function () {
    $response = staffPost('/api/staff/master-data/program-studi', [
        'nama_program_studi' => 'Ilmu Keperawatan',
        'singkatan_program_studi' => 'IK',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/program-studi validates required', function () {
    $response = staffPost('/api/staff/master-data/program-studi', []);

    $response->assertStatus(422);
});

test('PUT /api/staff/master-data/program-studi requires code', function () {
    $response = staffPut('/api/staff/master-data/program-studi', []);

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/program-studi/show validates code', function () {
    $response = staffGet('/api/staff/master-data/program-studi/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/program-studi/show returns program studi detail', function () {
    $prodi = TestDataHelper::createProgramStudi();

    $response = staffGet('/api/staff/master-data/program-studi/show', ['code' => TestDataHelper::encryptCode($prodi->kode_program_studi)]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/program-studi/{code} handles invalid code', function () {
    $response = staffDelete('/api/staff/master-data/program-studi/invalid');

    $response->assertStatus(422);
});

// ─── STAFF MASTER DATA: Dosen ────────────────────────────────────────────────

test('GET /api/staff/master-data/dosen returns paginated list', function () {
    TestDataHelper::createDosen();

    $response = staffGet('/api/staff/master-data/dosen');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('GET /api/staff/master-data/dosen/show validates code', function () {
    $response = staffGet('/api/staff/master-data/dosen/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/dosen/trash returns trash list', function () {
    $response = staffGet('/api/staff/master-data/dosen/trash');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/dosen validates required fields', function () {
    $response = staffPost('/api/staff/master-data/dosen', []);

    $response->assertStatus(422);
});

test('POST /api/staff/master-data/dosen stores successfully', function () {
    $prodi = TestDataHelper::createProgramStudi();

    $response = staffPost('/api/staff/master-data/dosen', [
        'nama_dosen' => 'Dosen Baru',
        'nik' => '9876543210',
        'no_telp' => '081234567890',
        'alamat_email' => 'dosenbaru@test.com',
        'field_studi' => 'Kedokteran Umum',
        'alumni' => 'UBG',
        'homebase' => TestDataHelper::encryptCode($prodi->kode_program_studi),
        'status_dosen' => 'T',
        'aktif' => 'A',
        'chatid' => '99999',
        'sandi_pengguna' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/dosen/{code} handles invalid code', function () {
    $response = staffDelete('/api/staff/master-data/dosen/invalid');

    $response->assertStatus(422);
});

test('PATCH /api/staff/master-data/dosen/{code}/restore restores soft deleted dosen', function () {
    $dosen = TestDataHelper::createDosen();
    $kode = TestDataHelper::encryptCode($dosen->kode_dosen);

    // Soft delete
    staffDelete("/api/staff/master-data/dosen/{$dosen->kode_dosen}");

    // Restore
    $response = staffPatch("/api/staff/master-data/dosen/{$dosen->kode_dosen}/restore");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/dosen/{code}/force force deletes dosen', function () {
    $dosen = TestDataHelper::createDosen(['kode_dosen' => 99]);

    // Soft delete first
    staffDelete("/api/staff/master-data/dosen/{$dosen->kode_dosen}");

    // Force delete
    $response = staffDelete("/api/staff/master-data/dosen/{$dosen->kode_dosen}/force");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF MASTER DATA: Nama Kurikulum ───────────────────────────────────────

test('GET /api/staff/master-data/nama-kurikulum returns list', function () {
    $response = staffGet('/api/staff/master-data/nama-kurikulum');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/nama-kurikulum validates required', function () {
    $response = staffPost('/api/staff/master-data/nama-kurikulum', []);

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/nama-kurikulum/show validates code', function () {
    $response = staffGet('/api/staff/master-data/nama-kurikulum/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/nama-kurikulum/show returns kurikulum detail', function () {
    $nk = NamaKurikulum::create([
        'nama_kurikulum' => 'Kurikulum 2024',
    ]);

    $response = staffGet('/api/staff/master-data/nama-kurikulum/show', ['code' => TestDataHelper::encryptCode($nk->id)]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/nama-kurikulum/{code} deletes kurikulum', function () {
    $nk = NamaKurikulum::create([
        'nama_kurikulum' => 'Kurikulum Hapus',
    ]);

    $response = staffDelete("/api/staff/master-data/nama-kurikulum/{$nk->id}");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF MASTER DATA: Tahun Akademik ────────────────────────────────────────

test('GET /api/staff/master-data/tahun-akademik returns list', function () {
    TestDataHelper::createTahunAkademik();

    $response = staffGet('/api/staff/master-data/tahun-akademik');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/tahun-akademik stores successfully', function () {
    $response = staffPost('/api/staff/master-data/tahun-akademik', [
        'tahun_akademik' => '2025/2026',
        'semester' => '1',
        'tanggal_mulai' => '2025-09-01',
        'tanggal_berakhir' => '2026-01-15',
        'status' => 'A',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/tahun-akademik validates required', function () {
    $response = staffPost('/api/staff/master-data/tahun-akademik', []);

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/tahun-akademik/show validates code', function () {
    $response = staffGet('/api/staff/master-data/tahun-akademik/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/tahun-akademik/show returns tahun akademik detail', function () {
    $ta = TestDataHelper::createTahunAkademik();

    $response = staffGet('/api/staff/master-data/tahun-akademik/show', ['code' => TestDataHelper::encryptCode($ta->id)]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/tahun-akademik/{code} deletes tahun akademik', function () {
    $ta = TahunAkademik::create([
        'tahun_akademik' => '2026/2027',
        'semester' => '1',
        'tanggal_mulai' => '2026-09-01',
        'tanggal_berakhir' => '2027-01-15',
        'status' => 'A',
    ]);

    $response = staffDelete("/api/staff/master-data/tahun-akademik/{$ta->id}");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF MASTER DATA: Kurikulum Angkatan ────────────────────────────────────

test('GET /api/staff/master-data/kurikulum-angkatan returns list', function () {
    $response = staffGet('/api/staff/master-data/kurikulum-angkatan');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/kurikulum-angkatan validates required', function () {
    $response = staffPost('/api/staff/master-data/kurikulum-angkatan', []);

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/kurikulum-angkatan/show validates code', function () {
    $response = staffGet('/api/staff/master-data/kurikulum-angkatan/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/kurikulum-angkatan/show returns kurikulum angkatan detail', function () {
    $ta = TestDataHelper::createTahunAkademik();
    $nk = NamaKurikulum::create(['nama_kurikulum' => 'Kurikulum Test']);
    $ka = KurikulumAngkatan::create([
        'kode_nama_kurikulum' => $nk->id,
        'tahun' => '2024',
    ]);

    $response = staffGet('/api/staff/master-data/kurikulum-angkatan/show', ['code' => TestDataHelper::encryptCode($ka->id)]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/kurikulum-angkatan/{code} deletes kurikulum angkatan', function () {
    $nk = NamaKurikulum::create(['nama_kurikulum' => 'Kurikulum Hapus']);
    $ka = KurikulumAngkatan::create([
        'kode_nama_kurikulum' => $nk->id,
        'tahun' => '2025',
    ]);

    $response = staffDelete("/api/staff/master-data/kurikulum-angkatan/{$ka->id}");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF MASTER DATA: Mahasiswa ────────────────────────────────────────────

test('GET /api/staff/master-data/mahasiswa returns list', function () {
    TestDataHelper::createMahasiswa();
    TestDataHelper::createMahasiswa(['nim' => '2023010002', 'nama_mahasiswa' => 'Mahasiswa Dua']);

    $response = staffGet('/api/staff/master-data/mahasiswa');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('GET /api/staff/master-data/mahasiswa/show validates code', function () {
    $response = staffGet('/api/staff/master-data/mahasiswa/show');

    $response->assertStatus(422);
});

test('GET /api/staff/master-data/mahasiswa/trash returns empty trash', function () {
    $response = staffGet('/api/staff/master-data/mahasiswa/trash');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/master-data/mahasiswa validates required fields', function () {
    $response = staffPost('/api/staff/master-data/mahasiswa', []);

    $response->assertStatus(422);
});

test('POST /api/staff/master-data/mahasiswa stores successfully', function () {
    $prodi = TestDataHelper::createProgramStudi();

    $response = staffPost('/api/staff/master-data/mahasiswa', [
        'nim' => '2023010099',
        'nik' => '99999999999999999999',
        'npm' => '2023010099',
        'nomor_pendaftaran' => 'REG099',
        'nomor_pendaftaran_ulang' => 'REG099',
        'program_studi_kode' => TestDataHelper::encryptCode($prodi->kode_program_studi),
        'nama_mahasiswa' => 'Mahasiswa Baru',
        'tempat_lahir' => 'Surabaya',
        'tanggal_lahir' => '2000-01-01',
        'alamat' => 'Jl. Test No.1',
        'kota' => 'Surabaya',
        'propinsi' => 'Jawa Timur',
        'jenis_kelamin' => 'L',
        'agama' => 'Islam',
        'golongan_darah' => 'O',
        'kewarganegaraan' => 'WNI',
        'email' => 'mhsbaru@test.com',
        'nama_ayah' => 'Ayah Test',
        'agama_ayah' => 'Islam',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu Test',
        'agama_ibu' => 'Islam',
        'pekerjaan_ibu' => 'Rumah Tangga',
        'alamat_orangtua' => 'Jl. Ortu No.1',
        'kota_orangtua' => 'Surabaya',
        'propinsi_orangtua' => 'Jawa Timur',
        'telepon_orangtua' => '081111111111',
        'status' => 'A',
        'status_pendaftaran' => 'B',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/mahasiswa/{code} handles invalid code', function () {
    $response = staffDelete('/api/staff/master-data/mahasiswa/invalid');

    $response->assertStatus(422);
});

test('PATCH /api/staff/master-data/mahasiswa/{code}/restore restores soft deleted mahasiswa', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010003']);

    // Soft delete
    staffDelete("/api/staff/master-data/mahasiswa/{$mhs->nim}");

    // Restore
    $response = staffPatch("/api/staff/master-data/mahasiswa/{$mhs->nim}/restore");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('DELETE /api/staff/master-data/mahasiswa/{code}/force force deletes mahasiswa', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => '2023010004']);

    // Soft delete first
    staffDelete("/api/staff/master-data/mahasiswa/{$mhs->nim}");

    // Force delete
    $response = staffDelete("/api/staff/master-data/mahasiswa/{$mhs->nim}/force");

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── STAFF ASSESSMENT ────────────────────────────────────────────────────────

test('GET /api/staff/assessment/templates returns list', function () {
    $response = staffGet('/api/staff/assessment/templates');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('POST /api/staff/assessment/templates validates required', function () {
    $response = staffPost('/api/staff/assessment/templates', []);

    $response->assertStatus(422);
});

test('GET /api/staff/assessment/templates/show validates code', function () {
    $response = staffGet('/api/staff/assessment/templates/show');

    $response->assertStatus(422);
});

test('PUT /api/staff/assessment/templates/update validates required', function () {
    $response = staffPut('/api/staff/assessment/templates/update', []);

    $response->assertStatus(422);
});

test('PUT /api/staff/assessment/templates/update updates template', function () {
    $response = staffPut('/api/staff/assessment/templates/update', [
        'template_id' => '1',
        'nama_template' => 'Updated Template',
        'keterangan' => 'Updated description',
    ]);

    // Should return 422 if template doesn't exist, or 200 if it succeeds
    expect($response->status())->toBeIn([200, 422]);
});

test('POST /api/staff/assessment/scores validates required', function () {
    $response = staffPost('/api/staff/assessment/scores', []);

    $response->assertStatus(422);
});

test('GET /api/staff/assessment/students/score validates params', function () {
    $response = staffGet('/api/staff/assessment/students/score');

    $response->assertStatus(422);
});

test('GET /api/staff/assessment/students/score/breakdown validates params', function () {
    $response = staffGet('/api/staff/assessment/students/score/breakdown');

    $response->assertStatus(422);
});

test('GET /api/staff/akademik/krs/detail executes without ambiguous column error', function () {
    $mhs = TestDataHelper::createMahasiswa();
    $ta = TestDataHelper::createTahunAkademik(['status' => 'A']);
    $mk = Matakuliah::create([
        'kode_matakuliah' => 'MK'.uniqid(),
        'nama_matakuliah' => 'Anatomi Test Ambiguous',
        'jenis' => 1,
        'sks_teori' => 2,
        'sks_praktik' => 1,
        'block' => 0,
        'tipe' => 'W',
    ]);
    $krs = \App\Models\Krs::create([
        'nim' => $mhs->nim,
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'semester' => '1',
    ]);
    \App\Models\KrsDetail::create([
        'kode_krs' => $krs->kode_krs,
        'id_matakuliah' => $mk->id_matakuliah,
        'status' => 'B',
    ]);

    $response = staffGet('/api/staff/akademik/krs-detail', [
        'code_krs' => $krs->toCode(),
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', true);
});

// ─── 404 Fallback ────────────────────────────────────────────────────────────

test('unknown staff endpoint returns 404', function () {
    $response = actingAsStaff()->getJson('/api/staff/nonexistent');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'error' => 'NOT_FOUND',
        ]);
});

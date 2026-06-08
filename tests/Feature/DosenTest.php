<?php

use App\Models\Dosen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsDosen()
{
    $dosen = TestDataHelper::createDosen();

    return (object) [
        'instance' => test()->actingAs($dosen, 'dosen_web'),
        'user' => $dosen,
    ];
}

function dosenGet($path, $params = [])
{
    $ctx = actingAsDosen();

    return $ctx->instance->getJson($path.($params ? '?'.http_build_query($params) : ''));
}

// ─── Unauthenticated Access ─────────────────────────────────────────────────

test('dosen endpoints return 401 without auth', function () {
    $paths = [
        'POST /api/dosen/logout',
        'GET /api/dosen/me',
        'PUT /api/dosen/profile/update',
        'GET /api/dosen',
        'GET /api/dosen/detail',
        'PUT /api/dosen',
        'GET /api/dosen/kurikulum',
        'GET /api/dosen/kurikulum/kelas',
        'GET /api/dosen/kurikulum/detail',
        'GET /api/dosen/perwalian/jumlah',
        'GET /api/dosen/perwalian/daftar',
        'GET /api/dosen/perwalian/riwayat',
        'GET /api/dosen/perwalian/krs',
        'POST /api/dosen/perwalian/validasi',
        'POST /api/dosen/perwalian/batal',
        'GET /api/dosen/penilaian',
        'GET /api/dosen/penilaian/mahasiswa',
        'POST /api/dosen/penilaian/input',
    ];

    foreach ($paths as $p) {
        [$method, $path] = explode(' ', $p, 2);
        $response = test()->json($method, $path);
        $response->assertStatus(401);
    }
});

// ─── GET /api/dosen/me ──────────────────────────────────────────────────────

test('GET /api/dosen/me returns authenticated user', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/me');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Profil dosen retrieved successfully.',
        ])
        ->assertJsonStructure([
            'data' => ['code', 'nik', 'no_telp', 'nama_dosen', 'alamat_email', 'status_dosen', 'homebase', 'nama_program_studi'],
        ]);
});

// ─── POST /api/dosen/logout ─────────────────────────────────────────────────

test('POST /api/dosen/logout works', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->postJson('/api/dosen/logout');

    $response->assertStatus(200)
        ->assertJson(['status' => true, 'message' => 'Logout berhasil.']);
});

// ─── PUT /api/dosen/profile/update ────────────────────────────────────────────

test('PUT /api/dosen/profile/update succeeds', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->putJson('/api/dosen/profile/update', [
        'no_telp' => '08111111111',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Profil berhasil diperbarui.',
        ]);
});

test('PUT /api/dosen/profile/update validates email format', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->putJson('/api/dosen/profile/update', [
        'alamat_email' => 'invalid-email',
    ]);

    $response->assertStatus(422);
});

test('PUT /api/dosen/profile/update changes password', function () {
    $ctx = actingAsDosen();

    $ctx->instance->putJson('/api/dosen/profile/update', [
        'sandi' => 'newpass123',
    ]);

    $ctx->user->refresh();
    expect(Hash::check('newpass123', $ctx->user->sandi_pengguna))->toBeTrue();
});

// ─── GET /api/dosen ──────────────────────────────────────────────────────────

test('GET /api/dosen returns paginated list', function () {
    TestDataHelper::createDosen(['kode_dosen' => 99, 'nama_dosen' => 'Dosen Kedua']);

    $response = dosenGet('/api/dosen');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Data dosen retrieved successfully.',
        ])
        ->assertJsonStructure([
            'data',
            'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
});

// ─── GET /api/dosen/detail ──────────────────────────────────────────────────

test('GET /api/dosen/detail returns dosen by code', function () {
    $dosen = TestDataHelper::createDosen();
    $code = $dosen->toCode();

    $response = dosenGet('/api/dosen/detail', ['code' => $code]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Data dosen retrieved successfully.',
        ])
        ->assertJsonPath('data.nama_dosen', $dosen->nama_dosen);
});

test('GET /api/dosen/detail returns 404 for invalid code', function () {
    $response = dosenGet('/api/dosen/detail', ['code' => 'invalid']);

    $response->assertStatus(404);
});

test('GET /api/dosen/detail returns 422 without code', function () {
    $response = dosenGet('/api/dosen/detail');

    $response->assertStatus(422);
});

// ─── PUT /api/dosen ──────────────────────────────────────────────────────────

test('PUT /api/dosen updates dosen data', function () {
    $dosen = TestDataHelper::createDosen();

    $response = dosenGet('/api/dosen', []);
    $payload = [
        'code' => $dosen->toCode(),
        'nama_dosen' => 'Nama Dosen Diupdate',
        'no_telp' => '08999999999',
    ];

    $ctx = actingAsDosen();
    $response = $ctx->instance->putJson('/api/dosen', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Data dosen berhasil diupdate.',
        ])
        ->assertJsonPath('data.nama_dosen', 'Nama Dosen Diupdate');
});

// ─── GET /api/dosen/kurikulum ────────────────────────────────────────────────

test('GET /api/dosen/kurikulum validates required params', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/kurikulum');

    $response->assertStatus(422);
});

// ─── GET /api/dosen/kurikulum/kelas ──────────────────────────────────────────

test('GET /api/dosen/kurikulum/kelas returns data or empty', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/kurikulum/kelas');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/dosen/kurikulum/detail ─────────────────────────────────────────

test('GET /api/dosen/kurikulum/detail returns 422 without code', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/kurikulum/detail');

    $response->assertStatus(422);
});

// ─── GET /api/dosen/perwalian/jumlah ─────────────────────────────────────────

test('GET /api/dosen/perwalian/jumlah returns count', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/perwalian/jumlah');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/dosen/perwalian/daftar ─────────────────────────────────────────

test('GET /api/dosen/perwalian/daftar returns list', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/perwalian/daftar');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/dosen/perwalian/riwayat ────────────────────────────────────────

test('GET /api/dosen/perwalian/riwayat returns history', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/perwalian/riwayat');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/dosen/perwalian/krs ────────────────────────────────────────────

test('GET /api/dosen/perwalian/krs returns 422 without code', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/perwalian/krs');

    $response->assertStatus(422);
});

test('GET /api/dosen/perwalian/krs returns krs detail', function () {
    $ctx = actingAsDosen();
    $mhs = TestDataHelper::createMahasiswa();

    $response = $ctx->instance->getJson('/api/dosen/perwalian/krs', ['code' => $mhs->toCode()]);

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── POST /api/dosen/perwalian/validasi ──────────────────────────────────────

test('POST /api/dosen/perwalian/validasi requires code and status', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->postJson('/api/dosen/perwalian/validasi', []);

    $response->assertStatus(422);
});

test('POST /api/dosen/perwalian/validasi validates status_krs', function () {
    $ctx = actingAsDosen();
    $mhs = TestDataHelper::createMahasiswa();

    $response = $ctx->instance->postJson('/api/dosen/perwalian/validasi', [
        'code_mahasiswa' => $mhs->toCode(),
        'status_krs' => 'X',
    ]);

    $response->assertStatus(422);
});

// ─── POST /api/dosen/perwalian/batal ─────────────────────────────────────────

test('POST /api/dosen/perwalian/batal requires code', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->postJson('/api/dosen/perwalian/batal', []);

    $response->assertStatus(422);
});

// ─── GET /api/dosen/penilaian ────────────────────────────────────────────────

test('GET /api/dosen/penilaian returns assessment tree or empty', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/penilaian');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/dosen/penilaian/mahasiswa ──────────────────────────────────────

test('GET /api/dosen/penilaian/mahasiswa requires code', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/penilaian/mahasiswa');

    $response->assertStatus(422);
});

// ─── POST /api/dosen/penilaian/input ─────────────────────────────────────────

test('POST /api/dosen/penilaian/input validates required fields', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->postJson('/api/dosen/penilaian/input', []);

    $response->assertStatus(422);
});

// ─── 404 Fallback ────────────────────────────────────────────────────────────

test('unknown dosen endpoint returns 404', function () {
    $ctx = actingAsDosen();

    $response = $ctx->instance->getJson('/api/dosen/nonexistent');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'error' => 'NOT_FOUND',
        ]);
});

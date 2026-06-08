<?php

use App\Models\Krs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function actingAsMahasiswa()
{
    $mhs = TestDataHelper::createMahasiswa();

    return (object) [
        'instance' => test()->actingAs($mhs, 'mahasiswa_web'),
        'user' => $mhs,
    ];
}

function mhsGet($path, $params = [])
{
    $ctx = actingAsMahasiswa();

    return $ctx->instance->getJson($path.($params ? '?'.http_build_query($params) : ''));
}

// ─── Unauthenticated Access ─────────────────────────────────────────────────

test('mahasiswa endpoints return 401 without auth', function () {
    $endpoints = [
        'GET', '/api/mhs/me',
        'POST', '/api/mhs/logout',
        'GET', '/api/mhs/profile',
        'PUT', '/api/mhs/profile/update',
        'GET', '/api/mhs/semester',
        'GET', '/api/mhs/kurikulum',
        'GET', '/api/mhs/krs',
        'GET', '/api/mhs/khs',
        'GET', '/api/mhs/petikan-nilai',
    ];

    for ($i = 0; $i < count($endpoints); $i += 2) {
        $method = $endpoints[$i];
        $path = $endpoints[$i + 1];
        $response = test()->json($method, $path);
        $response->assertStatus(401);
    }
});

// ─── GET /api/mhs/me ────────────────────────────────────────────────────────

test('GET /api/mhs/me returns authenticated user', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->getJson('/api/mhs/me');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
        ])
        ->assertJsonPath('data.nim', $ctx->user->nim);
});

// ─── POST /api/mhs/logout ───────────────────────────────────────────────────

test('POST /api/mhs/logout works', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->postJson('/api/mhs/logout');

    $response->assertStatus(200)
        ->assertJson(['status' => true, 'message' => 'Logout berhasil.']);
});

// ─── GET /api/mhs/profile ───────────────────────────────────────────────────

test('GET /api/mhs/profile returns profile with provinces', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->getJson('/api/mhs/profile');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
        ])
        ->assertJsonStructure([
            'data' => ['nim', 'nama_mahasiswa'],
            'provinces',
        ]);
});

// ─── PUT /api/mhs/profile/update ─────────────────────────────────────────────

test('PUT /api/mhs/profile/update succeeds with valid data', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->putJson('/api/mhs/profile/update', [
        'nama_mahasiswa' => 'Nama Baru',
        'tempat_lahir' => 'Jakarta',
        'jenis_kelamin' => 'L',
        'agama' => 'Islam',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Data mahasiswa berhasil diupdate.',
        ]);
});

test('PUT /api/mhs/profile/update validates gender', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->putJson('/api/mhs/profile/update', [
        'jenis_kelamin' => 'X',
    ]);

    $response->assertStatus(422);
});

test('PUT /api/mhs/profile/update validates province', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->putJson('/api/mhs/profile/update', [
        'propinsi' => 'Tidak Ada',
    ]);

    $response->assertStatus(422);
});

test('PUT /api/mhs/profile/update changes password', function () {
    $ctx = actingAsMahasiswa();

    $ctx->instance->putJson('/api/mhs/profile/update', [
        'sandi' => 'newpassword123',
    ]);

    $ctx->user->refresh();
    expect(Hash::check('newpassword123', $ctx->user->sandi))->toBeTrue();
});

// ─── GET /api/mhs/semester ───────────────────────────────────────────────────

test('GET /api/mhs/semester returns semester list', function () {
    $mhs = TestDataHelper::createMahasiswa();
    TahunAkademik::create([
        'tahun_akademik' => '2024/2025',
        'semester' => '1',
        'tanggal_mulai' => '2024-09-01',
        'tanggal_berakhir' => '2025-01-15',
        'status' => 'A',
    ]);
    Krs::create([
        'nim' => $mhs->nim,
        'kode_tahun_akademik' => 1,
        'semester' => 1,
        'sks_semester' => 0,
        'status_krs' => 'A',
    ]);

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/semester');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
        ])
        ->assertJsonStructure([
            'data' => ['semester'],
        ]);
});

// ─── GET /api/mhs/kurikulum ─────────────────────────────────────────────────

test('GET /api/mhs/kurikulum returns kurikulum or empty', function () {
    $mhs = TestDataHelper::createMahasiswa();

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/kurikulum');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/mhs/krs ────────────────────────────────────────────────────────

test('GET /api/mhs/krs returns krs or empty data', function () {
    $mhs = TestDataHelper::createMahasiswa();

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/krs');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

test('GET /api/mhs/krs filters by semester', function () {
    $mhs = TestDataHelper::createMahasiswa();

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/krs?semester=1');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/mhs/khs ────────────────────────────────────────────────────────

test('GET /api/mhs/khs returns khs or empty data', function () {
    $mhs = TestDataHelper::createMahasiswa();

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/khs');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── GET /api/mhs/petikan-nilai ──────────────────────────────────────────────

test('GET /api/mhs/petikan-nilai returns transcript or empty', function () {
    $mhs = TestDataHelper::createMahasiswa();

    $response = test()->actingAs($mhs, 'mahasiswa_web')
        ->getJson('/api/mhs/petikan-nilai');

    $response->assertStatus(200)
        ->assertJson(['status' => true]);
});

// ─── 404 Fallback within group ──────────────────────────────────────────────

test('unknown mhs endpoint returns 404', function () {
    $ctx = actingAsMahasiswa();

    $response = $ctx->instance->getJson('/api/mhs/nonexistent');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'error' => 'NOT_FOUND',
        ]);
});

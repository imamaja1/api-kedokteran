<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

// ─── CSRF Cookie ────────────────────────────────────────────────────────────

test('GET /api/auth/csrf-cookie returns success', function () {
    $response = $this->getJson('/api/auth/csrf-cookie');

    $response->assertStatus(200)
        ->assertJson(['message' => 'XSRF-TOKEN cookie set']);
});

// ─── MHS Login ──────────────────────────────────────────────────────────────

test('POST /api/auth/mhs/login succeeds with valid credentials', function () {
    TestDataHelper::createMahasiswa();

    $response = $this->postJson('/api/auth/mhs/login', [
        'nim' => '2023010001',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Login Mahasiswa berhasil.',
        ])
        ->assertJsonStructure([
            'data' => ['nim', 'nama', 'email', 'type'],
        ]);
});

test('POST /api/auth/mhs/login fails with wrong password', function () {
    TestDataHelper::createMahasiswa();

    $response = $this->postJson('/api/auth/mhs/login', [
        'nim' => '2023010001',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'NIM atau password salah.',
        ]);
});

test('POST /api/auth/mhs/login validates required fields', function () {
    $response = $this->postJson('/api/auth/mhs/login', [
        'nim' => '',
        'password' => '',
    ]);

    $response->assertStatus(422);
});

test('POST /api/auth/mhs/login logs in via email', function () {
    TestDataHelper::createMahasiswa(['email' => 'mhs@test.com']);

    $response = $this->postJson('/api/auth/mhs/login', [
        'nim' => 'mhs@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200);
});

// ─── Dosen Login ────────────────────────────────────────────────────────────

test('POST /api/auth/dosen/login succeeds with valid credentials', function () {
    TestDataHelper::createDosen();

    $response = $this->postJson('/api/auth/dosen/login', [
        'email' => 'dosen@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Login Dosen berhasil.',
        ])
        ->assertJsonStructure([
            'data' => ['kode_dosen', 'nik', 'nama', 'email', 'type'],
        ]);
});

test('POST /api/auth/dosen/login fails with wrong password', function () {
    TestDataHelper::createDosen();

    $response = $this->postJson('/api/auth/dosen/login', [
        'email' => 'dosen@test.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'Kode Dosen atau password salah.',
        ]);
});

test('POST /api/auth/dosen/login validates required fields', function () {
    $response = $this->postJson('/api/auth/dosen/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertStatus(422);
});

// ─── Staff Login ────────────────────────────────────────────────────────────

test('POST /api/auth/staff/login succeeds with valid credentials', function () {
    TestDataHelper::createStaff();

    $response = $this->postJson('/api/auth/staff/login', [
        'email' => 'staff@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Login Staff berhasil.',
        ])
        ->assertJsonStructure([
            'data' => ['id', 'email', 'nama', 'type'],
        ]);
});

test('POST /api/auth/staff/login fails with wrong credentials', function () {
    $response = $this->postJson('/api/auth/staff/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => false,
            'message' => 'Email atau password salah.',
        ]);
});

// ─── Logout ─────────────────────────────────────────────────────────────────

test('POST /api/auth/logout without auth returns 401', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});

test('POST /api/auth/logout succeeds when authenticated as mahasiswa', function () {
    $mahasiswa = TestDataHelper::createMahasiswa();

    $response = $this->actingAs($mahasiswa, 'mahasiswa_web')
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
});

test('POST /api/auth/logout succeeds when authenticated as dosen', function () {
    $dosen = TestDataHelper::createDosen();

    $response = $this->actingAs($dosen, 'dosen_web')
        ->postJson('/api/auth/logout');

    $response->assertStatus(200);
});

test('POST /api/auth/logout succeeds when authenticated as staff', function () {
    $staff = TestDataHelper::createStaff();

    $response = $this->actingAs($staff, 'staff_web')
        ->postJson('/api/auth/logout');

    $response->assertStatus(200);
});

// ─── 404 Fallback ────────────────────────────────────────────────────────────

test('GET /api/nonexistent returns 404 fallback', function () {
    $response = $this->getJson('/api/nonexistent');

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error' => 'NOT_FOUND',
        ]);
});

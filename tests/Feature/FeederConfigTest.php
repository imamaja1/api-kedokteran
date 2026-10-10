<?php

use App\Models\FeederCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    FeederCredential::truncate();
});

test('guest cannot access feeder config and is redirected to login', function () {
    $response = $this->get('/admin/feeder-config');
    $response->assertRedirect('/login');
});

test('staff cannot access feeder config and receives 403 forbidden', function () {
    $staff = User::where('role', 'staff')->first() ?? User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get('/admin/feeder-config');
    $response->assertStatus(403);
});

test('admin can access feeder config page and see all UI elements', function () {
    $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/feeder-config');

    $response->assertStatus(200);
    $response->assertSee('Konfigurasi Feeder PDDikti', false);
    $response->assertSee('feeder_url', false);
    $response->assertSee('feeder_port', false);
    $response->assertSee('feeder_endpoint', false);
    $response->assertSee('feeder_username', false);
    $response->assertSee('feeder_password', false);
    $response->assertSee('Simpan Konfigurasi', false);
    $response->assertSee('Uji Koneksi Feeder', false);
});

test('FeederCredential model encrypts values in database and decrypts on access', function () {
    $rawPassword = 'RahasiaFeeder2026!';

    FeederCredential::setValue('feeder_password', $rawPassword, 'Password Akun Feeder');

    // Cek langsung nilai mentah di basis data (harus bukan plain text)
    $dbRaw = DB::table('feeder_credentials')->where('key_name', 'feeder_password')->value('key_value');
    expect($dbRaw)->not->toBe($rawPassword);
    expect($dbRaw)->not->toBeEmpty();

    // Verifikasi dapat didekripsi via Laravel Crypt
    $decrypted = Crypt::decryptString($dbRaw);
    expect($decrypted)->toBe($rawPassword);

    // Verifikasi helper FeederCredential::getValue() mengembalikan teks asli
    expect(FeederCredential::getValue('feeder_password'))->toBe($rawPassword);
});

test('admin can update feeder credentials via form POST', function () {
    $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

    $payload = [
        'feeder_url' => 'http://192.168.1.100',
        'feeder_port' => '8082',
        'feeder_endpoint' => 'ws/live2.php',
        'feeder_username' => '081023',
        'feeder_password' => 'passwordSuperAman123',
    ];

    $response = $this->actingAs($admin)->post('/admin/feeder-config', $payload);

    $response->assertRedirect('/admin/feeder-config');
    $response->assertSessionHas('success', 'Konfigurasi Feeder berhasil disimpan.');

    expect(FeederCredential::getValue('feeder_url'))->toBe('http://192.168.1.100');
    expect(FeederCredential::getValue('feeder_port'))->toBe('8082');
    expect(FeederCredential::getValue('feeder_endpoint'))->toBe('ws/live2.php');
    expect(FeederCredential::getValue('feeder_username'))->toBe('081023');
    expect(FeederCredential::getValue('feeder_password'))->toBe('passwordSuperAman123');
});

test('empty password on update retains existing password', function () {
    $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

    FeederCredential::setValue('feeder_password', 'passwordLama123');

    $payload = [
        'feeder_url' => 'http://feeder.ubg.ac.id',
        'feeder_port' => '8082',
        'feeder_endpoint' => 'ws/live2.php',
        'feeder_username' => 'admin_feeder',
        'feeder_password' => '', // kosong, tidak ingin mengubah password
    ];

    $response = $this->actingAs($admin)->post('/admin/feeder-config', $payload);

    $response->assertRedirect('/admin/feeder-config');
    expect(FeederCredential::getValue('feeder_password'))->toBe('passwordLama123');
    expect(FeederCredential::getValue('feeder_url'))->toBe('http://feeder.ubg.ac.id');
});

test('admin can test connection and receive token upon successful GetToken', function () {
    $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

    Http::fake([
        'http://127.0.0.1:8082/ws/live2.php' => Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                'token' => 'sample_feeder_token_abc123xyz456',
            ],
        ], 200),
    ]);

    $payload = [
        'feeder_url' => 'http://127.0.0.1',
        'feeder_port' => '8082',
        'feeder_endpoint' => 'ws/live2.php',
        'feeder_username' => '081023',
        'feeder_password' => 'feederPass',
    ];

    $response = $this->actingAs($admin)->postJson('/admin/feeder-config/test', $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'token' => 'sample_feeder_token_abc123xyz456',
    ]);

    // Token harus tersimpan di database secara otomatis
    expect(FeederCredential::getValue('feeder_token'))->toBe('sample_feeder_token_abc123xyz456');
});

test('test connection returns proper error when feeder rejects authentication', function () {
    $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

    Http::fake([
        'http://127.0.0.1:8082/ws/live2.php' => Http::response([
            'error_code' => 100,
            'error_desc' => 'Username atau password salah',
            'data' => null,
        ], 200),
    ]);

    $payload = [
        'feeder_url' => 'http://127.0.0.1',
        'feeder_port' => '8082',
        'feeder_endpoint' => 'ws/live2.php',
        'feeder_username' => 'salah_user',
        'feeder_password' => 'salah_pass',
    ];

    $response = $this->actingAs($admin)->postJson('/admin/feeder-config/test', $payload);

    $response->assertStatus(400);
    $response->assertJson([
        'status' => false,
    ]);
});

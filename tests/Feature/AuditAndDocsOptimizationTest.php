<?php

use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;
use App\Models\AssessmentDosen;
use App\Models\AssessmentTemplate;
use App\Models\Dosen;
use App\Models\Grade;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Matakuliah;
use App\Models\NamaKelas;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Service\DocsSyncService;
use Database\Seeders\ApiEndpointParameterSeeder;
use Database\Seeders\JadwalDocsSeeder;
use Database\Seeders\KelasGroupDocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

test('staff api blocks non-staff guard and EnsureRole returns 403 json for api routes', function () {
    $mhs = TestDataHelper::createMahasiswa();
    $this->actingAs($mhs, 'mahasiswa_web')->getJson('/api/staff/me')->assertStatus(401);

    $user = new User(['role' => 'guest']);
    $this->actingAs($user, 'web');
    $middleware = new \App\Http\Middleware\EnsureRole();
    $request = \Illuminate\Http\Request::create('/api/staff/me', 'GET');
    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'admin', 'staff');

    expect($response->getStatusCode())->toBe(403);
    expect(json_decode($response->getContent(), true))->toMatchArray([
        'status' => false,
        'error' => 'FORBIDDEN',
    ]);
});

test('staff master data dosen index filters by homebase and plain text nama_dosen/alamat_email', function () {
    $staff = TestDataHelper::createStaff();
    $prodi1 = TestDataHelper::createProgramStudi(['kode_program_studi' => 10, 'nama_program_studi' => 'Kedokteran']);
    $prodi2 = TestDataHelper::createProgramStudi(['kode_program_studi' => 20, 'nama_program_studi' => 'Farmasi']);

    TestDataHelper::createDosen([
        'kode_dosen' => 1,
        'nama_dosen' => 'dr. Alpha',
        'alamat_email' => 'alpha@ubg.ac.id',
        'homebase' => $prodi1->kode_program_studi,
    ]);
    TestDataHelper::createDosen([
        'kode_dosen' => 2,
        'nama_dosen' => 'dr. Beta',
        'alamat_email' => 'beta@ubg.ac.id',
        'homebase' => $prodi2->kode_program_studi,
    ]);

    $resNama = $this->actingAs($staff, 'staff_web')
        ->getJson('/api/staff/master-data/dosen?nama_dosen=Alpha');
    $resNama->assertStatus(200)->assertJsonCount(1, 'data');

    $resProdi = $this->actingAs($staff, 'staff_web')
        ->getJson('/api/staff/master-data/dosen?kode_program_studi=' . urlencode($prodi2->toCode()));
    $resProdi->assertStatus(200)->assertJsonCount(1, 'data');
});

test('staff master data mahasiswa index accepts encrypted code and rejects invalid code with 422', function () {
    $staff = TestDataHelper::createStaff();
    $prodi = TestDataHelper::createProgramStudi(['kode_program_studi' => 11]);
    TestDataHelper::createMahasiswa(['nim' => '250100001', 'program_studi_kode' => $prodi->kode_program_studi]);

    $validRes = $this->actingAs($staff, 'staff_web')
        ->getJson('/api/staff/master-data/mahasiswa?code=' . urlencode($prodi->toCode()));
    $validRes->assertStatus(200)->assertJsonCount(1, 'data');

    $invalidRes = $this->actingAs($staff, 'staff_web')
        ->getJson('/api/staff/master-data/mahasiswa?code=invalid-encrypted-code');
    $invalidRes->assertStatus(422);
});

test('staff direct input khs works with huruf grade using Grade model', function () {
    $staff = TestDataHelper::createStaff();
    $prodi = TestDataHelper::createProgramStudi();
    $mhs = TestDataHelper::createMahasiswa(['program_studi_kode' => $prodi->kode_program_studi]);
    $ta = TestDataHelper::createTahunAkademik();
    $mk = Matakuliah::create([
        'kode_matakuliah' => 'MK101',
        'nama_matakuliah' => 'Anatomi',
        'sks_teori' => 2,
        'sks_praktik' => 0,
        'block' => 0,
        'kode_program_studi' => $prodi->kode_program_studi,
    ]);
    $krs = Krs::create(['nim' => $mhs->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => 1]);
    $detail = KrsDetail::create([
        'kode_krs_detail' => 1,
        'kode_krs' => $krs->kode_krs,
        'id_matakuliah' => $mk->id_matakuliah,
        'status' => 'B',
    ]);

    Grade::create([
        'kode_program_studi' => $prodi->kode_program_studi,
        'nilai_min' => 85,
        'nilai_max' => 100,
        'huruf' => 'A',
        'skor' => 4.0,
    ]);

    $response = $this->actingAs($staff, 'staff_web')->postJson('/api/staff/akademik/khs/direct-input', [
        'code_krs_detail' => $detail->toCode(),
        'grade' => 'A',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.grade', 'A')
        ->assertJsonPath('data.score', 4);
});

test('assessment dosen and tahun akademik remain open throughout the closing day', function () {
    $today = now()->toDateString();
    $ta = TestDataHelper::createTahunAkademik([
        'tanggal_buka_krs' => $today,
        'tanggal_tutup_krs' => $today,
    ]);
    expect($ta->isKrsOpen())->toBeTrue();

    $prodi = TestDataHelper::createProgramStudi();
    $mk = Matakuliah::create([
        'kode_matakuliah' => 'MK102',
        'nama_matakuliah' => 'Fisiologi',
        'sks_teori' => 2,
        'sks_praktik' => 0,
        'block' => 0,
        'kode_program_studi' => $prodi->kode_program_studi,
    ]);
    $nk = TestDataHelper::createNamaKurikulum(['kode_program_studi' => $prodi->kode_program_studi]);
    $ka = \App\Models\KurikulumAngkatan::create(['kode_nama_kurikulum' => $nk->kode_nama_kurikulum, 'angkatan' => '2024']);
    $template = AssessmentTemplate::create([
        'id_matakuliah' => $mk->id_matakuliah,
        'kode_nama_kurikulum' => $nk->kode_nama_kurikulum,
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'kode_kurikulum_angkatan' => $ka->kode_kurikulum_angkatan,
        'versi' => 1,
        'is_active' => true,
        'structure' => ['key' => 'root', 'name' => 'Root', 'weight' => 100, 'children' => [['key' => 'uts', 'name' => 'UTS', 'weight' => 100, 'type' => 'input']]],
    ]);
    $namaKelas = NamaKelas::create(['nama_kelas' => 'A']);
    $kelas = Kelas::create([
        'nama_kelas_id' => $namaKelas->nama_kelas_id,
        'semester' => 1,
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'kode_program_studi' => $prodi->kode_program_studi,
        'id_matakuliah' => $mk->id_matakuliah,
    ]);

    $assess = AssessmentDosen::create([
        'template_id' => $template->id,
        'kelas_id' => $kelas->kelas_id,
        'status' => 'aktif',
        'tanggal_buka' => $today,
        'tanggal_tutup' => $today,
    ]);
    expect($assess->isOpen())->toBeTrue();
});

test('docs sync cleans validation rules in body and prevents cross-role or bloated prerequisites', function () {
    $this->seed(JadwalDocsSeeder::class);
    $this->seed(KelasGroupDocsSeeder::class);
    $this->seed(ApiEndpointParameterSeeder::class);

    $endpoints = ApiEndpoint::with('parameters.sourceEndpoint')->get();
    expect($endpoints)->not->toBeEmpty();

    foreach ($endpoints as $ep) {
        if ($ep->body && $ep->body !== '-') {
            $decoded = json_decode($ep->body, true);
            expect(is_array($decoded))->toBeTrue("Body JSON invalid on {$ep->method} {$ep->url}");
        }

        foreach ($ep->parameters as $p) {
            if ($p->source_type === 'endpoint' && $p->sourceEndpoint) {
                $srcRole = $p->sourceEndpoint->auth_role;
                expect($srcRole === $ep->auth_role || $srcRole === 'Public / Session')
                    ->toBeTrue("Cross-role prerequisite on {$ep->url} param {$p->name} -> {$p->sourceEndpoint->url}");
            }
        }
    }
});

test('admin can sync docs and update endpoint parameters from admin panel', function () {
    $this->seed(JadwalDocsSeeder::class);
    $this->seed(ApiEndpointParameterSeeder::class);

    $admin = User::factory()->create(['role' => 'admin']);
    $ep = ApiEndpoint::first();

    $syncRes = $this->actingAs($admin)->post(route('admin.endpoints.sync-docs'));
    $syncRes->assertRedirect(route('admin.endpoints.index'));

    $editRes = $this->actingAs($admin)->get(route('admin.endpoints.edit', $ep));
    $editRes->assertStatus(200)->assertSee('Parameter &amp; Prerequisite', false);

    $updateRes = $this->actingAs($admin)->put(route('admin.endpoints.update', $ep), [
        'api_section_id' => $ep->api_section_id,
        'title' => $ep->title,
        'method' => $ep->method,
        'url' => $ep->url,
        'body' => '{"sample": "value"}',
        'parameters' => [
            [
                'name' => 'sample',
                'type' => 'string',
                'location' => 'body',
                'is_required' => '1',
                'source_type' => 'input',
                'example' => 'value',
            ],
        ],
    ]);
    $updateRes->assertRedirect(route('admin.endpoints.index'));
    expect(ApiEndpointParameter::where('api_endpoint_id', $ep->id)->where('name', 'sample')->exists())->toBeTrue();
});

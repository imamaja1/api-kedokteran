<?php

use App\Models\Hari;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Matakuliah;
use App\Models\NamaKelas;
use App\Models\Ruangan;
use App\Models\TahunAkademik;
use Database\Seeders\HariSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Helpers\TestDataHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HariSeeder::class);
});

// Helper functions for Staff test
function actingAsStaffJadwal()
{
    $staff = TestDataHelper::createStaff();
    return test()->actingAs($staff, 'staff_web');
}

function createSampleKelas(array $overrides = [])
{
    $prodi = TestDataHelper::createProgramStudi();
    $ta = TahunAkademik::active()->first() ?? TestDataHelper::createTahunAkademik(['status' => 'A']);
    $mk = Matakuliah::first() ?? Matakuliah::create([
        'kode_matakuliah' => 'MK'.uniqid(),
        'nama_matakuliah' => 'Anatomi Test',
        'jenis' => 1,
        'sks_teori' => 2,
        'sks_praktik' => 1,
        'block' => 0,
        'tipe' => 'W',
    ]);
    $namaKelas = NamaKelas::firstOrCreate(['nama_kelas' => 'A']);

    return Kelas::create(array_merge([
        'nama_kelas_id' => $namaKelas->nama_kelas_id,
        'semester' => '1',
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'kode_program_studi' => $prodi->kode_program_studi,
        'id_matakuliah' => $mk->id_matakuliah,
    ], $overrides));
}

// ─── MASTER RUANGAN TESTS ───────────────────────────────────────────────────

test('staff can create, read, update, toggle, and delete ruangan', function () {
    $client = actingAsStaffJadwal();

    // 1. Create Ruangan
    $resStore = $client->postJson('/api/staff/ruangan', [
        'kode_ruangan' => 'RK.TEST.01',
        'nama_ruangan' => 'Ruang Kuliah Test 01',
        'kapasitas' => 50,
        'jenis' => 'kuliah',
        'lokasi' => 'Gedung FK Lt. 1',
    ]);
    $resStore->assertStatus(201)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.kode_ruangan', 'RK.TEST.01');

    $ruanganId = $resStore->json('data.id');

    // 2. Read list & detail
    $resList = $client->getJson('/api/staff/ruangan');
    $resList->assertStatus(200)
        ->assertJsonPath('status', true);

    $resShow = $client->getJson("/api/staff/ruangan/{$ruanganId}");
    $resShow->assertStatus(200)
        ->assertJsonPath('data.kode_ruangan', 'RK.TEST.01');

    // 3. Update Ruangan
    $resUpdate = $client->putJson("/api/staff/ruangan/{$ruanganId}", [
        'nama_ruangan' => 'Ruang Kuliah Test Renamed',
        'kapasitas' => 60,
    ]);
    $resUpdate->assertStatus(200)
        ->assertJsonPath('data.nama_ruangan', 'Ruang Kuliah Test Renamed')
        ->assertJsonPath('data.kapasitas', 60);

    // 4. Toggle status aktif
    $resToggle = $client->patchJson("/api/staff/ruangan/{$ruanganId}/toggle");
    $resToggle->assertStatus(200)
        ->assertJsonPath('data.status_aktif', false);

    // Re-enable
    $client->patchJson("/api/staff/ruangan/{$ruanganId}/toggle");

    // 5. Delete Ruangan
    $resDelete = $client->deleteJson("/api/staff/ruangan/{$ruanganId}");
    $resDelete->assertStatus(200)
        ->assertJsonPath('status', true);
});

// ─── MASTER HARI TESTS ──────────────────────────────────────────────────────

test('staff can list days and toggle status aktif', function () {
    $client = actingAsStaffJadwal();

    $resList = $client->getJson('/api/staff/hari');
    $resList->assertStatus(200)
        ->assertJsonPath('status', true);

    $senin = Hari::where('nama_hari', 'Senin')->first();

    $resToggle = $client->patchJson("/api/staff/hari/{$senin->id}/toggle");
    $resToggle->assertStatus(200)
        ->assertJsonPath('data.status_aktif', false);

    // Re-enable
    $client->patchJson("/api/staff/hari/{$senin->id}/toggle");
});

// ─── PENJADWALAN KELAS & CONFLICT RULES ──────────────────────────────────────

test('B-04: reject schedule if jam_selesai is earlier than or equal to jam_mulai', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();
    $ruangan = Ruangan::create([
        'kode_ruangan' => 'RK.VAL.01',
        'nama_ruangan' => 'Ruang Validasi',
        'kapasitas' => 40,
        'status_aktif' => true,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    $response = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '10:00',
        'jam_selesai' => '08:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('B-05: reject schedule if room or day is inactive', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();
    $ruanganInactive = Ruangan::create([
        'kode_ruangan' => 'RK.OFF',
        'nama_ruangan' => 'Ruang Rusak',
        'kapasitas' => 40,
        'status_aktif' => false,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    $response = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruanganInactive->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('B-01: reject room conflict on overlapping time (HTTP 409) but allow adjacent schedule', function () {
    $client = actingAsStaffJadwal();
    $kelas1 = createSampleKelas();
    $kelas2 = createSampleKelas(['nama_kelas_id' => NamaKelas::firstOrCreate(['nama_kelas' => 'B'])->nama_kelas_id]);

    $ruangan = Ruangan::create([
        'kode_ruangan' => 'RK.CONF.01',
        'nama_ruangan' => 'Ruang Bentrok',
        'kapasitas' => 40,
        'status_aktif' => true,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    // 1. Jadwal pertama: 08:00 - 10:00
    $res1 = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas1->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
    ]);
    $res1->assertStatus(201);

    // 2. Jadwal kedua bentrok: 09:00 - 11:00 (irisan dengan 08:00-10:00)
    $resConflict = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas2->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '09:00',
        'jam_selesai' => '11:00',
    ]);
    $resConflict->assertStatus(409)
        ->assertJsonPath('status', false)
        ->assertJsonPath('error', 'ROOM_SCHEDULE_CONFLICT');

    // 3. Jadwal adjacent diperbolehkan: 10:00 - 12:00
    $resAdjacent = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas2->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
    ]);
    $resAdjacent->assertStatus(201)
        ->assertJsonPath('status', true);
});

test('B-03: capacity warning when room capacity is less than class students count', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();

    // Buat mahasiswa dan daftarkan ke kelas
    $mhs1 = TestDataHelper::createMahasiswa();
    $mhs2 = TestDataHelper::createMahasiswa();
    $krs1 = Krs::create(['nim' => $mhs1->nim, 'kode_tahun_akademik' => $kelas->kode_tahun_akademik, 'semester' => '1', 'status' => 'A']);
    $krs2 = Krs::create(['nim' => $mhs2->nim, 'kode_tahun_akademik' => $kelas->kode_tahun_akademik, 'semester' => '1', 'status' => 'A']);
    $kd1 = KrsDetail::create(['kode_krs_detail' => 90001, 'kode_krs' => $krs1->kode_krs, 'id_matakuliah' => $kelas->id_matakuliah, 'status' => 'B']);
    $kd2 = KrsDetail::create(['kode_krs_detail' => 90002, 'kode_krs' => $krs2->kode_krs, 'id_matakuliah' => $kelas->id_matakuliah, 'status' => 'B']);
    KelasMahasiswa::create(['kelas_id' => $kelas->kelas_id, 'kode_krs_detail' => $kd1->kode_krs_detail]);
    KelasMahasiswa::create(['kelas_id' => $kelas->kelas_id, 'kode_krs_detail' => $kd2->kode_krs_detail]);

    // Ruangan hanya berkapasitas 1 orang (mahasiswa ada 2)
    $ruanganKecil = Ruangan::create([
        'kode_ruangan' => 'RK.MINI',
        'nama_ruangan' => 'Ruang Mini',
        'kapasitas' => 1,
        'status_aktif' => true,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    $response = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruanganKecil->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '13:00',
        'jam_selesai' => '15:00',
    ]);

    // Tersimpan (201) namun memuat warnings
    $response->assertStatus(201)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.kapasitas_warning', true);

    expect($response->json('warnings'))->not->toBeEmpty();
});

test('staff can read schedules by kelas, ruangan, hari, and check status-kelas', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();
    $ruangan = Ruangan::create([
        'kode_ruangan' => 'RK.READ.01',
        'nama_ruangan' => 'Ruang Baca',
        'kapasitas' => 40,
        'status_aktif' => true,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    // Simpan 1 jadwal
    $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '08:00',
        'jam_selesai' => '09:40',
    ])->assertStatus(201);

    // 1. By Kelas
    $resKelas = $client->getJson("/api/staff/jadwal-kelas/kelas/{$kelas->kelas_id}");
    $resKelas->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.total_jadwal', 1);

    // 2. By Ruangan
    $resRuangan = $client->getJson("/api/staff/jadwal-kelas/ruangan/{$ruangan->id}");
    $resRuangan->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.total_jadwal', 1);

    // 3. By Hari
    $resHari = $client->getJson("/api/staff/jadwal-kelas/hari/{$senin->id}");
    $resHari->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.total_jadwal', 1);

    // 4. Status Kelas (filter sudah_terjadwal & belum_terjadwal)
    $resStatus = $client->getJson('/api/staff/jadwal-kelas/status-kelas');
    $resStatus->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.summary.sudah_terjadwal', 1);

    $resSudah = $client->getJson('/api/staff/jadwal-kelas/status-kelas?status=sudah_terjadwal');
    $resSudah->assertStatus(200);
    expect($resSudah->json('data.data'))->toHaveCount(1);
});

// ─── MAHASISWA JADWAL TESTS ─────────────────────────────────────────────────

test('mahasiswa can view their enrolled classes schedule', function () {
    $kelas = createSampleKelas();
    $ruangan = Ruangan::create([
        'kode_ruangan' => 'RK.MHS.01',
        'nama_ruangan' => 'Ruang Kuliah Mhs',
        'kapasitas' => 50,
        'status_aktif' => true,
    ]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    // Buat jadwal untuk kelas tersebut
    $staffClient = actingAsStaffJadwal();
    $staffClient->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruangan->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '08:00',
        'jam_selesai' => '09:40',
    ])->assertStatus(201);

    // Buat Mahasiswa dan daftarkan ke kelas
    $mhs = TestDataHelper::createMahasiswa();
    $krs = Krs::create([
        'nim' => $mhs->nim,
        'kode_tahun_akademik' => $kelas->kode_tahun_akademik,
        'semester' => '1',
        'status' => 'A',
    ]);
    $kd = KrsDetail::create([
        'kode_krs_detail' => 99001,
        'kode_krs' => $krs->kode_krs,
        'id_matakuliah' => $kelas->id_matakuliah,
        'status' => 'B',
    ]);
    KelasMahasiswa::create([
        'kelas_id' => $kelas->kelas_id,
        'kode_krs_detail' => $kd->kode_krs_detail,
    ]);

    // Test unauthenticated
    test()->getJson('/api/mhs/jadwal')->assertStatus(401);

    // Test authenticated
    $mhsClient = test()->actingAs($mhs, 'mahasiswa_web');
    $response = $mhsClient->getJson('/api/mhs/jadwal');

    $response->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.total_jadwal', 1)
        ->assertJsonPath('data.jadwal_list.0.nama_kelas', 'A')
        ->assertJsonPath('data.jadwal_list.0.ruangan.kode_ruangan', 'RK.MHS.01');
});

test('staff can create multi-schedule for one class, update, copy, and delete', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();
    $ruangan1 = Ruangan::create(['kode_ruangan' => 'RK.M1', 'nama_ruangan' => 'Ruang 1', 'kapasitas' => 40, 'status_aktif' => true]);
    $ruangan2 = Ruangan::create(['kode_ruangan' => 'RK.M2', 'nama_ruangan' => 'Ruang 2', 'kapasitas' => 40, 'status_aktif' => true]);
    $senin = Hari::where('nama_hari', 'Senin')->first();
    $rabu = Hari::where('nama_hari', 'Rabu')->first();

    // 1. Jadwal pertama: Senin di Ruang 1
    $res1 = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruangan1->id,
        'hari_id' => $senin->id,
        'jam_mulai' => '08:00',
        'jam_selesai' => '09:40',
    ]);
    $res1->assertStatus(201);
    $jadwal1Id = $res1->json('data.id');

    // 2. Multi-schedule: Jadwal kedua untuk kelas yang sama di hari Rabu Ruang 2
    $res2 = $client->postJson('/api/staff/jadwal-kelas', [
        'kelas_id' => $kelas->kelas_id,
        'ruangan_id' => $ruangan2->id,
        'hari_id' => $rabu->id,
        'jam_mulai' => '10:00',
        'jam_selesai' => '11:40',
    ]);
    $res2->assertStatus(201);
    $jadwal2Id = $res2->json('data.id');

    // Verifikasi kelas sekarang punya 2 jadwal
    $resList = $client->getJson("/api/staff/jadwal-kelas/kelas/{$kelas->kelas_id}");
    $resList->assertStatus(200)
        ->assertJsonPath('data.total_jadwal', 2);

    // 3. Update jadwal 1 (tanpa mengubah jam atau ruangan, tidak boleh bentrok dengan diri sendiri)
    $resUpdate = $client->putJson("/api/staff/jadwal-kelas/{$jadwal1Id}", [
        'keterangan' => 'Pertemuan Daring via Zoom',
    ]);
    $resUpdate->assertStatus(200)
        ->assertJsonPath('data.keterangan', 'Pertemuan Daring via Zoom');

    // 4. Salin jadwal (copy)
    $kamis = Hari::where('nama_hari', 'Kamis')->first();
    $resCopy = $client->postJson("/api/staff/jadwal-kelas/{$jadwal1Id}/copy", [
        'hari_id' => $kamis->id,
    ]);
    $resCopy->assertStatus(201)
        ->assertJsonPath('data.hari.nama_hari', 'Kamis');
    $jadwalCopyId = $resCopy->json('data.id');

    // 5. Delete jadwal copy
    $resDelete = $client->deleteJson("/api/staff/jadwal-kelas/{$jadwalCopyId}");
    $resDelete->assertStatus(200)
        ->assertJsonPath('status', true);
});

test('encryption support: endpoints return code_* and accept code_* in payloads and route parameters', function () {
    $client = actingAsStaffJadwal();
    $kelas = createSampleKelas();
    $ruangan = Ruangan::create(['kode_ruangan' => 'RK.ENC.01', 'nama_ruangan' => 'Ruang Enkripsi', 'kapasitas' => 40, 'status_aktif' => true]);
    $senin = Hari::where('nama_hari', 'Senin')->first();

    // 1. Ruangan returns code and can be retrieved via encrypted code in route
    $codeRuangan = $ruangan->toCode();
    expect($codeRuangan)->toBeString()->not->toBeEmpty();

    $resRuangan = $client->getJson("/api/staff/ruangan/{$codeRuangan}");
    $resRuangan->assertStatus(200);
    expect(\Illuminate\Support\Facades\Crypt::decryptString($resRuangan->json('data.code')))->toBe((string) $ruangan->id);
    expect(\Illuminate\Support\Facades\Crypt::decryptString($resRuangan->json('data.code_ruangan')))->toBe((string) $ruangan->id);

    // 2. Hari returns code and can be toggled via encrypted code
    $codeHari = $senin->toCode();
    $resHari = $client->getJson("/api/staff/hari/{$codeHari}");
    $resHari->assertStatus(200);
    expect(\Illuminate\Support\Facades\Crypt::decryptString($resHari->json('data.code_hari')))->toBe((string) $senin->id);

    // 3. Store schedule using ONLY encrypted code_* fields (code_kelas, code_ruangan, code_hari)
    $resStore = $client->postJson('/api/staff/jadwal-kelas', [
        'code_kelas' => $kelas->toCode(),
        'code_ruangan' => $ruangan->toCode(),
        'code_hari' => $senin->toCode(),
        'jam_mulai' => '08:00',
        'jam_selesai' => '09:40',
    ]);

    $resStore->assertStatus(201)
        ->assertJsonPath('status', true);

    $dataJadwal = $resStore->json('data');
    expect($dataJadwal)->toHaveKeys([
        'code',
        'code_jadwal',
        'code_kelas',
        'code_ruangan',
        'code_hari',
        'code_tahun_akademik',
    ]);
    expect($dataJadwal['matakuliah'])->toHaveKey('code_matakuliah');

    $codeJadwal = $dataJadwal['code_jadwal'];

    // 4. Retrieve schedule by encrypted code via /{code}
    $resShow = $client->getJson("/api/staff/jadwal-kelas/{$codeJadwal}");
    $resShow->assertStatus(200);
    expect(\Illuminate\Support\Facades\Crypt::decryptString($resShow->json('data.code_jadwal')))
        ->toBe((string) \Illuminate\Support\Facades\Crypt::decryptString($codeJadwal));

    // 5. Update schedule by encrypted code via URL parameter PUT /{code}
    $resUpdate = $client->putJson("/api/staff/jadwal-kelas/{$codeJadwal}", [
        'keterangan' => 'Diperbarui via code',
    ]);
    $resUpdate->assertStatus(200)
        ->assertJsonPath('data.keterangan', 'Diperbarui via code');

    // 7. Sub-endpoints accept encrypted codes
    $resSubKelas = $client->getJson("/api/staff/jadwal-kelas/kelas/{$kelas->toCode()}");
    $resSubKelas->assertStatus(200)->assertJsonPath('status', true);

    $resSubRuangan = $client->getJson("/api/staff/jadwal-kelas/ruangan/{$ruangan->toCode()}");
    $resSubRuangan->assertStatus(200)->assertJsonPath('status', true);

    $resSubHari = $client->getJson("/api/staff/jadwal-kelas/hari/{$senin->toCode()}");
    $resSubHari->assertStatus(200)->assertJsonPath('status', true);

    // 8. Delete schedule by encrypted code via DELETE /{code}
    $resDelete = $client->deleteJson("/api/staff/jadwal-kelas/{$codeJadwal}");
    $resDelete->assertStatus(200)->assertJsonPath('status', true);

    // 9. Invalid encrypted code on POST returns 422 validation error
    $resInvalidPost = $client->postJson('/api/staff/jadwal-kelas', [
        'code_kelas' => 'invalid-token-here',
        'code_ruangan' => $ruangan->toCode(),
        'code_hari' => $senin->toCode(),
        'jam_mulai' => '10:00',
        'jam_selesai' => '11:40',
    ]);
    $resInvalidPost->assertStatus(422)
        ->assertJsonPath('status', false)
        ->assertJsonValidationErrors(['code_kelas']);

    // 10. Invalid encrypted code on DELETE returns 422
    $resInvalidDelete = $client->deleteJson('/api/staff/jadwal-kelas/invalid-encrypted-token');
    $resInvalidDelete->assertStatus(422)
        ->assertJsonPath('status', false)
        ->assertJsonValidationErrors(['code']);
});

test('staff can use general master data style (show?code= and PUT with body) for ruangan, hari, and jadwal-kelas', function () {
    $client = actingAsStaffJadwal();

    // 1. Ruangan: show?code= and PUT with body code
    $ruangan = App\Models\Ruangan::create([
        'kode_ruangan' => 'R-TEST-MD',
        'nama_ruangan' => 'Ruang Master Data',
        'kapasitas' => 40,
        'status_aktif' => true,
    ]);

    $resShowRuangan = $client->getJson('/api/staff/ruangan/show?code='.$ruangan->toCode());
    $resShowRuangan->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.kode_ruangan', 'R-TEST-MD');

    $resPutRuangan = $client->putJson('/api/staff/ruangan', [
        'code' => $ruangan->toCode(),
        'nama_ruangan' => 'Ruang Master Data Updated',
    ]);
    $resPutRuangan->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.nama_ruangan', 'Ruang Master Data Updated');

    // 2. Hari: show?code= and PUT with body code
    $senin = App\Models\Hari::where('nama_hari', 'Senin')->first();
    $resShowHari = $client->getJson('/api/staff/hari/show?code='.$senin->toCode());
    $resShowHari->assertStatus(200)
        ->assertJsonPath('status', true);

    $resPutHari = $client->putJson('/api/staff/hari', [
        'code' => $senin->toCode(),
        'status_aktif' => true,
    ]);
    $resPutHari->assertStatus(200)
        ->assertJsonPath('status', true);

    // 3. Jadwal Kelas: show?code=, filter ?code=, and PUT with body code
    $kelas = createSampleKelas();

    $resStoreJadwal = $client->postJson('/api/staff/jadwal-kelas', [
        'code_kelas' => $kelas->toCode(),
        'code_ruangan' => $ruangan->toCode(),
        'code_hari' => $senin->toCode(),
        'jam_mulai' => '14:00',
        'jam_selesai' => '15:40',
    ]);
    $codeJadwal = $resStoreJadwal->json('data.code');

    $resShowJadwal = $client->getJson('/api/staff/jadwal-kelas/show?code='.$codeJadwal);
    $resShowJadwal->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.jam_mulai', '14:00');

    $resFilterKelas = $client->getJson('/api/staff/jadwal-kelas/kelas?code='.$kelas->toCode());
    $resFilterKelas->assertStatus(200)->assertJsonPath('status', true);

    $resFilterRuangan = $client->getJson('/api/staff/jadwal-kelas/ruangan?code='.$ruangan->toCode());
    $resFilterRuangan->assertStatus(200)->assertJsonPath('status', true);

    $resFilterHari = $client->getJson('/api/staff/jadwal-kelas/hari?code='.$senin->toCode());
    $resFilterHari->assertStatus(200)->assertJsonPath('status', true);

    $resUpdateJadwal = $client->putJson('/api/staff/jadwal-kelas', [
        'code' => $codeJadwal,
        'jam_mulai' => '14:30',
        'jam_selesai' => '16:10',
    ]);
    $resUpdateJadwal->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.jam_mulai', '14:30');
});



<?php

use App\Models\FeederCredential;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KhsDetail;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\NamaKelas;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Helpers\TestDataHelper;

beforeEach(function () {
    Cache::flush();
    FeederCredential::truncate();

    FeederCredential::setValue('feeder_url', 'http://127.0.0.1:8082');
    FeederCredential::setValue('feeder_port', '8082');
    FeederCredential::setValue('feeder_endpoint', 'ws/live2.php');
    FeederCredential::setValue('feeder_username', '081023');
    FeederCredential::setValue('feeder_password', 'secretPass');
    FeederCredential::setValue('feeder_token', 'valid_feeder_token_xyz');
});

function actingAsStaffUser()
{
    $staff = TestDataHelper::createStaff();
    return test()->actingAs($staff, 'staff_web');
}

function createTestMatakuliah(array $overrides = []): Matakuliah
{
    $prodi = ProgramStudi::first() ?? TestDataHelper::createProgramStudi();
    return Matakuliah::create(array_merge([
        'kode_matakuliah' => 'MK'.rand(100, 999),
        'nama_matakuliah' => 'Matakuliah Test',
        'jenis' => 1,
        'sks_teori' => 2,
        'sks_praktik' => 1,
        'block' => 0,
        'kode_program_studi' => $prodi->kode_program_studi,
    ], $overrides));
}

test('guest cannot access feeder api and gets 401', function () {
    $response = $this->getJson('/api/staff/feeder/status');
    $response->assertStatus(401);
});

test('staff can check feeder status', function () {
    $response = actingAsStaffUser()->getJson('/api/staff/feeder/status');

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'data' => [
            'connected' => true,
            'has_token' => true,
        ],
    ]);
});

test('staff can pull and cache class grades from feeder using code_kelas', function () {
    $prodi = ProgramStudi::first() ?? TestDataHelper::createProgramStudi();
    $ta = TahunAkademik::first() ?? TestDataHelper::createTahunAkademik();
    $mk = Matakuliah::first() ?? createTestMatakuliah([
        'kode_matakuliah' => 'KD101',
        'nama_matakuliah' => 'Anatomi Umum',
    ]);
    $namaKelas = NamaKelas::first() ?? NamaKelas::create(['nama_kelas' => 'A']);

    $kelas = Kelas::create([
        'nama_kelas_id' => $namaKelas->nama_kelas_id,
        'semester' => '1',
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'kode_program_studi' => $prodi->kode_program_studi,
        'id_matakuliah' => $mk->id_matakuliah,
    ]);

    Http::fake([
        'http://127.0.0.1:8082/ws/live2.php' => Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                [
                    'nim' => '2026001',
                    'nama_mahasiswa' => 'Siti Aminah',
                    'kode_mata_kuliah' => 'KD101',
                    'nama_mata_kuliah' => 'Anatomi Umum',
                    'nilai_angka' => 85.50,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                ],
            ],
        ], 200),
    ]);

    $codeKelas = $kelas->toCode();
    $response = actingAsStaffUser()->getJson("/api/staff/feeder/pull/kelas/{$codeKelas}");

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'data' => [
            'kelas_id' => $kelas->kelas_id,
            'total_rows' => 1,
        ],
    ]);

    // Pastikan data tersimpan di Cache
    expect(Cache::has("feeder_pull_kelas_{$kelas->kelas_id}"))->toBeTrue();
});

test('staff can audit class grades detecting match, discrepancy, and fuzzy matching', function () {
    $prodi = ProgramStudi::first() ?? TestDataHelper::createProgramStudi();
    $ta = TahunAkademik::first() ?? TestDataHelper::createTahunAkademik();
    $mk = Matakuliah::first() ?? createTestMatakuliah([
        'kode_matakuliah' => 'KD102',
        'nama_matakuliah' => 'Fisiologi Dasar',
    ]);
    $namaKelas = NamaKelas::first() ?? NamaKelas::create(['nama_kelas' => 'B']);

    $kelas = Kelas::create([
        'nama_kelas_id' => $namaKelas->nama_kelas_id,
        'semester' => '1',
        'kode_tahun_akademik' => $ta->kode_tahun_akademik,
        'kode_program_studi' => $prodi->kode_program_studi,
        'id_matakuliah' => $mk->id_matakuliah,
    ]);

    // 1. Mahasiswa 1: Nilai sama (SESUAI)
    $mhs1 = TestDataHelper::createMahasiswa(['nim' => 'NIM001', 'nama_mahasiswa' => 'Mahasiswa Cocok']);
    $krs1 = Krs::create(['nim' => $mhs1->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => '1']);
    $kd1 = KrsDetail::create(['kode_krs_detail' => 101, 'kode_krs' => $krs1->kode_krs, 'id_matakuliah' => $mk->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 101, 'nilai_akhir' => 80.00, 'grade' => 'A', 'score' => 4.00]);
    KelasMahasiswa::create(['kelas_id' => $kelas->kelas_id, 'kode_krs_detail' => 101]);

    // 2. Mahasiswa 2: Nilai berbeda (BERBEDA)
    $mhs2 = TestDataHelper::createMahasiswa(['nim' => 'NIM002', 'nama_mahasiswa' => 'Mahasiswa Beda']);
    $krs2 = Krs::create(['nim' => $mhs2->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => '1']);
    $kd2 = KrsDetail::create(['kode_krs_detail' => 102, 'kode_krs' => $krs2->kode_krs, 'id_matakuliah' => $mk->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 102, 'nilai_akhir' => 75.00, 'grade' => 'B', 'score' => 3.00]);
    KelasMahasiswa::create(['kelas_id' => $kelas->kelas_id, 'kode_krs_detail' => 102]);

    // 3. Mahasiswa 3: Kandidat Null / Fuzzy Match (nama MK mirip di Feeder)
    $mhs3 = TestDataHelper::createMahasiswa(['nim' => 'NIM003', 'nama_mahasiswa' => 'Mahasiswa Fuzzy']);
    $krs3 = Krs::create(['nim' => $mhs3->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => '1']);
    $kd3 = KrsDetail::create(['kode_krs_detail' => 103, 'kode_krs' => $krs3->kode_krs, 'id_matakuliah' => $mk->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 103, 'nilai_akhir' => 90.00, 'grade' => 'A', 'score' => 4.00]);
    KelasMahasiswa::create(['kelas_id' => $kelas->kelas_id, 'kode_krs_detail' => 103]);

    // Response tiruan dari Feeder
    Http::fake([
        'http://127.0.0.1:8082/ws/live2.php' => Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                // NIM001: Nilai sama
                [
                    'nim' => 'NIM001',
                    'nama_mahasiswa' => 'Mahasiswa Cocok',
                    'kode_mata_kuliah' => 'KD102',
                    'nama_mata_kuliah' => 'Fisiologi Dasar',
                    'nilai_angka' => 80.00,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                ],
                // NIM002: Nilai beda (di Feeder 70.00, di SISKA 75.00)
                [
                    'nim' => 'NIM002',
                    'nama_mahasiswa' => 'Mahasiswa Beda',
                    'kode_mata_kuliah' => 'KD102',
                    'nama_mata_kuliah' => 'Fisiologi Dasar',
                    'nilai_angka' => 70.00,
                    'nilai_huruf' => 'B',
                    'nilai_indeks' => 3.00,
                ],
                // NIM003: Kode beda tapi nama mirip "Fisiologi Dasar Kedokteran" (kemiripan >= 60%)
                [
                    'nim' => 'NIM003',
                    'nama_mahasiswa' => 'Mahasiswa Fuzzy',
                    'kode_mata_kuliah' => 'KD102B',
                    'nama_mata_kuliah' => 'Fisiologi Dasar (Kedokteran)',
                    'nilai_angka' => 90.00,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                ],
            ],
        ], 200),
    ]);

    $codeKelas = $kelas->toCode();
    $response = actingAsStaffUser()->getJson("/api/staff/feeder/audit/kelas/{$codeKelas}");

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'data' => [
            'summary' => [
                'total_mahasiswa_siska' => 3,
                'sesuai' => 1,
            ],
        ],
    ]);

    $data = $response->json('data.audit_results');
    $statuses = collect($data)->pluck('status', 'nim');

    expect($statuses['NIM001'])->toBe('SESUAI');
    expect($statuses['NIM002'])->toBe('BERBEDA');
    expect($statuses['NIM003'])->toBe('KANDIDAT_NULL');
});

test('staff can audit student transcript with two-tier matching and retake consolidation', function () {
    $prodi = ProgramStudi::first() ?? TestDataHelper::createProgramStudi();
    $ta = TahunAkademik::first() ?? TestDataHelper::createTahunAkademik();

    $mk1 = createTestMatakuliah([
        'kode_matakuliah' => 'KD201',
        'nama_matakuliah' => 'Biokimia Kedokteran',
    ]);

    $mk2 = createTestMatakuliah([
        'kode_matakuliah' => 'KD202',
        'nama_matakuliah' => 'Histologi Dasar *', // Ada tanda bintang (*) untuk uji Tier-2
    ]);

    $mhs = TestDataHelper::createMahasiswa(['nim' => 'NIM_TRANSKRIP_01', 'nama_mahasiswa' => 'Budi Transkrip']);

    // Kasus Retake: MK1 diambil 2x. Percobaan 1 dapat C (2.0), Percobaan 2 dapat A (4.0).
    // Sistem harus konsolidasi memilih A (4.0).
    $krsSmt1 = Krs::create(['nim' => $mhs->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => '1']);
    $kdRetake1 = KrsDetail::create(['kode_krs_detail' => 201, 'kode_krs' => $krsSmt1->kode_krs, 'id_matakuliah' => $mk1->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 201, 'nilai_akhir' => 60.00, 'grade' => 'C', 'score' => 2.00]);

    $krsSmt2 = Krs::create(['nim' => $mhs->nim, 'kode_tahun_akademik' => $ta->kode_tahun_akademik, 'semester' => '2']);
    $kdRetake2 = KrsDetail::create(['kode_krs_detail' => 202, 'kode_krs' => $krsSmt2->kode_krs, 'id_matakuliah' => $mk1->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 202, 'nilai_akhir' => 88.00, 'grade' => 'A', 'score' => 4.00]);

    // MK2 diambil 1x dapat B+
    $kdMk2 = KrsDetail::create(['kode_krs_detail' => 203, 'kode_krs' => $krsSmt2->kode_krs, 'id_matakuliah' => $mk2->id_matakuliah]);
    KhsDetail::create(['kode_krs_detail' => 203, 'nilai_akhir' => 78.00, 'grade' => 'B+', 'score' => 3.50]);

    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($mhs) {
        $act = $request['act'] ?? '';
        if ($act === 'GetListMahasiswa') {
            return Http::response([
                'error_code' => 0,
                'error_desc' => '',
                'data' => [
                    [
                        'nim' => $mhs->nim,
                        'nama_mahasiswa' => $mhs->nama_mahasiswa,
                        'id_registrasi_mahasiswa' => 'fake-reg-123',
                    ],
                ],
            ], 200);
        }

        return Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                // MK1: Tier-1 Exact Match (harus cocok dengan nilai terbaik 88.00 A)
                [
                    'nim' => $mhs->nim,
                    'kode_mata_kuliah' => 'KD201',
                    'nama_mata_kuliah' => 'Biokimia Kedokteran',
                    'nilai_angka' => 88.00,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                    'sks' => 3,
                ],
                // MK2: Tier-2 Fallback Match (di Feeder namanya "Histologi Dasar", di SISKA "Histologi Dasar *", kodenya beda "KD202-REV")
                [
                    'nim' => $mhs->nim,
                    'kode_mata_kuliah' => 'KD202-REV',
                    'nama_mata_kuliah' => 'Histologi Dasar',
                    'nilai_angka' => 78.00,
                    'nilai_huruf' => 'B+',
                    'nilai_indeks' => 3.50,
                    'sks' => 2,
                ],
            ],
        ], 200);
    });

    $response = actingAsStaffUser()->getJson("/api/staff/feeder/audit/mahasiswa/{$mhs->nim}");

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'data' => [
            'summary' => [
                'total_matakuliah_siska' => 2,
                'sesuai' => 2,
            ],
        ],
    ]);

    $auditResults = $response->json('data.audit_results');
    $mk1Result = collect($auditResults)->firstWhere('kode_matakuliah', 'KD201');
    $mk2Result = collect($auditResults)->firstWhere('kode_matakuliah', 'KD202');

    // Pastikan MK1 ditandai retake dan mengambil nilai terbaik A (88.00)
    expect($mk1Result['is_retake'])->toBeTrue();
    expect($mk1Result['siska']['nilai_angka'])->toEqual(88.0);
    expect($mk1Result['status'])->toBe('SESUAI');

    // Pastikan MK2 cocok via Tier-2 (Nama MK Normalisasi)
    expect($mk2Result['tier_matching'])->toContain('Tier-2');
    expect($mk2Result['status'])->toBe('SESUAI');
});

test('staff can use query parameter fallback when URL contains literal placeholder', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => 'FALLBACK01', 'nama_mahasiswa' => 'Mahasiswa Fallback']);

    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($mhs) {
        $act = $request['act'] ?? '';
        if ($act === 'GetListMahasiswa') {
            return Http::response([
                'error_code' => 0,
                'error_desc' => '',
                'data' => [
                    [
                        'nim' => $mhs->nim,
                        'nama_mahasiswa' => $mhs->nama_mahasiswa,
                        'id_registrasi_mahasiswa' => 'reg-fb-01',
                    ],
                ],
            ], 200);
        }

        return Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                [
                    'id_registrasi_mahasiswa' => 'reg-fb-01',
                    'nim' => $mhs->nim,
                    'nama_mahasiswa' => $mhs->nama_mahasiswa,
                    'kode_mata_kuliah' => 'KD101',
                    'nama_mata_kuliah' => 'Anatomi Umum',
                    'nilai_angka' => 85.00,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                ],
            ],
        ], 200);
    });

    // Endpoint dipanggil dengan literal {nim} di URL dan nim di query param ?nim=FALLBACK01
    $response = actingAsStaffUser()->getJson('/api/staff/feeder/pull/mahasiswa/{nim}?nim=' . $mhs->nim);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => true,
        'data' => [
            'nim' => $mhs->nim,
        ],
    ]);
});

test('feeder pull responses hide internal UUID fields (id_registrasi_mahasiswa, id_mahasiswa, id_kelas_kuliah, id_matkul, id_prodi)', function () {
    $mhs = TestDataHelper::createMahasiswa(['nim' => 'UUIDTEST01', 'nama_mahasiswa' => 'Mahasiswa Test UUID']);

    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($mhs) {
        $act = $request['act'] ?? '';
        if ($act === 'GetListMahasiswa') {
            return Http::response([
                'error_code' => 0,
                'error_desc' => '',
                'data' => [
                    [
                        'nim' => $mhs->nim,
                        'nama_mahasiswa' => $mhs->nama_mahasiswa,
                        'id_registrasi_mahasiswa' => 'bd322a2d-df93-4d90-907d-86f81ddc8f0c',
                    ],
                ],
            ], 200);
        }

        return Http::response([
            'error_code' => 0,
            'error_desc' => '',
            'data' => [
                [
                    'id_prodi' => 'c70d596a-6f2c-425c-892a-0daccdb3695d',
                    'nama_program_studi' => 'S1 Kedokteran',
                    'id_semester' => '20251',
                    'nama_semester' => '2025/2026 Ganjil',
                    'id_matkul' => '40979294-8b3b-4c9e-b369-14228aa0d7b6',
                    'kode_mata_kuliah' => 'KD101',
                    'nama_mata_kuliah' => 'Anatomi Umum',
                    'sks_mata_kuliah' => '3.00',
                    'id_kelas_kuliah' => '1c98e2a2-772a-4b27-81e1-e27b53a87291',
                    'nama_kelas_kuliah' => 'IA',
                    'id_registrasi_mahasiswa' => 'bd322a2d-df93-4d90-907d-86f81ddc8f0c',
                    'id_mahasiswa' => '50c1c4c8-a721-4eb4-859d-ed7a86eb4bc3',
                    'nim' => $mhs->nim,
                    'nama_mahasiswa' => $mhs->nama_mahasiswa,
                    'nilai_angka' => 85.00,
                    'nilai_huruf' => 'A',
                    'nilai_indeks' => 4.00,
                ],
            ],
        ], 200);
    });

    $response = actingAsStaffUser()->getJson('/api/staff/feeder/pull/mahasiswa/' . $mhs->nim);

    $response->assertStatus(200);
    $data = $response->json('data.data');
    expect($data)->toBeArray()->toHaveCount(1);

    $row = $data[0];
    expect($row)->toHaveKeys(['nim', 'nama_mahasiswa', 'kode_mata_kuliah', 'nama_mata_kuliah', 'nilai_angka', 'nilai_huruf', 'nilai_indeks']);

    // Pastikan seluruh field UUID / ID teknis Feeder disembunyikan
    expect($row)->not->toHaveKeys([
        'id_registrasi_mahasiswa',
        'id_mahasiswa',
        'id_kelas_kuliah',
        'id_matkul',
        'id_prodi',
        'id_semester',
    ]);
});

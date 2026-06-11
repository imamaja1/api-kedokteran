# Pengembangan Route Dosen — Fitur Lengkap

## Daftar Isi

- [Latar Belakang](#latar-belakang)
- [Ringkasan File](#ringkasan-file)
- [Fitur 1: Profil](#fitur-1-profil)
- [Fitur 2: Kurikulum](#fitur-2-kurikulum)
- [Fitur 3: Perwalian](#fitur-3-perwalian)
- [Fitur 4: Penilaian Dosen → Kaprodi](#fitur-4-penilaian-dosen--kaprodi)
- [Route File — Hasil Akhir](#route-file--hasil-akhir)
- [Catatan Penting](#catatan-penting)

---

## Latar Belakang

Route dosen dikembangkan menjadi 4 fitur utama: **Profil, Kurikulum, Perwalian, Penilaian dengan Kaprodi Validasi**.

Total akhir: **17 endpoint** (3 auth + 2 profil + 1 data dosen + 3 kurikulum + 6 perwalian + 9 penilaian).

---

## Ringkasan File

| #   | File                                                             | Status    | Keterangan                    |
| --- | ---------------------------------------------------------------- | --------- | ----------------------------- |
| 1   | `database/migrations/..._modify_student_scores_add_tracking.php` | **BARU**  | +4 kolom di student_scores    |
| 2   | `database/migrations/..._create_penilaian_status_table.php`      | **BARU**  | Tabel tracking per-mahasiswa  |
| 3   | `database/migrations/..._add_kaprodi_to_program_studi.php`       | **BARU**  | FK kaprodi                    |
| 4   | `database/migrations/..._create_fakultas_table.php`              | **BARU**  | Untuk dekan                   |
| 5   | `database/migrations/..._add_fakultas_to_program_studi.php`      | **BARU**  | FK fakultas                   |
| 6   | `app/Models/PenilaianStatus.php`                                 | **BARU**  | Model baru                    |
| 7   | `app/Models/Fakultas.php`                                        | **BARU**  | Model baru                    |
| 8   | `app/Models/StudentScore.php`                                    | **UBAH**  | +4 fillable, +2 relasi        |
| 9   | `app/Models/ProgramStudi.php`                                    | **UBAH**  | +2 relasi (kaprodi, fakultas) |
| 10  | `app/Models/Dosen.php`                                           | **UBAH**  | +2 relasi (kaprodi, dekan)    |
| 11  | `app/Models/KhsDetail.php`                                       | **UBAH**  | Fix fillable                  |
| 12  | `app/Http/Middleware/EnsureIsKaprodi.php`                        | **BARU**  | Middleware kaprodi            |
| 13  | `bootstrap/app.php`                                              | **UBAH**  | Register middleware           |
| 14  | `app/Service/ServicePenilaianDosen.php`                          | **BARU**  | 5 methods                     |
| 15  | `app/Service/ServicePenilaianKaprodi.php`                        | **BARU**  | 4 methods                     |
| 16  | `app/Http/Controllers/Api_Dosen/PenilaianDosenController.php`    | **BARU**  | 5 endpoints                   |
| 17  | `app/Http/Controllers/Api_Dosen/PenilaianKaprodiController.php`  | **BARU**  | 4 endpoints                   |
| 18  | `routes/dosen.php`                                               | **UBAH**  | 17 routes                     |
| 19  | `app/Service/ServiceNilaiDosen.php`                              | **HAPUS** | Diganti                       |
| 20  | `app/Http/Controllers/Api_Dosen/NilaiController.php`             | **HAPUS** | Diganti                       |

---

## Fitur 1: Profil

### Endpoint

| Method | Endpoint                    | Controller                      | Fungsi                     |
| ------ | --------------------------- | ------------------------------- | -------------------------- |
| `GET`  | `/api/dosen/me`             | `DosenController@me`            | Profil lengkap dosen login |
| `PUT`  | `/api/dosen/profile/update` | `DosenController@profileUpdate` | Update data diri           |

---

## Fitur 2: Kurikulum

### Endpoint

| Method | Endpoint                      | Controller                           | Fungsi                |
| ------ | ----------------------------- | ------------------------------------ | --------------------- |
| `GET`  | `/api/dosen/kurikulum`        | `KurikulumController@getKurikulum`   | Daftar nama kurikulum |
| `GET`  | `/api/dosen/kurikulum/kelas`  | `KurikulumController@getKelasDosen`  | Kelas yang diajar     |
| `GET`  | `/api/dosen/kurikulum/detail` | `KurikulumController@getDetailKelas` | Detail kelas          |

---

## Fitur 3: Perwalian

### Endpoint

| Method | Endpoint                        | Controller                             | Fungsi                     |
| ------ | ------------------------------- | -------------------------------------- | -------------------------- |
| `GET`  | `/api/dosen/perwalian/jumlah`   | `PerwalianController@jumlahPerwalian`  | Jumlah mahasiswa bimbingan |
| `GET`  | `/api/dosen/perwalian/daftar`   | `PerwalianController@daftarPerwalian`  | Daftar mahasiswa bimbingan |
| `GET`  | `/api/dosen/perwalian/riwayat`  | `PerwalianController@riwayatPerwalian` | Riwayat perwalian          |
| `GET`  | `/api/dosen/perwalian/krs`      | `PerwalianController@showKrsDetail`    | Detail KRS mahasiswa       |
| `POST` | `/api/dosen/perwalian/validasi` | `PerwalianController@validasiKrs`      | Validasi KRS               |
| `POST` | `/api/dosen/perwalian/batal`    | `PerwalianController@batalPerwalian`   | Batalkan perwalian         |

---

## Fitur 4: Penilaian Dosen → Kaprodi

### Prinsip Utama

> **`khs_detail` hanya menyimpan `nilai_akhir`** — tidak ada `nilai_harian/uts/uas`.
> `nilai_akhir` dihitung dari `student_scores` via `ScoreCalculationService`.
> Jadi: **dosen → student_scores → ScoreCalculationService → khs_detail**.

### Status Flow

```
                    ┌──────────────┐
                    │ TIDAK ADA    │ ← belum_input (inferred)
                    │ RECORD       │
                    └──────┬───────┘
                           │ dosen input scores
                           ▼
                    ┌──────────────┐
                    │   PROSES     │ ← penilaian_status + student_scores
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    │              │
              Kaprodi          Kaprodi
             VALIDASI          REVISI
                    │              │
                    ▼              ▼
           ┌──────────────┐ ┌──────────────┐
           │  VALIDASI    │ │   PROSES     │ ← balik ke dosen
           │              │ │ + catatan    │
           └──────┬───────┘ └──────────────┘
                  │
                  │ ScoreCalculationService
                  │ calculateFinalScore()
                  │
                  ▼
           ┌──────────────┐
           │  KHS_DETAIL  │ ← nilai_akhir + tidak_berhak = 'A'
           └──────────────┘
```

### Endpoint

#### Penilaian Dosen (5 endpoints)

| Method | Endpoint                                     | Controller                                       | Fungsi                          |
| ------ | -------------------------------------------- | ------------------------------------------------ | ------------------------------- |
| `GET`  | `/api/dosen/penilaian/kelas`                 | `PenilaianDosenController@getKelasPenilaian`     | Daftar kelas + status ringkas   |
| `GET`  | `/api/dosen/penilaian/mahasiswa?code_kelas=` | `PenilaianDosenController@getMahasiswaPenilaian` | Daftar mahasiswa + status       |
| `GET`  | `/api/dosen/penilaian/template?code_kelas=`  | `PenilaianDosenController@getTemplate`           | Template leaf nodes untuk input |
| `POST` | `/api/dosen/penilaian/input`                 | `PenilaianDosenController@inputNilai`            | Input scores per-node           |
| `PUT`  | `/api/dosen/penilaian/update`                | `PenilaianDosenController@updateNilai`           | Update scores (selama proses)   |

#### Kaprodi (5 endpoints, dengan middleware `kaprodi`)

| Method | Endpoint                                                                | Controller                                           | Fungsi                        |
| ------ | ----------------------------------------------------------------------- | ---------------------------------------------------- | ----------------------------- |
| `GET`  | `/api/dosen/kaprodi/penilaian/kelas`                                    | `PenilaianKaprodiController@getKelasPenilaian`       | Daftar kelas di prodi         |
| `GET`  | `/api/dosen/kaprodi/penilaian/mahasiswa?code_kelas=`                    | `PenilaianKaprodiController@getMahasiswaPenilaian`   | Status per mahasiswa          |
| `GET`  | `/api/dosen/kaprodi/penilaian/detail?code_kelas=...&code_mahasiswa=...` | `PenilaianKaprodiController@getDetailNilaiMahasiswa` | Detail nilai + hitung lengkap |
| `POST` | `/api/dosen/kaprodi/penilaian/validasi`                                 | `PenilaianKaprodiController@validasi`                | Validasi → masuk KHS          |
| `POST` | `/api/dosen/kaprodi/penilaian/revisi`                                   | `PenilaianKaprodiController@revisi`                  | Revisi → balik dosen          |

### Database Changes

#### Migration 1: Modifikasi `student_scores`

```php
Schema::table('student_scores', function (Blueprint $table) {
    $table->unsignedBigInteger('dosen_kode_dosen')->nullable()->after('assessor_id');
    $table->enum('status', ['proses', 'validasi'])->default('proses')->after('dosen_kode_dosen');
    $table->unsignedBigInteger('validated_by')->nullable()->after('status');
    $table->timestamp('validated_at')->nullable()->after('validated_by');

    $table->foreign('dosen_kode_dosen')->references('kode_dosen')->on('dosen')->nullOnDelete();
    $table->foreign('validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();
});
```

#### Migration 2: Tabel baru `penilaian_status`

```php
Schema::create('penilaian_status', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('kelas_id');
    $table->char('nim', 12);
    $table->uuid('template_id');
    $table->enum('status', ['proses', 'validasi'])->default('proses');
    $table->unsignedBigInteger('dosen_input_by');
    $table->unsignedBigInteger('kaprodi_validated_by')->nullable();
    $table->timestamp('validated_at')->nullable();
    $table->text('catatan_dosen')->nullable();
    $table->text('catatan_kaprodi')->nullable();
    $table->timestamps();

    $table->foreign('kelas_id')->references('kelas_id')->on('kelas')->cascadeOnDelete();
    $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();
    $table->foreign('template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
    $table->foreign('dosen_input_by')->references('kode_dosen')->on('dosen')->cascadeOnDelete();
    $table->foreign('kaprodi_validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();

    $table->unique(['kelas_id', 'nim'], 'uq_penilaian_kelas_nim');
});
```

#### Migration 3: Tambah `kode_dosen_kaprodi` ke `program_studi`

```php
Schema::table('program_studi', function (Blueprint $table) {
    $table->unsignedBigInteger('kode_dosen_kaprodi')->nullable()->after('kode_program_studi');
    $table->foreign('kode_dosen_kaprodi')->references('kode_dosen')->on('dosen')->nullOnDelete();
});
```

#### Migration 4: Tabel `fakultas`

```php
Schema::create('fakultas', function (Blueprint $table) {
    $table->smallIncrements('kode_fakultas');
    $table->string('nama_fakultas', 100);
    $table->unsignedBigInteger('kode_dosen_dekan')->nullable();
    $table->timestamps();

    $table->foreign('kode_dosen_dekan')->references('kode_dosen')->on('dosen')->nullOnDelete();
});
```

#### Migration 5: Tambah `kode_fakultas` ke `program_studi`

```php
Schema::table('program_studi', function (Blueprint $table) {
    $table->unsignedSmallInteger('kode_fakultas')->nullable()->after('singkatan_program_studi');
    $table->foreign('kode_fakultas')->references('kode_fakultas')->on('fakultas')->nullOnDelete();
});
```

### Model Changes

#### NEW: `PenilaianStatus`

```php
class PenilaianStatus extends Model
{
    protected $table = 'penilaian_status';
    protected $fillable = [
        'kelas_id', 'nim', 'template_id', 'status',
        'dosen_input_by', 'kaprodi_validated_by', 'validated_at',
        'catatan_dosen', 'catatan_kaprodi',
    ];
    protected $casts = ['validated_at' => 'datetime'];

    public function kelas() { return $this->belongsTo(Kelas::class); }
    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class, 'nim', 'nim'); }
    public function template() { return $this->belongsTo(AssessmentTemplate::class, 'template_id'); }
    public function dosenInput() { return $this->belongsTo(Dosen::class, 'dosen_input_by', 'kode_dosen'); }
    public function kaprodiValidator() { return $this->belongsTo(Dosen::class, 'kaprodi_validated_by', 'kode_dosen'); }
}
```

#### NEW: `Fakultas`

```php
class Fakultas extends Model
{
    use HasCode;
    protected $table = 'fakultas';
    protected $primaryKey = 'kode_fakultas';
    protected $fillable = ['nama_fakultas', 'kode_dosen_dekan'];

    public function dekan() { return $this->belongsTo(Dosen::class, 'kode_dosen_dekan', 'kode_dosen'); }
    public function programStudi() { return $this->hasMany(ProgramStudi::class, 'kode_fakultas'); }
}
```

#### UBAH: `StudentScore`

```php
protected $fillable = [
    'template_id', 'nim', 'node_key', 'score', 'assessor_id', 'notes',
    'dosen_kode_dosen', 'status', 'validated_by', 'validated_at',
];

public function dosenInput()
{
    return $this->belongsTo(Dosen::class, 'dosen_kode_dosen', 'kode_dosen');
}

public function kaprodiValidator()
{
    return $this->belongsTo(Dosen::class, 'validated_by', 'kode_dosen');
}
```

#### UBAH: `ProgramStudi`

```php
public function kaprodi()
{
    return $this->belongsTo(Dosen::class, 'kode_dosen_kaprodi', 'kode_dosen');
}

public function fakultas()
{
    return $this->belongsTo(Fakultas::class, 'kode_fakultas', 'kode_fakultas');
}
```

#### UBAH: `Dosen`

```php
public function kaprodiProgramStudi()
{
    return $this->hasOne(ProgramStudi::class, 'kode_dosen_kaprodi', 'kode_dosen');
}

public function dekanFakultas()
{
    return $this->hasOne(Fakultas::class, 'kode_dosen_dekan', 'kode_dosen');
}
```

#### UBAH: `KhsDetail`

```php
// Hanya kolom yang ADA di DB:
protected $fillable = [
    'kode_krs_detail', 'nilai_akhir', 'tidak_berhak',
];
```

### Middleware: `EnsureIsKaprodi`

```php
class EnsureIsKaprodi
{
    public function handle(Request $request, Closure $next)
    {
        $dosen = Auth::guard('dosen_web')->user();

        if (! $dosen) {
            return ApiResponse::unauthorized();
        }

        $isKaprodi = ProgramStudi::where('kode_dosen_kaprodi', $dosen->kode_dosen)
            ->exists();

        if (! $isKaprodi) {
            return ApiResponse::error('Anda tidak memiliki akses sebagai Kaprodi.', 403);
        }

        return $next($request);
    }
}
```

Registrasi di `bootstrap/app.php`:

```php
$middleware->alias([
    'kaprodi' => EnsureIsKaprodi::class,
    // ... existing aliases
]);
```

### Service: `ServicePenilaianDosen`

| Method                                          | Fungsi                        |
| ----------------------------------------------- | ----------------------------- |
| `getKelasPenilaian($kodeDosen)`                 | Daftar kelas + jumlah input   |
| `getMahasiswaPenilaian($codeKelas, $kodeDosen)` | Daftar mahasiswa + status     |
| `getTemplateForKelas($codeKelas, $kodeDosen)`   | Template tree untuk input     |
| `inputNilai($payload, $kodeDosen)`              | Input scores per-node         |
| `updateNilai($payload, $kodeDosen)`             | Update scores (selama proses) |

### Service: `ServicePenilaianKaprodi`

| Method                                                            | Fungsi                |
| ----------------------------------------------------------------- | --------------------- |
| `getKelasPenilaian($kodeDosen)`                                   | Daftar kelas di prodi |
| `getMahasiswaPenilaian($codeKelas, $kodeDosen)`                   | Status per mahasiswa  |
| `getDetailNilaiMahasiswa($codeKelas, $codeMahasiswa, $kodeDosen)` | Detail nilai per node |
| `validasiPenilaian(...)`                                          | Validasi → masuk KHS  |
| `revisiPenilaian(...)`                                            | Revisi → balik dosen  |

### Alur `validasiPenilaian()` Detail

```
1. Decode code_kelas → Kelas
2. Decode code_mahasiswa → Mahasiswa
3. Cek: ProgramStudi::where('kode_dosen_kaprodi', kodeDosen)
        ->where('kode_program_studi', kelas->kode_program_studi) exists?
4. Cek: PenilaianStatus::where('kelas_id', kelas_id)
        ->where('nim', nim)->where('status', 'proses') exists?
5. Update penilaian_status → status='validasi', kaprodi_validated_by, validated_at
6. Update student_scores → status='validasi' untuk semua node (template_id + nim)
7. Hitung: ScoreCalculationService::calculateFinalScore(template, nim)
   → recursive: leaf_scores × weight = parent_score → ... → root = nilai_akhir
8. Cari KrsDetail via:
   KelasMahasiswa::where('kelas_id', kelas_id)
       ->whereHas('krsDetail.krs', fn($q) => $q->where('nim', nim))
       ->first()->krsDetail
9. Tulis KhsDetail::updateOrCreate(
       ['kode_krs_detail' => krsDetail->kode_krs_detail],
       ['nilai_akhir' => calculated_value, 'tidak_berhak' => 'A']
   )
```

---

## Route File — Hasil Akhir

File: `routes/dosen.php`

```php
<?php

use App\Http\Controllers\Api_Dosen\KurikulumController;
use App\Http\Controllers\Api_Dosen\PenilaianDosenController;
use App\Http\Controllers\Api_Dosen\PenilaianKaprodiController;
use App\Http\Controllers\Api_Dosen\PerwalianController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DosenController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware(['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {

        // Auth
        Route::post('/dosen/logout', [AuthController::class, 'logout']);

        // Profil (2 endpoints)
        Route::get('/dosen/me', [DosenController::class, 'me']);
        Route::put('/dosen/profile/update', [DosenController::class, 'profileUpdate']);

        // Data Dosen (3 endpoints)
        Route::get('/dosen', [DosenController::class, 'index']);
        Route::get('/dosen/detail', [DosenController::class, 'show']);
        Route::put('/dosen', [DosenController::class, 'update']);

        // Kurikulum (3 endpoints)
        Route::get('/dosen/kurikulum', [KurikulumController::class, 'getKurikulum']);
        Route::get('/dosen/kurikulum/kelas', [KurikulumController::class, 'getKelasDosen']);
        Route::get('/dosen/kurikulum/detail', [KurikulumController::class, 'getDetailKelas']);

        // Perwalian (6 endpoints)
        Route::get('/dosen/perwalian/jumlah', [PerwalianController::class, 'jumlahPerwalian']);
        Route::get('/dosen/perwalian/daftar', [PerwalianController::class, 'daftarPerwalian']);
        Route::get('/dosen/perwalian/riwayat', [PerwalianController::class, 'riwayatPerwalian']);
        Route::get('/dosen/perwalian/krs', [PerwalianController::class, 'showKrsDetail']);
        Route::post('/dosen/perwalian/validasi', [PerwalianController::class, 'validasiKrs']);
        Route::post('/dosen/perwalian/batal', [PerwalianController::class, 'batalPerwalian']);

        // Penilaian Dosen (5 endpoints)
        Route::prefix('/dosen/penilaian')->group(function () {
            Route::get('/kelas', [PenilaianDosenController::class, 'getKelasPenilaian']);
            Route::get('/mahasiswa', [PenilaianDosenController::class, 'getMahasiswaPenilaian']);
            Route::get('/template', [PenilaianDosenController::class, 'getTemplate']);
            Route::post('/input', [PenilaianDosenController::class, 'inputNilai']);
            Route::put('/update', [PenilaianDosenController::class, 'updateNilai']);
        });

        // Kaprodi (5 endpoints, dengan middleware kaprodi)
        Route::prefix('/dosen/kaprodi')
            ->middleware('kaprodi')
            ->group(function () {
                Route::get('/penilaian/kelas', [PenilaianKaprodiController::class, 'getKelasPenilaian']);
                Route::get('/penilaian/mahasiswa', [PenilaianKaprodiController::class, 'getMahasiswaPenilaian']);
                Route::get('/penilaian/detail', [PenilaianKaprodiController::class, 'getDetailNilaiMahasiswa']);
                Route::post('/penilaian/validasi', [PenilaianKaprodiController::class, 'validasi']);
                Route::post('/penilaian/revisi', [PenilaianKaprodiController::class, 'revisi']);
            });

        // Fallback
        Route::fallback(fn() => response()->json([
            'status'  => false,
            'message' => 'Endpoint tidak ditemukan.',
            'error'   => 'NOT_FOUND',
        ], 404));
    });
```

---

## Catatan Penting

### 1. `khs_detail` Hanya Menyimpan `nilai_akhir`

Schema DB: `kode_khs_detail, kode_krs_detail, nilai_akhir, tidak_berhak, timestamps`.
TIDAK ADA `nilai_harian`, `nilai_uts`, `nilai_uas`.

### 2. `assessor_id` → `users.id`

FK `student_scores.assessor_id` mengacu ke `users.id` (tabel admin/staff). Dosen menggunakan kolom baru `dosen_kode_dosen` yang mengacu ke `dosen.kode_dosen`.

### 3. Template System Sudah Ada

`AssessmentTemplate` → `AssessmentNodeIndex` → `StudentScore` → `ScoreCalculationService`. Semua infrastructure sudah ada dan digunakan oleh flow penilaian.

### 4. Middleware `kaprodi`

Kaprodi = dosen yang `kode_dosen`-nya tercatat di `program_studi.kode_dosen_kaprodi`. Bukan role di tabel users, tapi relasi di program_studi.

### 5. Revisi Kaprodi

Ketika kaprodi merevisi, status kembali ke `proses`, `khs_detail` dihapus, dan dosen bisa input ulang. Catatan kaprodi tersimpan untuk referensi dosen.

---

## Ringkasan Akhir

| Fitur           | Endpoint | File Baru                                                                  | File Diubah                                                                                                   | File Dihapus                                   |
| --------------- | -------- | -------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| Profil          | 2        | -                                                                          | `DosenController.php`                                                                                         | -                                              |
| Data Dosen      | 1        | -                                                                          | `DosenController.php`                                                                                         | -                                              |
| Kurikulum       | 3        | -                                                                          | `KurikulumController.php`                                                                                     | -                                              |
| Perwalian       | 6        | -                                                                          | `PerwalianController.php`, `ServicePerwalian.php`                                                             | -                                              |
| Penilaian Dosen | 5        | `PenilaianDosenController.php`, `ServicePenilaianDosen.php`                | -                                                                                                             | `NilaiController.php`, `ServiceNilaiDosen.php` |
| Kaprodi         | 5        | `PenilaianKaprodiController.php`, `ServicePenilaianKaprodi.php`            | -                                                                                                             | -                                              |
| Infra           | -        | `PenilaianStatus.php`, `Fakultas.php`, `EnsureIsKaprodi.php`, 5 migrations | `StudentScore.php`, `ProgramStudi.php`, `Dosen.php`, `KhsDetail.php`, `routes/dosen.php`, `bootstrap/app.php` | -                                              |
| **Total**       | **22**   | **10 file**                                                                | **8 file**                                                                                                    | **2 file**                                     |

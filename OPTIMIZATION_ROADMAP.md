# 🚀 OPTIMIZATION ROADMAP — API_SISKA_KEDOKTERAN

> Panduan implementasi optimasi untuk meningkatkan performa, kualitas code, dan scalability sistem.

---

## 📊 PRIORITAS OPTIMASI

| No  | Area                                  | Prioritas   | Impact      | Effort      | Timeline |
| --- | ------------------------------------- | ----------- | ----------- | ----------- | -------- |
| 1   | **Database Query Optimization (N+1)** | 🔴 CRITICAL | 🟢🟢🟢 High | 🟡🟡 Medium | 3-5 hari |
| 2   | **Database Indexing**                 | 🔴 CRITICAL | 🟢🟢 Medium | 🟢 Low      | 1-2 hari |
| 3   | **Redis Caching Strategy**            | 🟠 HIGH     | 🟢🟢🟢 High | 🟡🟡🟡 High | 4-6 hari |
| 4   | **Code Cleanup & Standardization**    | 🟠 HIGH     | 🟢 Low      | 🟢 Low      | 2-3 hari |
| 5   | **API Response Optimization**         | 🟡 MEDIUM   | 🟢🟢 Medium | 🟢 Low      | 2-3 hari |
| 6   | **Database Schema Optimization**      | 🟡 MEDIUM   | 🟢 Low      | 🟡 Medium   | 2-3 hari |
| 7   | **Business Logic Refactoring**        | 🟡 MEDIUM   | 🟢 Low      | 🟡 Medium   | 2-3 hari |

---

## 1. 🔴 DATABASE QUERY OPTIMIZATION (N+1 PROBLEMS)

**Current Issue:** ServiceKRS, ServiceKHS, ServiceKurikulum menggunakan multiple join + map loop → banyak query terpisah.

### Problem Code

```php
// ❌ BAD: Multiple queries dalam loop
$data['krs'] = Krs::join('tahun_akademik', ...)
    ->join('krs_detail', ...)
    ->join('matakuliah', ...)
    ->get()
    ->map(function ($item) {  // ← Setiap map → 1+ query tambahan!
        $item->kode = Crypt::encryptString($item->kode);
        return $item;
    });
```

### Optimization Solution

```php
// ✅ GOOD: Eager loading + minimal queries
$krs = Krs::where('nim', $nim)
    ->where('semester', $semester)
    ->with([
        'tahunAkademik',
        'krsDetail' => function ($q) {
            $q->select('kode_krs_detail', 'kode_krs', 'id_matakuliah')
              ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
        }
    ])
    ->first();

// Format di controller, jangan di query
$data['krs'] = $krs->krsDetail->map(function ($detail, $idx) {
    return [
        'id' => $idx + 1,
        'kode' => Crypt::encryptString($detail->kode_krs_detail),
        'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
        'sks_teori' => $detail->matakuliah->sks_teori,
        'sks_praktik' => $detail->matakuliah->sks_praktik,
        'block' => $detail->matakuliah->block,
    ];
});
```

### Performance Impact

| Metrik        | Before        | After       | Improvement     |
| ------------- | ------------- | ----------- | --------------- |
| Query Count   | 15-20 queries | 2-3 queries | **85% ↓**       |
| Response Time | 800-1200ms    | 150-250ms   | **70% ↓**       |
| Database Load | High          | Low         | **Significant** |

### Implementation Checklist

- [ ] Optimize `ServiceKRS::getKRSMhs()` — use eager loading
- [ ] Optimize `ServiceKHS::getKHSMhs()` — use eager loading
- [ ] Optimize `ServiceKurikulum::buildKurikulumData()` — cache result
- [ ] Optimize `ServiceMahasiswa::getAllMahasiswa()` — select only needed columns
- [ ] Optimize `ServicePetikanNilai::petikan_nilai_by_nim()` — reduce joins

---

## 2. 🔴 DATABASE INDEXING

**Current Issue:** Kolom filter sering tidak ada index → full table scan pada dataset besar.

### Migrations to Add

```php
// database/migrations/YYYY_MM_DD_add_indexes.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // mahasiswa table
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->index('nim');  // Primary search
            $table->index('program_studi_kode');  // Filter by prodi
            $table->index('email');  // Login alternative
            $table->fullText('nama_mahasiswa');  // Full-text search
        });

        // krs table
        Schema::table('krs', function (Blueprint $table) {
            $table->index('nim');  // Join mahasiswa
            $table->index('kode_tahun_akademik');  // Filter by TA
            $table->index(['nim', 'semester']);  // Composite for common query
        });

        // krs_detail table
        Schema::table('krs_detail', function (Blueprint $table) {
            $table->index('kode_krs');  // Join krs
            $table->index('id_matakuliah');  // Join matakuliah
        });

        // khs_detail table
        Schema::table('khs_detail', function (Blueprint $table) {
            $table->index('kode_krs_detail');  // Join krs_detail
        });

        // matakuliah table
        Schema::table('matakuliah', function (Blueprint $table) {
            $table->index('kode_program_studi');  // Filter by prodi
            $table->fullText('nama_matakuliah');  // Search by name
        });

        // dosen table
        Schema::table('dosen', function (Blueprint $table) {
            $table->index('kode_dosen');  // Primary search
            $table->index('alamat_email');  // Login alternative
            $table->index('homebase');  // Filter by prodi
        });

        // tahun_akademik table
        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->index('kode_tahun_akademik');  // Primary search
            $table->index('tahun_akademik');  // Filter by year
        });
    }

    public function down()
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropIndex('mahasiswa_nim_index');
            $table->dropIndex('mahasiswa_program_studi_kode_index');
            $table->dropIndex('mahasiswa_email_index');
            $table->dropFullText('mahasiswa_nama_mahasiswa_fulltext');
        });

        // ... repeat for other tables
    }
};
```

### Query Improvement Examples

```php
// ❌ BEFORE (full table scan)
$mahasiswa = Mahasiswa::where('nama_mahasiswa', 'like', "%Budi%")->get();
// Full scan semua 100K+ rows!

// ✅ AFTER (full-text search)
$mahasiswa = Mahasiswa::whereFullText('nama_mahasiswa', 'Budi')->get();
// Index search: 10-20ms vs 2000ms

// ✅ COMPOSITE INDEX
$krs = Krs::where('nim', '2401001')
    ->where('semester', 3)
    ->get();
// Uses index: ['nim', 'semester']
```

### Implementation Checklist

- [ ] Create migration file `add_indexes.php`
- [ ] Run `php artisan migrate`
- [ ] Test query performance with EXPLAIN
- [ ] Monitor slow query log

---

## 3. 🟠 REDIS CACHING STRATEGY

**Target:** Cache data yang jarang berubah (kurikulum, program studi, matakuliah) → response cepat.

### Setup Redis

```bash
composer require laravel/cache
```

### Caching Implementation

```php
// app/Service/ServiceKurikulum.php
<?php

namespace App\Service;

use App\Models\Kurikulum;
use Illuminate\Support\Facades\Cache;

class ServiceKurikulum
{
    private const CACHE_TTL = 3600; // 1 jam

    public function buildKurikulumData($kode_nama_kurikulum)
    {
        $cacheKey = "kurikulum::{$kode_nama_kurikulum}";

        // ✅ Return cached data jika ada
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kode_nama_kurikulum) {
            return Kurikulum::where('kode_nama_kurikulum', $kode_nama_kurikulum)
                ->with('matakuliah:id_matakuliah,nama_matakuliah,sks_teori,sks_praktik')
                ->orderBy('semester')
                ->get()
                ->groupBy('semester')
                ->map(function ($group) {
                    return [
                        'total_sks' => $group->sum(fn($k) => $k->matakuliah->sks_teori + $k->matakuliah->sks_praktik),
                        'matakuliah' => $group->map(fn($k) => [
                            'nama' => $k->matakuliah->nama_matakuliah,
                            'sks_teori' => $k->matakuliah->sks_teori,
                            'sks_praktik' => $k->matakuliah->sks_praktik,
                        ])->toArray(),
                    ];
                })
                ->toArray();
        });
    }

    // ✅ Invalidate cache saat update
    public function invalidateKurikulumCache($kode_nama_kurikulum)
    {
        Cache::forget("kurikulum::{$kode_nama_kurikulum}");
    }
}
```

### Cache Strategy Table

| Data           | TTL    | Invalidate When                |
| -------------- | ------ | ------------------------------ |
| Kurikulum      | 1 jam  | Admin update kurikulum         |
| Program Studi  | 6 jam  | Admin update prodi             |
| Matakuliah     | 6 jam  | Admin update matakuliah        |
| Tahun Akademik | 24 jam | Admin buat tahun akademik baru |
| Dosen          | 12 jam | Admin update data dosen        |

### Implementation Checklist

- [ ] Configure Redis in `.env` (`CACHE_DRIVER=redis`)
- [ ] Create cache invalidation events di Admin controllers
- [ ] Add `Cache::forget()` di update methods
- [ ] Test cache hits with Laravel Debugbar
- [ ] Monitor Redis memory usage

---

## 4. 🟠 CODE CLEANUP & STANDARDIZATION

### 4.1 Remove Debug Code

```php
// ❌ BEFORE: app/Http/Controllers/Auth/AuthController.php (baris 88)
if (! $user || ! Hash::check($password, $user->sandi_pengguna)) {
    echo json_encode($user);  // ← DEBUG CODE!
    die;  // ← BLOCKS EXECUTION!
    return response()->json([...], 401);
}

// ✅ AFTER: Hapus debug code
if (! $user || ! Hash::check($password, $user->sandi_pengguna)) {
    return response()->json([
        'status' => false,
        'message' => 'Kode Dosen atau password salah.',
    ], 401);
}
```

### 4.2 Consolidate Middleware

```php
// ❌ BEFORE: 3 middleware yang redundan
// EnsureIsAdmin, EnsureIsStaff, EnsureRole

// ✅ AFTER: Single middleware untuk semua role
// app/Http/Middleware/EnsureRole.php
public function handle($request, $next, ...$roles)
{
    if (! auth()->check() || ! in_array(auth()->user()->role, $roles)) {
        abort(403, 'Unauthorized');
    }
    return $next($request);
}

// Usage di routes:
Route::post('/delete', [AdminController::class, 'delete'])
    ->middleware('role:admin,staff');
```

### 4.3 Standardize API Responses

```php
// app/Http/Responses/ApiResponse.php
<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function paginated($paginator, $message = 'Success')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}

// Usage:
return ApiResponse::success($user, 'Login berhasil');
return ApiResponse::error('User tidak ditemukan', 404);
return ApiResponse::paginated($mahasiswa, 'Data mahasiswa');
```

### Implementation Checklist

- [ ] Remove debug code dari AuthController
- [ ] Merge 3 middleware menjadi 1 `EnsureRole`
- [ ] Create `ApiResponse` helper class
- [ ] Update semua controllers untuk use `ApiResponse`
- [ ] Run tests untuk pastikan functionality tidak berubah

---

## 5. 🟡 API RESPONSE OPTIMIZATION

### 5.1 Selective Field Projection

```php
// ❌ BEFORE: Return all columns
$mahasiswa = Mahasiswa::paginate(20);

// ✅ AFTER: Select only needed fields
$mahasiswa = Mahasiswa::select([
    'nim', 'nama_mahasiswa', 'email', 'program_studi_kode'
])->paginate(20);

// Response size: 2MB → 200KB (10x smaller!)
```

### 5.2 Pagination untuk Endpoint Besar

```php
// ❌ BEFORE: Return all data
public function index() {
    $mahasiswa = Mahasiswa::all();  // Bisa 100K+ records!
    return response()->json($mahasiswa);
}

// ✅ AFTER: Mandatory pagination
public function index(Request $request) {
    $perPage = $request->query('per_page', 20);
    $mahasiswa = Mahasiswa::paginate(min($perPage, 100));  // Max 100

    return ApiResponse::paginated($mahasiswa, 'Data mahasiswa');
}
```

### 5.3 Response Time Tracking

```php
// app/Http/Middleware/TrackResponseTime.php
public function handle($request, $next)
{
    $start = microtime(true);
    $response = $next($request);
    $duration = round((microtime(true) - $start) * 1000, 2);

    $response->header('X-Response-Time-Ms', $duration);

    // Log slow requests
    if ($duration > 500) {
        logger()->warning("Slow request: {$request->path()} took {$duration}ms");
    }

    return $response;
}
```

### Implementation Checklist

- [ ] Audit semua endpoints yang return full rows
- [ ] Tambah pagination di `/api/staff/mahasiswa`
- [ ] Tambah pagination di `/api/mhs/kurikulum` (per semester)
- [ ] Select only needed columns di queries
- [ ] Add response time tracking middleware

---

## 6. 🟡 DATABASE SCHEMA OPTIMIZATION

### 6.1 Remove Redundant Columns

```php
// database/migrations/YYYY_MM_DD_clean_redundant_columns.php
public function up()
{
    // ❌ krs_detail.kode_matakuliah redundant (exists in relation)
    Schema::table('krs_detail', function (Blueprint $table) {
        $table->dropColumn('kode_matakuliah');
    });

    // ✅ Verify via relationship:
    // $krsDetail->matakuliah->kode_matakuliah works fine
}
```

### 6.2 Add Timestamps & Soft Deletes

```php
// database/migrations/YYYY_MM_DD_add_timestamps_to_tables.php
public function up()
{
    $tables = ['mahasiswa', 'dosen', 'krs', 'krs_detail', 'khs_detail'];

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $table) {
            $table->timestamps();  // created_at, updated_at
            $table->softDeletes();  // deleted_at
        });
    }
}
```

### 6.3 Add Audit Column

```php
// database/migrations/YYYY_MM_DD_add_audit_columns.php
public function up()
{
    Schema::table('mahasiswa', function (Blueprint $table) {
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->text('change_log')->nullable();  // JSON history
    });
}
```

### Implementation Checklist

- [ ] Backup database sebelum migration
- [ ] Remove `kode_matakuliah` dari `krs_detail`
- [ ] Add timestamps ke semua tabel penting
- [ ] Add `created_by`, `updated_by` untuk audit
- [ ] Update Models dengan trait `Timestamps`, `SoftDeletes`

---

## 7. 🟡 BUSINESS LOGIC REFACTORING

### 7.1 Simplify Angkatan Logic

```php
// ❌ BEFORE: Hardcoded substr logic scattered everywhere
$angkatan = substr($nim, 0, 2);

// ✅ AFTER: Centralized accessor di Model
// app/Models/Mahasiswa.php
public function getAngkatanAttribute()
{
    return (int) substr($this->nim, 0, 2);
}

// Usage: $mahasiswa->angkatan (automatic!)
```

### 7.2 Move Complex Logic to Service

```php
// ❌ BEFORE: Logic dalam loop di Controller
foreach ($students as $student) {
    $kurikulum = /* complex query */;
    $nilai = /* another query */;
    // ... 10 more lines of logic
}

// ✅ AFTER: Dedicated Service
class StudentAcademicService
{
    public function getStudentAcademicProfile($nim)
    {
        return [
            'kurikulum' => $this->getKurikulum($nim),
            'nilai' => $this->getNilai($nim),
            'progress' => $this->calculateProgress($nim),
        ];
    }

    private function getKurikulum($nim) { /* ... */ }
    private function getNilai($nim) { /* ... */ }
    private function calculateProgress($nim) { /* ... */ }
}

// Controller jadi simple:
return ApiResponse::success(
    $this->academicService->getStudentAcademicProfile($nim)
);
```

### 7.3 Implement Result Caching for Expensive Operations

```php
// app/Service/ServicePetikanNilai.php
public function petikan_nilai_by_nim($nim, $kode_prodi)
{
    $cacheKey = "petikan::{$nim}";

    return Cache::remember($cacheKey, 86400, function () use ($nim, $kode_prodi) {
        // Expensive operation dengan 8+ joins
        $data = $this->queryPetikanNilai($nim, $kode_prodi);

        return ApiResponse::success($data, 'Petikan nilai');
    });
}

// Invalidate saat nilai update
public function invalidatePetikanCache($nim)
{
    Cache::forget("petikan::{$nim}");
}
```

### Implementation Checklist

- [ ] Move angkatan logic ke Model accessor
- [ ] Extract complex loops ke dedicated Services
- [ ] Implement Result caching untuk expensive operations
- [ ] Add cache invalidation events
- [ ] Test cache hits dengan Debugbar

---

## 📋 IMPLEMENTATION TIMELINE

### Week 1: Foundation (Database)

- [ ] Add database indexes
- [ ] Create migration untuk timestamps
- [ ] Run performance benchmarks

### Week 2: Query Optimization

- [ ] Optimize ServiceKRS
- [ ] Optimize ServiceKHS
- [ ] Optimize ServiceKurikulum
- [ ] Test dengan real data

### Week 3: Caching & Code Cleanup

- [ ] Setup Redis caching
- [ ] Remove debug code
- [ ] Consolidate middleware
- [ ] Standardize API responses

### Week 4: Refinement

- [ ] Add pagination ke remaining endpoints
- [ ] Schema cleanup (remove redundant columns)
- [ ] Business logic refactoring
- [ ] Final testing & monitoring

---

## 🎯 EXPECTED IMPROVEMENTS

| Metrik                        | Before     | After     | Gain         |
| ----------------------------- | ---------- | --------- | ------------ |
| **Average Response Time**     | 800-1200ms | 150-300ms | **70-80% ↓** |
| **Database Queries/Request**  | 15-20      | 2-3       | **85-90% ↓** |
| **Average Response Size**     | 2-5MB      | 200-500KB | **75-90% ↓** |
| **Concurrent Users Capacity** | 50         | 500+      | **10x ↑**    |
| **Database CPU Usage**        | 70-80%     | 10-20%    | **70% ↓**    |
| **Memory Usage**              | 512MB      | 128MB     | **75% ↓**    |

---

## 🔍 MONITORING & VALIDATION

### Tools to Use

```bash
# 1. Laravel Debugbar (development)
composer require barryvdh/laravel-debugbar --dev

# 2. Laravel Telescope (production monitoring)
composer require laravel/telescope

# 3. Query logging
php artisan tinker
>>> DB::listen(function($query) { logger()->info($query->sql); });

# 4. Slow query log
# Set in MySQL: set global slow_query_log = 'ON';
# set global long_query_time = 1;
```

### KPI to Track

- [ ] Average response time per endpoint
- [ ] Database queries per request
- [ ] Cache hit rate
- [ ] Peak concurrent users
- [ ] Error rate (5xx responses)

---

## ✅ SUCCESS CRITERIA

- [ ] All queries: < 50ms (at 1K records)
- [ ] Response time: < 300ms at p99
- [ ] Database CPU: < 30% at peak load
- [ ] Cache hit rate: > 80% for static data
- [ ] Response size: < 500KB avg
- [ ] Zero N+1 query problems
- [ ] All unit tests passing
- [ ] Load test: 500+ concurrent users

---

## 📞 QUESTIONS?

Untuk detail lebih lanjut tentang implementasi spesifik area manapun, lihat:

- **Query Optimization:** [analisis.md](analisis.md) Section 14.1
- **Security Considerations:** [analisis.md](analisis.md) Section 13
- **Architecture Overview:** [base.md](base.md)

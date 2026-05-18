# RIWAYAT PERUBAHAN SISTEM

---

## 2026-04-27 — Perbaikan Infrastruktur, Audit Trail, dan Konsolidasi Middleware

### 1. Konfigurasi Domain Production (Sanctum & Session)

**Masalah sebelumnya:**
Sistem hanya dikonfigurasi untuk `localhost` sebagai default hardcoded. Tidak ada panduan untuk deploy ke server production, sehingga autentikasi cookie akan gagal jika langsung di-deploy tanpa perubahan konfigurasi.

**File yang diubah:**

#### `config/sanctum.php`
- Ditambahkan blok komentar panduan konfigurasi production lengkap di bagian `stateful` domain.
- Menjelaskan 3 variabel wajib diset di `.env` saat production:
  - `SANCTUM_STATEFUL_DOMAINS` — domain frontend yang boleh akses cookie
  - `SESSION_DOMAIN` — prefix `.ubg.ac.id` agar cookie berlaku semua subdomain
  - `SESSION_SECURE_COOKIE=true` — wajib untuk HTTPS

#### `.env`
- Ditambahkan variabel `SESSION_SECURE_COOKIE=false` (lokal) dengan komentar ganti `true` untuk production.
- Ditambahkan komentar pada `SESSION_DOMAIN` menjelaskan nilai production-nya (`.ubg.ac.id`).
- Ditambahkan komentar pada `APP_URL` menjelaskan nilai production-nya.

**Cara pakai saat deploy ke production:**
```env
APP_URL=https://api-kedokteran.ubg.ac.id
APP_ENV=production
APP_DEBUG=false
SESSION_DOMAIN=.ubg.ac.id
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=siska.ubg.ac.id,api-kedokteran.ubg.ac.id
CORS_ALLOWED_ORIGINS=https://siska.ubg.ac.id,https://api-kedokteran.ubg.ac.id
```

---

### 2. Audit Trail — Activity Log

**Masalah sebelumnya:**
Tidak ada catatan aktivitas pengguna. Tidak bisa tahu siapa login kapan, endpoint apa yang diakses, dari IP mana, dan berapa response code-nya.

**File yang dibuat:**

#### `database/migrations/2026_04_27_000000_create_activity_logs_table.php`
Migrasi baru membuat tabel `activity_logs` dengan kolom:
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint auto | Primary key |
| `guard` | varchar(30) | Guard yang aktif: `mahasiswa_web`, `dosen_web`, `staff_web` |
| `user_id` | varchar(50) | NIM / kode_dosen / user.id |
| `user_type` | varchar(20) | `mahasiswa`, `dosen`, atau `staff` |
| `method` | varchar(10) | HTTP method: GET, POST, PUT, DELETE |
| `path` | varchar(255) | Path endpoint, contoh: `api/mhs/krs` |
| `ip_address` | varchar(45) | IP address client (support IPv6) |
| `user_agent` | text | Browser/app info |
| `status_code` | smallint | HTTP response code (200, 401, 404, dll) |
| `created_at` | timestamp | Waktu akses (auto-set) |

> **Wajib jalankan:** `php artisan migrate` setelah perubahan ini.

#### `app/Models/ActivityLog.php`
Model Eloquent untuk tabel `activity_logs`. Tanpa `updated_at` (hanya `created_at`) karena log tidak pernah diubah.

#### `app/Http/Middleware/LogActivity.php`
Middleware yang berjalan di akhir setiap request (setelah response dibuat). Cara kerja:
1. Jalankan request normal (`$next($request)`)
2. Cek guard mana yang aktif dari 3 guard: `mahasiswa_web`, `dosen_web`, `staff_web`
3. Jika ada user yang terautentikasi, simpan log ke tabel `activity_logs`
4. Seluruh proses logging dibungkus `try-catch` — jika DB error pun, response tidak terganggu

**File yang diubah:**

#### `bootstrap/app.php`
- Ditambahkan alias `'log.activity' => LogActivity::class`

#### `routes/mahasiswa.php`
- Middleware group mahasiswa diubah dari:
  ```php
  ['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie']
  ```
  menjadi:
  ```php
  ['sanctum.spa', 'auth:mahasiswa_web', 'sanctum.cookie', 'log.activity']
  ```

#### `routes/dosen.php`
- Middleware group dosen diubah dari:
  ```php
  ['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie']
  ```
  menjadi:
  ```php
  ['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie', 'log.activity']
  ```

#### `routes/staff.php`
- Middleware group staf diubah dari:
  ```php
  ['sanctum.spa', 'auth:staff_web', 'sanctum.cookie']
  ```
  menjadi:
  ```php
  ['sanctum.spa', 'auth:staff_web', 'sanctum.cookie', 'log.activity']
  ```

**Cakupan logging:**
- Semua endpoint `/api/mhs/*` (mahasiswa)
- Semua endpoint `/api/dosen/*` (dosen)
- Semua endpoint `/api/staff/*` (staf)
- Endpoint login **tidak** dicatat (hanya setelah berhasil login)

---

### 3. Konsolidasi Middleware Role

**Masalah sebelumnya:**
Ada 3 middleware untuk role-based access control yang tumpang tindih:
- `EnsureIsAdmin` — cek `user->isAdmin()`
- `EnsureIsStaff` — cek `user->isStaff()`
- `EnsureRole` — dinamis, cek `user->role in [$roles]`

Padahal seluruh route di `web.php` sudah menggunakan `role:admin,staff` (EnsureRole), sehingga `EnsureIsAdmin` dan `EnsureIsStaff` terdaftar sebagai alias tapi tidak dipakai oleh route manapun.

**File yang diubah:**

#### `bootstrap/app.php`
- Dihapus import `use App\Http\Middleware\EnsureIsAdmin;`
- Dihapus import `use App\Http\Middleware\EnsureIsStaff;`
- Dihapus alias `'admin' => EnsureIsAdmin::class`
- Dihapus alias `'staff' => EnsureIsStaff::class`
- Ditambahkan komentar pengingat: gunakan `role:admin` / `role:admin,staff`

**Cara pakai yang benar (tidak berubah):**
```php
// Admin panel — sudah benar sejak awal
->middleware(['auth', 'role:admin,staff'])

// Jika ingin batasi hanya admin:
->middleware(['auth', 'role:admin'])
```

**File `EnsureIsAdmin.php` dan `EnsureIsStaff.php`** tidak dihapus dari disk — tetap ada sebagai class PHP, hanya tidak terdaftar sebagai middleware alias. Bisa dihapus manual jika sudah yakin tidak diperlukan.

---

---

## 2026-05-13 — Perbaikan Bug EnsureFrontendRequestsAreStateful (X-XSRF-TOKEN Tidak Diperlukan)

**Masalah yang ditemukan:**
Sistem bisa diakses menggunakan `X-XSRF-TOKEN` (token CSRF dari browser) padahal arsitektur sistem ini hanya menggunakan `laravel-session` — tidak seharusnya meminta token CSRF.

**Akar masalah:**
Di `bootstrap/app.php`, terdapat komentar yang menyatakan `EnsureFrontendRequestsAreStateful` **tidak dipakai**, tetapi class-nya tetap aktif di dalam grup middleware `sanctum.spa`:

```php
// Catatan: EnsureFrontendRequestsAreStateful TIDAK dipakai...
$middleware->appendToGroup('sanctum.spa', [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    // ValidateCsrfToken::class,              ← dimatikan (benar)
    EnsureFrontendRequestsAreStateful::class,  ← masih aktif (BUG)
]);
```

`EnsureFrontendRequestsAreStateful` dari Laravel Sanctum secara internal menambahkan kembali:
- `ValidateCsrfToken` → menyebabkan browser wajib kirim `X-XSRF-TOKEN`
- `AuthenticateSession` → bisa memflush session mahasiswa/dosen saat ada request dari guard `web` (admin panel) di browser yang sama

**Kenapa `SameSite=Lax` sudah cukup tanpa `X-XSRF-TOKEN`?**
Cookie session sudah dikonfigurasi `SameSite=Lax` (`config/session.php` baris 202). Dengan ini, browser **tidak akan mengirimkan** cookie session pada request cross-origin (misalnya dari domain lain). Serangan CSRF sudah dicegah di level cookie, bukan di level token.

**File yang diubah:**

#### `bootstrap/app.php`
- Dihapus `use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;`
- Dihapus `EnsureFrontendRequestsAreStateful::class` dari grup `sanctum.spa`
- Ditambahkan komentar penjelasan lengkap kenapa class ini tidak dipakai
- Grup `sanctum.spa` sekarang hanya berisi: `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`

**Setelah perbaikan:**

| Kondisi | Sebelum | Sesudah |
|---|---|---|
| Request dari browser (SPA) | Wajib kirim `X-XSRF-TOKEN` | Cukup `laravel-session` saja |
| Request dari Postman/API | Cukup `laravel-session` | Cukup `laravel-session` saja |
| Proteksi CSRF | Via `ValidateCsrfToken` (token-based) | Via `SameSite=Lax` (cookie-based, lebih tepat) |
| Risiko `AuthenticateSession` | Ada (bisa flush session lintas guard) | Tidak ada |

---

## Ringkasan File yang Berubah

| Tanggal | Aksi | File |
|---|---|---|
| 2026-04-27 | BARU | `database/migrations/2026_04_27_000000_create_activity_logs_table.php` |
| 2026-04-27 | BARU | `app/Models/ActivityLog.php` |
| 2026-04-27 | BARU | `app/Http/Middleware/LogActivity.php` |
| 2026-05-13 | DIUBAH | `bootstrap/app.php` — hapus `EnsureFrontendRequestsAreStateful`, perbaiki bug X-XSRF-TOKEN |
| 2026-04-27 | DIUBAH | `bootstrap/app.php` — hapus alias admin/staff, tambah log.activity |
| 2026-04-27 | DIUBAH | `config/sanctum.php` — tambah panduan production |
| 2026-04-27 | DIUBAH | `.env` — tambah SESSION_SECURE_COOKIE, komentar production |
| 2026-04-27 | DIUBAH | `routes/mahasiswa.php` — tambah log.activity middleware |
| 2026-04-27 | DIUBAH | `routes/dosen.php` — tambah log.activity middleware |
| 2026-04-27 | DIUBAH | `routes/staff.php` — tambah log.activity middleware |

## Langkah Wajib Setelah Update Ini

```bash
php artisan migrate
```

Tabel `activity_logs` harus dibuat sebelum sistem bisa mencatat aktivitas.

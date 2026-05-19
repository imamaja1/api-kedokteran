# SISKA Kedokteran — API Reference

> Sistem Informasi Akademik (SISKA) Kedokteran — Laravel 12 + Sanctum Cookie Auth

---

## 1. Informasi Project

| Item | Detail |
|---|---|
| **Framework** | Laravel 12 |
| **PHP** | ^8.2 |
| **Database** | MySQL (`api_siska_kedokteran`) |
| **Auth** | Laravel Sanctum (Cookie-based, session) |
| **Testing** | Pest PHP ^4.4 |
| **Session** | Database driver |
| **DB** | `api_siska_kedokteran` (root, no password) |

### Dependencies Utama

| Package | Versi | Kegunaan |
|---|---|---|
| laravel/framework | ^12.0 | Core framework |
| laravel/sanctum | * | API authentication |
| pestphp/pest | ^4.4 | Testing framework |
| laravel/pint | ^1.24 | Code style fixer |
| laravel/sail | ^1.41 | Docker development |
| laramint/laravel-brain | * | AI assistant |
| laravel/boost | ^2.3 | Dev tools |

### Scripts

| Command | Fungsi |
|---|---|
| `composer run setup` | Install deps, migrate, npm install & build |
| `composer run dev` | Jalankan server, queue, dan Vite dev |
| `composer run test` | Jalankan test suite |
| `php artisan test` | Jalankan Pest tests |

---

## 2. Arsitektur

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT (SPA)                         │
│                  (Vue / React / Blade)                      │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP Request + Cookie
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL 12 API                           │
│                                                             │
│  ┌───────────┐   ┌─────────────┐   ┌──────────────────┐   │
│  │  Routes   │──▶│ Middleware  │──▶│   Controller     │   │
│  │ (api.php) │   │  (Sanctum)  │   │                  │   │
│  └───────────┘   └─────────────┘   └────────┬─────────┘   │
│                                             │              │
│                                    ┌────────▼─────────┐   │
│                                    │    Service /     │   │
│                                    │   Repository     │   │
│                                    └────────┬─────────┘   │
│                                             │              │
│                                    ┌────────▼─────────┐   │
│                                    │     Model        │   │
│                                    │  (Eloquent ORM)  │   │
│                                    └────────┬─────────┘   │
│                                             │              │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
               ┌───────────────────────┐
               │    MySQL Database     │
               └───────────────────────┘
```

### Alur Autentikasi (Sanctum Cookie)

```
CLIENT                          SERVER (Laravel 12)
  │                                     │
  │── GET /sanctum/csrf-cookie ────────▶│
  │                                     │ Set-Cookie: XSRF-TOKEN
  │◀─────────────────────────────────── │
  │                                     │
  │── POST /api/auth/mhs/login ────────▶│
  │   Body: { nim, password }           │ Validasi credentials
  │   Header: X-XSRF-TOKEN             │ Buat session cookie
  │                                     │ Set-Cookie: laravel_session
  │◀── 200 OK + Cookie ─────────────── │
  │    { user, type: mahasiswa }        │
  │                                     │
  │── GET /api/mhs/me ────────────────▶│
  │   Cookie: laravel_session           │ auth:mahasiswa_web middleware
  │                                     │ Verifikasi session
  │◀── 200 OK { data user } ─────────  │
  │                                     │
  │── POST /api/auth/logout ───────────▶│
  │                                     │ Hapus session
  │◀── 200 OK ──────────────────────── │
```

### Alur Request API

```
1. Client kirim request ke /api/{endpoint}
        │
        ▼
2. Route → middleware (sanctum.spa, auth:xxx_web)
        │
        ├─ Tidak terautentikasi → 401 Unauthenticated
        │
        └─ Terautentikasi ──────▶
                │
                ▼
        3. Controller menerima Request
                │
                ▼
        4. Validasi input
                │
                ├─ Gagal validasi → 422 Unprocessable Entity
                │
                └─ Lolos ────────▶
                        │
                        ▼
                5. Service (Business logic)
                        │
                        ▼
                6. Model ↔ Database
                        │
                        ▼
                7. Return JsonResponse { status, message, data }
```

### Format Response

**Success:**
```json
{ "status": true, "message": "Berhasil", "data": { ... } }
```

**Error:**
```json
{ "status": false, "message": "Validasi gagal", "errors": { "field": ["error"] } }
```

---

## 3. Struktur Folder

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php              ← Login, Logout, Me (semua role)
│   │   ├── Api_Mahasiswa/
│   │   │   ├── MahasiswaController.php         ← me, profil, semester, profil_update
│   │   │   ├── KrsController.php               ← krs
│   │   │   ├── KhsController.php               ← khs
│   │   │   ├── KurikulumController.php         ← kurikulum
│   │   │   └── PetikanNIlaiController.php      ← petikan_nilai
│   │   ├── Api_Dosen/
│   │   │   └── PerwalianController.php         ← (kosong, WIP)
│   │   ├── Api_Staff/
│   │   │   ├── DefaultController.php           ← tahun_angkatan
│   │   │   ├── AkademikController.php          ← program_studi, kurikulum, krs, khs, petikan
│   │   │   └── MasterDataController.php        ← CRUD semua master data
│   │   ├── Admin/
│   │   │   ├── DashboardController.php         ← admin dashboard
│   │   │   ├── UserController.php              ← CRUD users
│   │   │   ├── ApiSectionController.php        ← CRUD api sections
│   │   │   ├── ApiEndpointController.php       ← CRUD api endpoints
│   │   │   ├── ApiConnectionController.php     ← CRUD api connections
│   │   │   ├── MahasiswaController.php         ← admin mahasiswa + sync-siska
│   │   │   ├── DosenController.php             ← admin dosen + sync-siska
│   │   │   ├── MatakuliahController.php        ← admin matakuliah + sync-siska
│   │   │   ├── TahunAkademikController.php     ← admin tahun akademik + sync-siska
│   │   │   ├── KrsKhsController.php            ← admin krs/khs + sync-siska
│   │   │   ├── KelasController.php             ← admin kelas + sync-siska
│   │   │   └── KurikulumController.php         ← admin kurikulum + sync-siska
│   │   ├── LoginController.php                 ← web login
│   │   ├── DocsController.php                  ← docs & tester page
│   │   ├── DosenController.php                 ← (legacy)
│   │   ├── MahasiswaController.php             ← (legacy)
│   │   ├── KrsController.php                   ← (legacy)
│   │   ├── KhsController.php                   ← (legacy)
│   │   ├── ProgramStudiController.php          ← (legacy)
│   │   ├── MatakuliahController.php            ← (legacy)
│   │   └── Controller.php                      ← base controller
│   ├── Middleware/
│   │   ├── EnsureRole.php                      ← role-based access
│   │   ├── EnsureIsAdmin.php                   ← admin only
│   │   ├── EnsureIsStaff.php                   ← staff only
│   │   ├── EnsureValidSanctumCookie.php        ← validate sanctum cookie
│   │   └── LogActivity.php                     ← log all requests
│   └── Requests/
│       └── Auth/
│           ├── MhsLoginRequest.php             ← validasi login mahasiswa
│           └── DosenLoginRequest.php           ← validasi login dosen
├── Models/
│   ├── User.php                                ← users table
│   ├── Mahasiswa.php                           ← mahasiswa table
│   ├── Dosen.php                               ← dosen table
│   ├── ProgramStudi.php                        ← program_studi table
│   ├── Matakuliah.php                          ← matakuliah table
│   ├── TahunAkademik.php                       ← tahun_akademik table
│   ├── Kurikulum.php                           ← kurikulum table
│   ├── NamaKurikulum.php                       ← nama_kurikulum table
│   ├── KurikulumAngkatan.php                   ← kurikulum_angkatan table
│   ├── Krs.php                                 ← krs table
│   ├── KrsDetail.php                           ← krs_detail table
│   ├── KhsDetail.php                           ← khs_detail table
│   ├── Kelas.php                               ← kelas table
│   ├── NamaKelas.php                           ← nama_kelas table
│   ├── KelasMahasiswa.php                      ← kelas_mahasiswa table
│   ├── Mengajar.php                            ← mengajar table
│   ├── ActivityLog.php                         ← activity_logs table
│   ├── ApiSection.php                          ← api_sections table
│   ├── ApiEndpoint.php                         ← api_endpoints table
│   └── ApiConnection.php                       ← api_connections table
├── Service/
│   ├── ServiceKRS.php                          ← logic KRS mahasiswa & staff
│   ├── ServiceKHS.php                          ← logic KHS mahasiswa & staff
│   ├── ServiceKurikulum.php                    ← logic kurikulum
│   ├── ServicePetikanNilai.php                 ← logic petikan nilai
│   ├── ServiceMahasiswa.php                    ← logic mahasiswa CRUD
│   ├── ServiceDosen.php                        ← logic dosen CRUD
│   ├── ServiceMatakuliah.php                   ← logic matakuliah CRUD
│   ├── ServiceProgramStudi.php                 ← logic program studi CRUD
│   ├── ServiceTahunAkademik.php                ← logic tahun akademik CRUD
│   └── ServiceTahunAngkatan.php                ← logic tahun angkatan
└── Providers/
    └── AppServiceProvider.php

routes/
├── api.php                                     ← Auth endpoints (public)
├── mahasiswa.php                               ← Protected mahasiswa routes
├── dosen.php                                   ← Protected dosen routes
├── staff.php                                   ← Protected staff routes
├── web.php                                     ← Web routes (admin panel, login)
└── console.php                                 ← Console routes
```

---

## 4. Route Groups & Middleware

| Prefix | Middleware | Deskripsi |
|---|---|---|
| `/api/auth` | `sanctum.spa`, `throttle:6,1` | Login (mhs/dosen/staff), logout, csrf-cookie |
| `/api/mhs` | `sanctum.spa`, `auth:mahasiswa_web`, `sanctum.cookie`, `log.activity` | Endpoint mahasiswa |
| `/api/dosen` | `sanctum.spa`, `auth:dosen_web`, `sanctum.cookie`, `log.activity` | Endpoint dosen |
| `/api/staff` | `sanctum.spa`, `auth:staff_web`, `sanctum.cookie`, `log.activity` | Endpoint staff |
| `/admin` | `auth`, `role:admin,staff` | Admin panel web UI |

---

## 5. Semua Endpoint API

### 5.1 Auth (Public)

| Method | Endpoint | Controller | Deskripsi | Body |
|---|---|---|---|---|
| GET | `/api/auth/csrf-cookie` | Closure | Ambil CSRF token | - |
| POST | `/api/auth/mhs/login` | AuthController::mhs_login | Login Mahasiswa | `{ nim, password }` |
| POST | `/api/auth/dosen/login` | AuthController::dosen_login | Login Dosen | `{ email, password }` |
| POST | `/api/auth/staff/login` | AuthController::login_staff | Login Staff | `{ email, password }` |
| POST | `/api/auth/logout` | AuthController::logout | Logout (semua role) | - |

### 5.2 Mahasiswa (Protected: `auth:mahasiswa_web`)

| Method | Endpoint | Controller | Deskripsi | Query Params |
|---|---|---|---|---|
| GET | `/api/mhs/me` | MahasiswaController::me | Data user login | - |
| GET | `/api/mhs/profile` | MahasiswaController::profil | Profil + provinces | - |
| PUT | `/api/mhs/profile/update` | MahasiswaController::profil_update | Update profil | body: semua field mahasiswa |
| GET | `/api/mhs/semester` | MahasiswaController::semester | List semester | - |
| GET | `/api/mhs/kurikulum` | KurikulumController::kurikulum | Kurikulum mahasiswa | - |
| GET | `/api/mhs/krs` | KrsController::krs | KRS mahasiswa | `?semester=` |
| GET | `/api/mhs/khs` | KhsController::khs | KHS mahasiswa | `?semester=` |
| GET | `/api/mhs/petikan-nilai` | PetikanNilaiController::petikan_nilai | Petikan nilai | - |

### 5.3 Dosen (Protected: `auth:dosen_web`)

| Method | Endpoint | Controller | Deskripsi | Query Params |
|---|---|---|---|---|
| GET | `/api/dosen/me` | AuthController::me_dosen | Data user login | - |
| GET | `/api/dosen` | DosenController::index | List dosen (paginate 20) | - |
| GET | `/api/dosen/detail` | DosenController::show | Detail dosen | `?kode_dosen=` |
| PUT | `/api/dosen` | DosenController::update | Update dosen | body: `{ kode_dosen, ... }` |
| POST | `/api/dosen/logout` | AuthController::logout | Logout dosen | - |

### 5.4 Staff (Protected: `auth:staff_web`)

#### Default

| Method | Endpoint | Controller | Deskripsi |
|---|---|---|---|
| GET | `/api/staff/me` | AuthController::me_staff | Data user login |
| GET | `/api/staff/tahun-angkatan` | DefaultController::tahun_angkatan | List tahun angkatan |

#### Akademik

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/akademik/program-studi` | AkademikController::program_studi | List program studi | - |
| GET | `/api/staff/akademik/nama-kurikulum` | AkademikController::NamaKurikulum | List nama kurikulum | - |
| GET | `/api/staff/akademik/kurikulum` | AkademikController::Kurikulum | List kurikulum | body: `{ code_nama_kurikulum }` (encrypted) |
| GET | `/api/staff/akademik/krs` | AkademikController::KRS | KRS by NIM | body: `{ nim }` |
| GET | `/api/staff/akademik/krs-detail` | AkademikController::KRSDetail | Detail KRS | body: `{ code_krs }` (encrypted) |
| GET | `/api/staff/akademik/khs` | AkademikController::KHS | KHS by NIM | body: `{ nim }` |
| GET | `/api/staff/akademik/khs-detail` | AkademikController::KHSDetail | Detail KHS | body: `{ code_krs }` (encrypted) |
| GET | `/api/staff/akademik/petikan-nilai` | AkademikController::PetikanNilai | Petikan nilai by NIM | body: `{ nim }` |

#### Master Data — Program Studi

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/program-studi` | MasterDataController::GetProgramStudi | List program studi | - |
| GET | `/api/staff/master-data/program-studi-show` | MasterDataController::GetOneProgramStudi | Detail program studi | `?code=` (encrypted) |
| POST | `/api/staff/master-data/program-studi` | MasterDataController::StoreProgramStudi | Buat program studi | `{ nama_program_studi, singkatan_program_studi, kompetensi }` |
| PUT | `/api/staff/master-data/program-studi` | MasterDataController::UpdateProgramStudi | Update program studi | `{ code, nama_program_studi, singkatan_program_studi, kompetensi }` |
| DELETE | `/api/staff/master-data/program-studi/{code}` | MasterDataController::DeleteProgramStudi | Hapus program studi | `code` (encrypted) |

#### Master Data — Nama Kurikulum

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/nama-kurikulum` | MasterDataController::GetNamaKurikulum | List nama kurikulum | - |
| GET | `/api/staff/master-data/nama-kurikulum-show` | MasterDataController::GetOneNamaKurikulum | Detail nama kurikulum | `?code=` (encrypted) |
| POST | `/api/staff/master-data/nama-kurikulum` | MasterDataController::StoreNamaKurikulum | Buat nama kurikulum | `{ nama_kurikulum, kode_program_studi, angkatan1, ekstensi1, paket1 }` |
| PUT | `/api/staff/master-data/nama-kurikulum` | MasterDataController::UpdateNamaKurikulum | Update nama kurikulum | `{ code, nama_kurikulum, kode_program_studi, angkatan1, ekstensi1, paket1 }` |
| DELETE | `/api/staff/master-data/nama-kurikulum/{code}` | MasterDataController::DeleteNamaKurikulum | Hapus nama kurikulum | `code` (encrypted) |

#### Master Data — Tahun Akademik

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/tahun-akademik` | MasterDataController::GetTahunAkademik | List tahun akademik | `?tahun_akademik=&semester=&status=` |
| GET | `/api/staff/master-data/tahun-akademik-show` | MasterDataController::GetOneTahunAkademik | Detail tahun akademik | `?code=` (encrypted) |
| POST | `/api/staff/master-data/tahun-akademik` | MasterDataController::StoreTahunAkademik | Buat tahun akademik | `{ tahun_akademik, semester, tanggal_mulai, tanggal_berakhir, status, status_kpat }` |
| PUT | `/api/staff/master-data/tahun-akademik` | MasterDataController::UpdateTahunAkademik | Update tahun akademik | `{ code, tahun_akademik, semester, tanggal_mulai, tanggal_berakhir, status, status_kpat }` |
| DELETE | `/api/staff/master-data/tahun-akademik/{code}` | MasterDataController::DeleteTahunAkademik | Hapus tahun akademik | `code` (encrypted) |

#### Master Data — Matakuliah

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/matakuliah` | MasterDataController::GetMatakuliah | List matakuliah | `?code_program_studi=` (encrypted) |
| GET | `/api/staff/master-data/matakuliah-show` | MasterDataController::GetOneMatakuliah | Detail matakuliah | `?code=` (encrypted) |
| POST | `/api/staff/master-data/matakuliah` | MasterDataController::StoreMatakuliah | Buat matakuliah | `{ kode_matakuliah, nama_matakuliah, jenis, sks_teori, sks_praktik, block, kode_program_studi }` |
| PUT | `/api/staff/master-data/matakuliah` | MasterDataController::UpdateMatakuliah | Update matakuliah | `{ code, kode_matakuliah, nama_matakuliah, jenis, sks_teori, sks_praktik, block, kode_program_studi }` |
| DELETE | `/api/staff/master-data/matakuliah/{code}` | MasterDataController::DeleteMatakuliah | Hapus matakuliah | `code` (encrypted) |

#### Master Data — Dosen

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/dosen` | MasterDataController::GetDosen | List dosen | `?kode_program_studi=&nama_dosen=&alamat_email=` (encrypted) |
| GET | `/api/staff/master-data/dosen-show` | MasterDataController::GetOneDosen | Detail dosen | `?code=` (encrypted) |
| POST | `/api/staff/master-data/dosen` | MasterDataController::StoreDosen | Buat dosen | `{ nama_dosen, nik, no_telp, alamat_email, field_studi, alumni, homebase, status_dosen, aktif, chatid, sandi_pengguna }` |
| PUT | `/api/staff/master-data/dosen` | MasterDataController::UpdateDosen | Update dosen | `{ code, nama_dosen, nik, no_telp, alamat_email, field_studi, alumni, homebase, status_dosen, aktif, chatid, sandi_pengguna }` |
| DELETE | `/api/staff/master-data/dosen/{code}` | MasterDataController::DeleteDosen | Hapus dosen | `code` (encrypted) |

#### Master Data — Mahasiswa

| Method | Endpoint | Controller | Deskripsi | Body / Query |
|---|---|---|---|---|
| GET | `/api/staff/master-data/mahasiswa` | MasterDataController::GetMahasiswa | List mahasiswa | `?nim=&code=&angkatan=` |
| GET | `/api/staff/master-data/mahasiswa-show` | MasterDataController::GetOneMahasiswa | Detail mahasiswa | `?code=` (encrypted) |
| POST | `/api/staff/master-data/mahasiswa` | MasterDataController::StoreMahasiswa | Buat mahasiswa | `{ nim, nik, program_studi_kode, nama_mahasiswa, ... }` |
| PUT | `/api/staff/master-data/mahasiswa` | MasterDataController::UpdateMahasiswa | Update mahasiswa | `{ code, nim, nik, program_studi_kode, nama_mahasiswa, ... }` |
| DELETE | `/api/staff/master-data/mahasiswa/{code}` | MasterDataController::DeleteMahasiswa | Hapus mahasiswa | `code` (encrypted) |

---

## 6. Database — Models & Tabel

| Model | Tabel | Primary Key | Notes |
|---|---|---|---|
| User | users | id | role: admin/staff |
| Mahasiswa | mahasiswa | nim | soft deletes, HasApiTokens |
| Dosen | dosen | kode_dosen | HasApiTokens |
| ProgramStudi | program_studi | kode_program_studi | - |
| Matakuliah | matakuliah | kode_matakuliah | - |
| TahunAkademik | tahun_akademik | kode_tahun_akademik | - |
| Kurikulum | kurikulum | kode_kurikulum | - |
| NamaKurikulum | nama_kurikulum | kode_nama_kurikulum | - |
| KurikulumAngkatan | kurikulum_angkatan | - | pivot table |
| Krs | krs | kode_krs | - |
| KrsDetail | krs_detail | kode_krs_detail | - |
| KhsDetail | khs_detail | kode_khs_detail | - |
| Kelas | kelas | id | - |
| NamaKelas | nama_kelas | id | - |
| KelasMahasiswa | kelas_mahasiswa | id | - |
| Mengajar | mengajar | id | - |
| ActivityLog | activity_logs | id | auto-logged |
| ApiSection | api_sections | id | docs management |
| ApiEndpoint | api_endpoints | id | docs management |
| ApiConnection | api_connections | id | external API config |

---

## 7. Middleware

| Middleware | File | Fungsi |
|---|---|---|
| `sanctum.spa` | Built-in Sanctum | Stateful SPA authentication |
| `auth:mahasiswa_web` | Built-in Auth | Guard mahasiswa |
| `auth:dosen_web` | Built-in Auth | Guard dosen |
| `auth:staff_web` | Built-in Auth | Guard staff |
| `sanctum.cookie` | EnsureValidSanctumCookie.php | Validasi cookie Sanctum |
| `log.activity` | LogActivity.php | Log semua request ke activity_logs |
| `role:admin,staff` | EnsureRole.php | Role-based access control |
| `auth` | Built-in Auth | Web authentication |

---

## 8. Guards (config/auth.php)

| Guard | Driver | Provider | Model |
|---|---|---|---|
| `mahasiswa_web` | session | mahasiswa | Mahasiswa |
| `dosen_web` | session | dosen | Dosen |
| `staff_web` | session | users | User |

---

## 9. Migrations (25 files)

| Tanggal | Migration | Tabel |
|---|---|---|
| 0001_01_01 | create_users_table | users, password_reset_tokens, sessions |
| 0001_01_01 | create_cache_table | cache, cache_locks |
| 0001_01_01 | create_jobs_table | jobs, job_batches, failed_jobs |
| 2026_03_13 | create_mahasiswa_table | mahasiswa |
| 2026_03_13 | create_program_studi_table | program_studi |
| 2026_03_13 | create_tahun_akademik_table | tahun_akademik |
| 2026_03_13 | create_matakuliah_table | matakuliah |
| 2026_03_13 | create_nama_kurikulum_table | nama_kurikulum |
| 2026_03_13 | create_kurikulum_table | kurikulum |
| 2026_03_13 | create_dosen_table | dosen |
| 2026_03_13 | create_krs_table | krs |
| 2026_03_13 | create_krs_detail_table | krs_detail |
| 2026_03_13 | create_khs_detail_table | khs_detail |
| 2026_03_13 | create_personal_access_tokens_table | personal_access_tokens |
| 2026_03_16 | add_role_to_users_table | users (add role column) |
| 2026_03_16 | create_api_sections_table | api_sections |
| 2026_03_16 | create_api_endpoints_table | api_endpoints |
| 2026_03_25 | create_api_connections_table | api_connections |
| 2026_03_25 | add_cookie_to_api_connections_table | api_connections (add cookie column) |
| 2026_04_01 | create_nama_kelas_table | nama_kelas |
| 2026_04_01 | create_kelas_table | kelas |
| 2026_04_01 | create_kelas_mahasiswa_table | kelas_mahasiswa |
| 2026_04_01 | create_mengajar_table | mengajar |
| 2026_04_17 | create_kurikulum_angkatan_table | kurikulum_angkatan |
| 2026_04_27 | create_activity_logs_table | activity_logs |

---

## 10. Catatan Penting

### Encrypted Parameters
Semua parameter `code` yang dikirim ke endpoint staff di-**encrypt** menggunakan `Crypt::encryptString()` dan di-decrypt di backend dengan `Crypt::decryptString()`. Ini berlaku untuk semua CRUD operations di master data.

### Rate Limiting
Endpoint auth (`/api/auth/*`) memiliki throttle **6 requests per menit**.

### Activity Logging
Semua request ke endpoint protected (mhs, dosen, staff) otomatis dicatat ke tabel `activity_logs` melalui middleware `log.activity`.

### Soft Deletes
Model `Mahasiswa` dan `Dosen` menggunakan soft deletes.

### Password Fields
- Mahasiswa: field `sandi` (hashed)
- Dosen: field `sandi_pengguna` (hashed)
- Staff/User: field `password` (hashed)

### Sync Siska
Admin panel memiliki fitur sync dengan SISKA external API untuk: mahasiswa, dosen, matakuliah, tahun akademik, krs-khs, kelas, kurikulum.

---

## 11. Hasil Test

```
PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                    2.26s

PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                        6.91s

Tests:    2 passed (2 assertions)
Duration: 21.73s
```

| Test Suite | Test Name | Status | Duration |
|---|---|---|---|
| Unit | that true is true | ✅ Passed | 2.26s |
| Feature | the application returns a successful response | ✅ Passed | 6.91s |

---

## 12. Quick Reference — Login Flow

```bash
# 1. Get CSRF cookie
curl -X GET http://localhost:8000/sanctum/csrf-cookie -c cookies.txt

# 2. Login Mahasiswa
curl -X POST http://localhost:8000/api/auth/mhs/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -b cookies.txt -c cookies.txt \
  -d '{"nim":"12345","password":"rahasia"}'

# 3. Access protected endpoint
curl -X GET http://localhost:8000/api/mhs/me \
  -H "Accept: application/json" \
  -b cookies.txt
```

# Alur Program — API Laravel 12 + Sanctum Cookie

---

## 1. Gambaran Umum Arsitektur

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

---

## 2. Alur Autentikasi (Sanctum Cookie)

```
CLIENT                          SERVER (Laravel 12)
  │                                     │
  │── GET /sanctum/csrf-cookie ────────▶│
  │                                     │ Set-Cookie: XSRF-TOKEN
  │◀─────────────────────────────────── │
  │                                     │
  │── POST /api/login ─────────────────▶│
  │   Body: { email, password }         │ Validasi credentials
  │   Header: X-XSRF-TOKEN             │ Buat session cookie
  │                                     │ Set-Cookie: laravel_session
  │◀── 200 OK + Cookie ─────────────── │
  │    { user, token (opsional) }       │
  │                                     │
  │── GET /api/user ────────────────── ▶│
  │   Cookie: laravel_session           │ auth:sanctum middleware
  │                                     │ Verifikasi session
  │◀── 200 OK { data user } ─────────  │
  │                                     │
  │── POST /api/logout ────────────────▶│
  │                                     │ Hapus session
  │◀── 200 OK ──────────────────────── │
  │                                     │
```

---

## 3. Struktur Folder Project

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php         ← Login, Logout, Me
│   │   ├── MahasiswaController.php
│   │   ├── DosenController.php
│   │   ├── KrsController.php
│   │   ├── KhsController.php
│   │   └── ProgramStudiController.php
│   ├── Middleware/
│   │   └── (Sanctum sudah otomatis)
│   └── Requests/
│       ├── Auth/
│       │   └── LoginRequest.php
│       └── (FormRequest per fitur)
├── Models/
│   ├── Mahasiswa.php                      ← HasApiTokens
│   ├── Dosen.php                          ← HasApiTokens
│   ├── TahunAkademik.php
│   ├── Matakuliah.php
│   ├── ProgramStudi.php
│   ├── NamaKurikulum.php
│   ├── Kurikulum.php
│   ├── Krs.php
│   ├── KrsDetail.php
│   └── KhsDetail.php
├── Services/                              ← Business logic
│   ├── AuthService.php
│   ├── KrsService.php
│   └── KhsService.php
└── Providers/
    └── AppServiceProvider.php

routes/
├── api.php                                ← Semua endpoint API
└── web.php                                ← Hanya untuk CSRF cookie

config/
├── sanctum.php                            ← Stateful domains
└── cors.php                               ← Allow credentials

database/
└── migrations/                            ← Semua file migration
```

---

## 4. Alur Request API (Setelah Login)

```
1. Client kirim request ke /api/{endpoint}
        │
        ▼
2. Route api.php → middleware ['auth:sanctum']
        │
        ├─ Tidak terautentikasi → 401 Unauthenticated
        │
        └─ Terautentikasi ──────▶
                │
                ▼
        3. Controller menerima Request
                │
                ▼
        4. FormRequest validasi input
                │
                ├─ Gagal validasi → 422 Unprocessable Entity
                │
                └─ Lolos ────────▶
                        │
                        ▼
                5. Service / Repository
                   (Business logic)
                        │
                        ▼
                6. Model ↔ Database
                        │
                        ▼
                7. Return JsonResponse
                   { status, message, data }
```

---

## 5. Daftar Endpoint API (Rencana)

### Auth

| Method | Endpoint               | Deskripsi             |
| ------ | ---------------------- | --------------------- |
| GET    | `/sanctum/csrf-cookie` | Ambil CSRF token      |
| POST   | `/api/login`           | Login mahasiswa/dosen |
| POST   | `/api/logout`          | Logout                |
| GET    | `/api/me`              | Data user yang login  |

### Mahasiswa

| Method | Endpoint               | Deskripsi             |
| ------ | ---------------------- | --------------------- |
| GET    | `/api/mahasiswa`       | List semua mahasiswa  |
| GET    | `/api/mahasiswa/{nim}` | Detail mahasiswa      |
| POST   | `/api/mahasiswa`       | Tambah mahasiswa      |
| PUT    | `/api/mahasiswa/{nim}` | Update mahasiswa      |
| DELETE | `/api/mahasiswa/{nim}` | Soft delete mahasiswa |

### KRS

| Method | Endpoint                          | Deskripsi                 |
| ------ | --------------------------------- | ------------------------- |
| GET    | `/api/krs`                        | List KRS                  |
| POST   | `/api/krs`                        | Buat KRS baru             |
| GET    | `/api/krs/{id}/detail`            | Detail KRS + matakuliah   |
| POST   | `/api/krs/{id}/detail`            | Tambah matakuliah ke KRS  |
| DELETE | `/api/krs/{id}/detail/{detailId}` | Hapus matakuliah dari KRS |

### KHS

| Method | Endpoint        | Deskripsi       |
| ------ | --------------- | --------------- |
| GET    | `/api/khs`      | List KHS        |
| POST   | `/api/khs`      | Input nilai KHS |
| GET    | `/api/khs/{id}` | Detail nilai    |

### Matakuliah

| Method | Endpoint               | Deskripsi         |
| ------ | ---------------------- | ----------------- |
| GET    | `/api/matakuliah`      | List matakuliah   |
| GET    | `/api/matakuliah/{id}` | Detail matakuliah |

### Program Studi

| Method | Endpoint             | Deskripsi          |
| ------ | -------------------- | ------------------ |
| GET    | `/api/program-studi` | List program studi |

---

## 6. Format Response Standar

```json
{
  "status": true,
  "message": "Berhasil",
  "data": { ... }
}
```

```json
{
    "status": false,
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email tidak valid"]
    }
}
```

---

## 7. Konfigurasi Sanctum Cookie

### `config/sanctum.php`

```php
'stateful' => explode(',', env(
    'SANCTUM_STATEFUL_DOMAINS',
    'localhost,localhost:8000,127.0.0.1,127.0.0.1:8000'
)),
'guard' => ['web'],
'expiration' => null,
```

### `.env`

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false     # true jika HTTPS
```

### `config/cors.php`

```php
'supports_credentials' => true,
'allowed_origins' => ['http://localhost:8000'],
```

### `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
})
```

---

## 8. Urutan Development

```
[1] Setup Project
    ├── Install Sanctum
    ├── Konfigurasi CORS & Cookie
    └── php artisan migrate

[2] Autentikasi
    ├── AuthController (login, logout, me)
    ├── HasApiTokens di Model Dosen & Mahasiswa
    └── Route: /sanctum/csrf-cookie, /api/login, /api/logout

[3] Master Data
    ├── ProgramStudiController
    ├── MatakuliahController
    └── TahunAkademikController

[4] Data Akademik
    ├── KrsController + KrsDetailController
    └── KhsDetailController

[5] Testing
    ├── Uji CSRF cookie flow
    ├── Uji login/logout
    └── Uji akses endpoint dengan auth:sanctum
```

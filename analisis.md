# ANALISIS MENDALAM — API_SISKA_KEDOKTERAN

> Dokumen ini merupakan analisis menyeluruh tentang kapabilitas, arsitektur, alur kerja, serta kelemahan sistem.

---

## 1. GAMBARAN UMUM SISTEM

**API_SISKA_KEDOKTERAN** adalah sistem backend berbasis **Laravel 12** yang berfungsi sebagai jembatan antara aplikasi front-end (SPA) dengan data akademik Program Studi Kedokteran. Sistem ini juga terhubung ke sistem eksternal bernama **SISKA** (Sistem Informasi Akademik) untuk sinkronisasi data.

| Komponen        | Detail                                 |
| --------------- | -------------------------------------- |
| Framework       | Laravel 12                             |
| Database        | MySQL                                  |
| Autentikasi     | Laravel Sanctum (Cookie-based session) |
| Pola API        | RESTful JSON                           |
| Bahasa          | PHP 8.x                                |
| Frontend Target | SPA (React/Vue/Blade)                  |

---

## 2. STRUKTUR DIREKTORI

```
API_SISKA_KEDOKTERAN/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              ← Login/logout semua role
│   │   │   ├── Admin/             ← Admin panel + sinkronisasi SISKA
│   │   │   ├── Api_Mahasiswa/     ← Endpoint untuk mahasiswa
│   │   │   ├── Api_Dosen/         ← Endpoint untuk dosen
│   │   │   ├── Api_Staff/         ← Endpoint untuk staf akademik
│   │   │   └── [root controllers] ← Controller publik
│   │   ├── Middleware/            ← Guard & role checker
│   │   └── Requests/              ← Form request validation
│   ├── Models/                    ← 19 model Eloquent
│   ├── Service/                   ← 5 service class logika bisnis
│   └── Providers/
├── routes/
│   ├── api.php                    ← Route utama + login
│   ├── mahasiswa.php              ← Route protected mahasiswa
│   ├── dosen.php                  ← Route protected dosen
│   └── staff.php                  ← Route protected staf
├── config/
│   ├── auth.php                   ← Definisi guard
│   └── sanctum.php                ← Konfigurasi cookie domain
└── database/
    └── migrations/                ← 22 file migrasi
```

---

## 3. ARSITEKTUR AUTENTIKASI

Sistem menggunakan **Multi-Guard Session-based Authentication** via Laravel Sanctum Cookie. Artinya tidak ada JWT/token string, melainkan session cookie seperti aplikasi web tradisional — cocok untuk SPA yang berjalan di domain sama.

### Guard yang Terdaftar

| Guard           | Model               | Digunakan Untuk   |
| --------------- | ------------------- | ----------------- |
| `web`           | `User`              | Default Laravel   |
| `mahasiswa_web` | `Mahasiswa`         | Session mahasiswa |
| `dosen_web`     | `Dosen`             | Session dosen     |
| `staff_web`     | `User` (role=staff) | Session staf      |

### Stateful Domains (Sanctum)

```
localhost, localhost:3000, localhost:8000, localhost:8080,
127.0.0.1, 127.0.0.1:3000, 127.0.0.1:8000, 127.0.0.1:5173
```

> **Catatan:** Hanya dikonfigurasi untuk lokal. Belum ada domain production.

### Alur Login Mahasiswa

```
1. Client → GET /auth/csrf-cookie
   ← Server set cookie XSRF-TOKEN

2. Client → POST /auth/mhs/login
   Body: { nim: "2401001", password: "..." }
   ← Server: query mahasiswa by nim OR email
             Hash::check(password, mahasiswa.sandi)
             Auth::guard('mahasiswa_web')->login($user)
             Session regenerated
   ← Response: data mahasiswa

3. Client → GET /api/mhs/krs (dengan session cookie)
   ← Middleware auth:mahasiswa_web validasi session
   ← Return data KRS
```

---

## 4. SEMUA ENDPOINT YANG TERSEDIA

### 4.1 Autentikasi (Publik)

| Method | Endpoint            | Keterangan            |
| ------ | ------------------- | --------------------- |
| GET    | `/auth/csrf-cookie` | Ambil CSRF token      |
| POST   | `/auth/mhs/login`   | Login mahasiswa       |
| POST   | `/auth/dosen/login` | Login dosen           |
| POST   | `/auth/staff/login` | Login staf ⚠️ ADA BUG |

### 4.2 Endpoint Mahasiswa (Protected: `auth:mahasiswa_web`)

| Method | Endpoint                                 | Keterangan                       |
| ------ | ---------------------------------------- | -------------------------------- |
| GET    | `/api/mhs/me`                            | Data mahasiswa saat ini          |
| GET    | `/api/mhs/profile`                       | Profil lengkap + daftar provinsi |
| PUT    | `/api/mhs/profile/update`                | Update profil mahasiswa          |
| GET    | `/api/mhs/menu`                          | Daftar menu/hak akses            |
| GET    | `/api/mhs/kurikulum`                     | Kurikulum sesuai angkatan        |
| GET    | `/api/mhs/krs?tahun_akademik=&semester=` | Kartu Rencana Studi              |
| GET    | `/api/mhs/khs?tahun_akademik=&semester=` | Kartu Hasil Studi                |
| GET    | `/api/mhs/petikannilai`                  | Petikan nilai seluruh semester   |
| POST   | `/api/mhs/logout`                        | Logout                           |

### 4.3 Endpoint Dosen (Protected: `auth:dosen_web`)

| Method | Endpoint                        | Keterangan                 |
| ------ | ------------------------------- | -------------------------- |
| GET    | `/api/dosen/me`                 | Data dosen saat ini        |
| GET    | `/api/dosen`                    | Daftar dosen (paginate 20) |
| GET    | `/api/dosen/detail?kode_dosen=` | Detail satu dosen          |
| PUT    | `/api/dosen`                    | Update data dosen          |
| POST   | `/api/dosen/logout`             | Logout                     |

### 4.4 Endpoint Staf (Protected: `auth:staff_web`)

| Method | Endpoint                                          | Keterangan           |
| ------ | ------------------------------------------------- | -------------------- |
| GET    | `/api/staff/me`                                   | Data staf saat ini   |
| GET    | `/api/staff/mahasiswa?nim=&kode_prodi=&angkatan=` | Cari mahasiswa       |
| GET    | `/api/staff/kurikulum/nama`                       | Semua nama kurikulum |

---

## 5. FITUR YANG DAPAT DILAKUKAN SISTEM

### 5.1 Manajemen Akademik Mahasiswa

- **KRS (Kartu Rencana Studi):** Mahasiswa bisa lihat matakuliah yang diambil per semester, lengkap dengan SKS teori, SKS praktik, dan status block.
- **KHS (Kartu Hasil Studi):** Mahasiswa bisa lihat nilai akhir per matakuliah per semester.
- **Kurikulum:** Mahasiswa bisa lihat seluruh kurikulum sesuai angkatan, dikelompokkan per semester dengan total SKS.
- **Petikan Nilai:** Mahasiswa bisa lihat rekap semua nilai dari seluruh semester — berguna untuk transkip informal.
- **Update Profil:** Mahasiswa bisa update data pribadi: nama, alamat, tanggal lahir, agama, telepon, data orang tua, dan password.

### 5.2 Fitur Dosen

- Lihat daftar dosen beserta data lengkap (bidang studi, kontak, homebase).
- Update data pribadi dosen (nama, email, nomor telepon, password).
- Autentikasi terpisah dari mahasiswa via guard `dosen_web`.

### 5.3 Fitur Staf Akademik

- Cari data mahasiswa berdasarkan NIM, kode program studi, atau angkatan.
- Lihat seluruh nama kurikulum yang tersedia.

### 5.4 Sinkronisasi Data dari SISKA (Admin Panel)

Ini fitur paling kompleks. Admin dapat melakukan sinkronisasi data dari sistem SISKA eksternal:

**Proses Sinkronisasi:**

1. Ambil CSRF token dari endpoint SISKA.
2. Login ke SISKA menggunakan kredensial tersimpan.
3. Simpan session cookie SISKA (berlaku 8 jam).
4. Fetch data (Mahasiswa / Dosen / Tahun Akademik / Kurikulum) dari SISKA.
5. Upsert (insert atau update) ke database lokal.
6. Password default `password` untuk mahasiswa baru yang disinkronisasi.

**Data yang Bisa Disinkronisasi:**

- Data mahasiswa (lengkap dengan profil)
- Data dosen
- Tahun akademik
- Kurikulum + nama kurikulum + kurikulum angkatan

### 5.5 Keamanan Fitur

- **CSRF Protection:** Semua endpoint login wajib CSRF token.
- **Rate Limiting:** Login dibatasi 6 request/menit.
- **Enkripsi Kredensial:** Password koneksi SISKA dienkripsi dengan `Crypt::encryptString()`.
- **Enkripsi ID:** `kode_krs_detail` dan `kode_khs_detail` dienkripsi sebelum dikirim ke frontend — mencegah manipulasi ID.
- **Soft Delete:** Data mahasiswa tidak benar-benar dihapus, tetap tersimpan dengan `deleted_at`.
- **Multi-Guard Logout:** Logout akan clear semua guard sekaligus (mahasiswa, dosen, staf).

---

## 6. MODEL DATA & RELASI

```
ProgramStudi
 ├── hasMany → Dosen (via homebase)
 ├── hasMany → NamaKurikulum
 └── belongsToMany → Mahasiswa

NamaKurikulum
 ├── hasMany → Kurikulum
 └── hasMany → KurikulumAngkatan

Kurikulum
 └── belongsTo → Matakuliah

Mahasiswa
 ├── belongsTo → ProgramStudi
 └── hasMany → Krs

Krs
 ├── belongsTo → Mahasiswa
 ├── belongsTo → TahunAkademik
 └── hasMany → KrsDetail

KrsDetail
 ├── belongsTo → Krs
 ├── belongsTo → Matakuliah
 └── hasOne → KhsDetail

KhsDetail
 └── belongsTo → KrsDetail

Kelas
 ├── hasMany → KelasMahasiswa
 └── hasMany → Mengajar

KelasMahasiswa
 ├── belongsTo → Kelas
 └── belongsTo → KrsDetail

ApiConnection
 └── Stores credentials SISKA (password encrypted)
```

---

## 7. SERVICE LAYER — LOGIKA BISNIS

### ServiceKRS

- Query gabungan: mahasiswa → program_studi → krs → krs_detail → matakuliah
- Filter berdasarkan NIM + tahun akademik + semester
- Enkripsi `kode_krs_detail` sebelum response
- Return: info mahasiswa + list matakuliah KRS

### ServiceKHS

- Identik dengan ServiceKRS tetapi join tambahan ke `khs_detail`
- Mengambil `nilai_akhir` per matakuliah

### ServiceKurikulum

- **Logika angkatan:** ambil 2 digit pertama NIM (e.g., NIM `2401001` → angkatan `24`)
- Cocokkan dengan `kurikulum_angkatan.angkatan` digit ke-3 dan ke-4
- Kelompokkan kurikulum per semester
- Hitung total SKS (teori + praktik) per semester

### ServiceMahasiswa

- Build query dinamis berdasarkan parameter yang ada (NIM / kode_prodi / angkatan)
- Angkatan dikonversi ke 2 digit → cocok dengan substr NIM posisi 1-2

### ServicePetikanNilai

- Gabungkan kurikulum dengan nilai dari semua KRS yang pernah diambil
- Per matakuliah bisa memiliki beberapa nilai (jika diulang)
- Sort nilai ascending per matakuliah

---

## 8. KELEMAHAN & MASALAH YANG DITEMUKAN

### 8.1 BUG KRITIS — Login Staf Tidak Berfungsi

**File:** `app/Http/Controllers/Auth/AuthController.php`, sekitar baris 88

```php
// Kode debug yang tertinggal — MEMBLOKIR EKSEKUSI
echo json_encode($user);
die;
```

**Dampak:** Endpoint `POST /auth/staff/login` tidak akan pernah mengembalikan response yang benar — eksekusi berhenti di `die`. Seluruh fitur staf (cari mahasiswa, lihat kurikulum) tidak bisa diakses karena tidak bisa login.

**Solusi:** Hapus kedua baris tersebut.

---

### 8.2 Konfigurasi Domain Hanya untuk Lokal

**File:** `config/sanctum.php`

Domain stateful yang dikonfigurasi hanya `localhost` dan `127.0.0.1`. Jika sistem di-deploy ke server production, autentikasi cookie tidak akan bekerja karena domain tidak terdaftar.

**Dampak:** Sistem tidak siap production.

**Solusi:** Tambahkan domain production ke `SANCTUM_STATEFUL_DOMAINS` di `.env`.

---

### 8.3 Password Mahasiswa Default Tidak Aman

**File:** Admin/MahasiswaController.php (syncWithSiska)

Mahasiswa baru hasil sinkronisasi SISKA mendapat password default `'password'` — hardcoded dan sangat mudah ditebak.

**Dampak:** Semua akun mahasiswa baru rentan diretas dengan password sederhana ini.

**Solusi:** Generate password acak per mahasiswa dan kirim via email/notifikasi, atau gunakan NIM sebagai password awal.

---

### 8.4 Penggunaan `substr` Hardcoded untuk Angkatan

**File:** `app/Service/ServiceKurikulum.php`, `app/Service/ServiceMahasiswa.php`

```php
// Asumsi 2 digit pertama NIM = kode angkatan
$angkatan = substr($nim, 0, 2);
```

Logika ini mengasumsikan format NIM selalu `YY` (2 digit tahun) di posisi pertama. Jika format NIM berubah atau ada pengecualian, seluruh fitur kurikulum dan petikan nilai akan rusak.

**Dampak:** Rapuh terhadap perubahan format NIM.

---

### 8.5 Tidak Ada Endpoint CRUD untuk KRS/KHS dari Sisi Mahasiswa

Mahasiswa hanya bisa **melihat** KRS dan KHS — tidak ada fitur:

- Tambah/ubah/hapus matakuliah KRS
- Input nilai KHS
- Persetujuan KRS oleh dosen wali

Artinya sistem ini hanya berperan sebagai **read-only viewer**, bukan sistem manajemen KRS penuh.

---

### 8.6 Fitur Dosen Sangat Terbatas

Endpoint dosen hanya bisa:

- Lihat daftar dosen
- Update profil sendiri

Tidak ada fitur:

- Lihat mahasiswa bimbingan (perwalian)
- Input nilai
- Lihat jadwal mengajar
- Approve KRS mahasiswa

Padahal sudah ada model `Mengajar` dan `KelasMahasiswa` yang menunjukkan relasi dosen-kelas sudah dirancang, tetapi belum ada controller/endpoint-nya.

---

### 8.7 Tidak Ada Validasi Kepemilikan Data

Contoh: endpoint `GET /api/mhs/krs` hanya mengambil NIM dari session, bukan dari parameter. Ini sudah benar. Tetapi di beberapa tempat, tidak ada pengecekan apakah data yang diminta benar-benar milik user yang login.

Potensi **IDOR (Insecure Direct Object Reference)** jika ada endpoint yang menerima ID dari parameter tanpa validasi kepemilikan.

---

### 8.8 Throttle Login Terlalu Rendah

**File:** `routes/api.php`

```php
->middleware(['sanctum.spa', 'throttle:6,1'])
```

Hanya 6 request per menit per IP. Ini bisa memblokir user normal yang mencoba login ulang beberapa kali (misalnya salah password 3x, refresh, coba lagi). Di sisi lain, masih terlalu lemah untuk mencegah brute force dari banyak IP berbeda.

---

### 8.9 Tidak Ada Pagination di Beberapa Endpoint

`GET /api/staff/mahasiswa` mengembalikan **semua mahasiswa** sekaligus tanpa pagination. Jika ada ribuan mahasiswa, ini akan sangat lambat dan boros memori.

---

### 8.10 Tidak Ada Logging & Audit Trail

Tidak ada pencatatan aktivitas:

- Siapa yang login/logout kapan
- Perubahan data profil tidak dicatat siapa yang mengubah
- Sinkronisasi SISKA tidak ada log riwayat

---

### 8.11 Struktur Middleware Ganda yang Tumpang Tindih

Ada `EnsureIsAdmin`, `EnsureIsStaff`, dan `EnsureRole` — ketiganya punya fungsi serupa. `EnsureRole` bisa menggantikan keduanya, tetapi ketiganya tetap ada. Ini menambah kompleksitas tanpa manfaat.

---

### 8.12 Kolom Legacy di Database

Di tabel `kurikulum` dan `krs_detail` ada kolom `kode_matakuliah` yang sepertinya legacy (duplikat dari relasi via `id_matakuliah`). Ini berpotensi menyebabkan data tidak konsisten jika satu diupdate tetapi yang lain tidak.

---

## 9. FITUR YANG SUDAH ADA DI MODEL TAPI BELUM DIIMPLEMENTASI

| Model/Tabel                     | Indikasi Fitur             | Status                                                  |
| ------------------------------- | -------------------------- | ------------------------------------------------------- |
| `Mengajar`                      | Jadwal mengajar dosen      | Model ada, controller/endpoint belum ada                |
| `KelasMahasiswa`                | Mahasiswa masuk kelas mana | Model ada, endpoint belum ada                           |
| `Kelas` + `NamaKelas`           | Manajemen kelas            | Model ada, Admin controller ada, API endpoint belum ada |
| `Api_Dosen/PerwalianController` | Perwalian dosen-mahasiswa  | Controller ada tapi mungkin kosong/belum lengkap        |
| `ApiSection` + `ApiEndpoint`    | Dokumentasi API dinamis    | Model ada, belum jelas digunakan untuk apa              |

---

## 10. RINGKASAN KAPABILITAS vs KETERBATASAN

### Yang Sudah Bisa Dilakukan

| Fitur                                     | Status                                           |
| ----------------------------------------- | ------------------------------------------------ |
| Login multi-role (mahasiswa, dosen, staf) | ✅ Berfungsi (kecuali staf ada bug)              |
| Lihat KRS per semester                    | ✅ Berfungsi                                     |
| Lihat KHS per semester                    | ✅ Berfungsi                                     |
| Lihat kurikulum berdasarkan angkatan      | ✅ Berfungsi                                     |
| Lihat petikan nilai                       | ✅ Berfungsi                                     |
| Update profil mahasiswa                   | ✅ Berfungsi                                     |
| Update profil dosen                       | ✅ Berfungsi                                     |
| Cari mahasiswa (staf)                     | ✅ Berfungsi (setelah bug login staf diperbaiki) |
| Sinkronisasi data dari SISKA              | ✅ Berfungsi di admin panel                      |
| CSRF protection                           | ✅ Aktif                                         |
| Rate limiting login                       | ✅ Aktif                                         |
| Enkripsi ID sensitif                      | ✅ Aktif                                         |

### Yang Belum Bisa Dilakukan

| Fitur                               | Status                     |
| ----------------------------------- | -------------------------- |
| Login staf via API                  | ❌ Bug kritis (debug code) |
| Tambah/ubah KRS oleh mahasiswa      | ❌ Belum ada               |
| Input/edit nilai KHS                | ❌ Belum ada               |
| Approve KRS oleh dosen              | ❌ Belum ada               |
| Jadwal mengajar dosen               | ❌ Belum ada endpoint      |
| Notifikasi (email/push)             | ❌ Belum ada               |
| Export PDF/Excel (transkip, KRS)    | ❌ Belum ada               |
| Siap production (domain config)     | ❌ Belum dikonfigurasi     |
| Audit trail / logging               | ❌ Belum ada               |
| Pagination endpoint staff/mahasiswa | ❌ Belum ada               |

---

## 11. PRIORITAS PERBAIKAN

| Prioritas | Masalah                                         | Lokasi                                      |
| --------- | ----------------------------------------------- | ------------------------------------------- |
| 🔴 KRITIS | Hapus debug code `echo json_encode($user);die;` | `AuthController.php` ~baris 88              |
| 🔴 KRITIS | Konfigurasi domain production di Sanctum        | `config/sanctum.php` / `.env`               |
| 🟠 TINGGI | Password default mahasiswa tidak aman           | Admin/MahasiswaController.php               |
| 🟠 TINGGI | Tambah pagination di endpoint staff/mahasiswa   | `Api_Staff/AkademikController.php`          |
| 🟡 SEDANG | Implementasi endpoint perwalian dosen           | `Api_Dosen/PerwalianController.php`         |
| 🟡 SEDANG | Tambah logging aktivitas                        | Middleware baru                             |
| 🟢 RENDAH | Konsolidasi middleware role                     | Hapus duplikasi EnsureIsAdmin/EnsureIsStaff |
| 🟢 RENDAH | Bersihkan kolom legacy `kode_matakuliah`        | Database migration                          |

---

## 12. ANALISIS MENDALAM — PATTERN & BEST PRACTICES

### 12.1 Pattern yang Diterapkan dengan Baik

#### ✅ Service Layer Pattern

Sistem sudah memisahkan logika bisnis dari controller. Contoh:

- `ServiceKRS`, `ServiceKHS` menangani query kompleks + enkripsi
- `ServiceMahasiswa` menangani filter dinamis

```php
// Di Controller
public function krs(): JsonResponse {
    return (new ServiceKRS)->getKRS(Auth::user()->nim);
}

// Query kompleks tersenter di Service
```

**Manfaat:** Logika reusable, mudah di-test, controller tetap slim.

---

#### ✅ Multi-Guard Architecture

Menggunakan guard terpisah untuk setiap role:

```php
Auth::guard('mahasiswa_web')->login($user);
Auth::guard('dosen_web')->login($user);
```

**Manfaat:**

- Session berbeda per role, tidak ada cross-contamination
- Mudah implementasi role-based access
- Logout bisa dilakukan per-role

---

#### ✅ Enkripsi ID Sensitif

`kode_krs_detail` dan `kode_khs_detail` dienkripsi sebelum dikirim ke frontend:

```php
$detail->kode_krs_detail = Crypt::encryptString($detail->kode_krs_detail);
```

**Manfaat:** Mencegah user menebak ID dan mengakses data orang lain (IDOR).

---

#### ✅ Form Request Validation

Ada file `app/Http/Requests/Auth/MhsLoginRequest.php` untuk validasi input.

**Manfaat:** Centralized validation rules, mudah di-maintenance.

---

### 12.2 Pattern yang Bisa Ditingkatkan

#### ⚠️ Logika Angkatan Hardcoded di Service

Saat ini:

```php
// Di ServiceKurikulum.php
$angkatan = substr($nim, 0, 2);
```

**Masalah:** Jika format NIM berubah atau ada mahasiswa transfer, ini akan error. Lebih baik simpen `angkatan` langsung di tabel `mahasiswa`.

**Rekomendasi:**

```php
// Di tabel mahasiswa, tambah kolom: angkatan (YEAR)
// Saat sync dari SISKA, set angkatan = substr(nim, 0, 2)
// Di service, tinggal query:
$mahasiswa->angkatan
```

---

#### ⚠️ ActivityLog Dibuat Tapi Tidak Digunakan Maksimal

Model `ActivityLog` sudah ada, tetapi hanya dicatat di login. Tidak ada pencatatan untuk:

- Perubahan profil
- Sinkronisasi SISKA
- Akses endpoint sensitif

**Rekomendasi:** Buat trait `Loggable` atau middleware untuk auto-log setiap request sensitif.

```php
// Middleware baru: LogActivity
public function handle($request, $next) {
    $response = $next($request);

    if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $request->method(),
            'endpoint' => $request->path(),
            'ip_address' => $request->ip(),
        ]);
    }
    return $response;
}
```

---

#### ⚠️ Error Response Tidak Konsisten

Beberapa endpoint return `404` dengan struktur `{'status': false, ...}`, yang lain return `401` dengan struktur berbeda.

**Rekomendasi:** Buat base exception handler yang standardisasi response:

```php
// app/Http/Responses/ApiResponse.php
class ApiResponse {
    public static function success($data, $message = 'Success', $code = 200) {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message, $code = 400, $errors = null) {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}

// Pakai di setiap controller:
return ApiResponse::success($user, 'Login berhasil');
```

---

#### ⚠️ Model Tidak Ada Accessor/Mutator untuk Data Sensitif

Contoh: `Mahasiswa->sandi` di-hide di `$hidden`, tapi tidak ada `getNameAttribute()` untuk format nama konsisten.

**Rekomendasi:** Tambah accessor untuk format data:

```php
// Di Model Mahasiswa
public function getNamaAttribute() {
    return ucwords(strtolower($this->nama_mahasiswa));
}

public function getAngkatanAttribute() {
    return substr($this->nim, 0, 2);
}
```

---

### 12.3 Struktur Database — Observasi

#### ✅ Normalisasi Baik

Tabel sudah well-normalized:

- `mahasiswa` dan `dosen` terpisah
- `krs` dan `krs_detail` terpisah (1:N)
- `kurikulum` dan `kurikulum_angkatan` terpisah

#### ⚠️ Potensi Redundansi

- `krs_detail.kode_matakuliah` + `krs_detail.id_matakuliah` (redundan)
- `kurikulum.sks_teori` + `kurikulum.sks_praktek` bisa disederhanakan jadi 1 kolom `skS` dengan tipe `json`

#### ⚠️ Tidak Ada Timestamp Auto

Tidak ada `created_at`, `updated_at`, `deleted_at` di beberapa tabel penting. Ini membuat audit trail mustahil dan query time-based kompleks.

**Rekomendasi:** Gunakan Laravel trait `Timestamps` dan `SoftDeletes` di semua tabel utama.

---

## 13. SECURITY AUDIT

### 13.1 Keamanan Yang Sudah Diterapkan ✅

| Kontrol          | Implementasi                      |
| ---------------- | --------------------------------- |
| CSRF Token       | Middleware `sanctum.spa` aktif    |
| Rate Limiting    | Throttle 6/menit pada login       |
| Password Hashing | `Hash::check()` di AuthController |
| SQL Injection    | ORM Eloquent + parameter binding  |
| Session Security | Cookie httpOnly, SameSite         |
| Soft Delete      | Data tidak benar-benar dihapus    |
| Multi-Guard      | Session terpisah per role         |

### 13.2 Keamanan Yang Perlu Ditambahkan ⚠️

| Risiko                                            | Severity    | Solusi                              |
| ------------------------------------------------- | ----------- | ----------------------------------- |
| Staf login tidak berfungsi (debug code)           | 🔴 CRITICAL | Hapus `die;` statement              |
| Password default hardcoded                        | 🔴 CRITICAL | Generate random password            |
| Tidak ada MFA/2FA                                 | 🟠 HIGH     | Implementasikan TOTP                |
| Domain config hanya lokal                         | 🟠 HIGH     | Tambah domain prod ke `.env`        |
| Tidak ada rate limiting global                    | 🟠 HIGH     | Tambah middleware `throttle` global |
| Tidak ada validation endpoint untuk exported data | 🟠 HIGH     | Validate semua output               |
| Tidak ada API key untuk aplikasi mobile           | 🟡 MEDIUM   | Implementasikan API key + OAuth2    |
| Activity log tidak lengkap                        | 🟡 MEDIUM   | Log semua sensitive actions         |
| Tidak ada encryption untuk transit                | 🟡 MEDIUM   | Enforce HTTPS + TLS                 |
| Dependency vulnerable                             | 🟡 MEDIUM   | Run `composer audit` regular        |

---

## 14. PERFORMANCE ANALYSIS

### 14.1 Query yang Potensial Lambat

#### Endpoint `GET /api/staff/mahasiswa` tanpa filter

```php
$mahasiswa = Mahasiswa::paginate(20);
```

**Analisis:**

- Jika ada 100K+ mahasiswa, scan tabel penuh
- Tidak ada index di kolom yang sering difilter (nim, program_studi_kode)

**Rekomendasi:**

```php
// Harus ada index di database
Schema::table('mahasiswa', function (Blueprint $table) {
    $table->index('program_studi_kode');
    $table->index('nama_mahasiswa'); // untuk search
});

// Di controller, always add pagination + default filter
$mahasiswa = Mahasiswa::where('program_studi_kode', auth()->user()->program_studi_kode)
    ->paginate(20);
```

---

#### Endpoint `GET /api/mhs/krs` dengan multiple joins

```php
// Di ServiceKRS, ada 4-5 join:
// mahasiswa → program_studi → krs → krs_detail → matakuliah
```

**Analisis:**

- Jika satu semester ada 10-15 matakuliah, query bisa menjadi 50+ row sebelum group
- N+1 problem jika tidak pakai eager loading

**Rekomendasi:**

```php
// Gunakan eager loading
$krs = Krs::where('nim', $nim)
    ->with(['krsDetail.matakuliah', 'tahunAkademik'])
    ->first();
```

---

### 14.2 Database Connection

Tidak ada connection pooling atau caching. Setiap request buat connection baru ke MySQL. Dengan 100+ concurrent users, ini bisa menjadi bottleneck.

**Rekomendasi:**

- Implementasikan Redis untuk session + cache
- Setup MySQL connection pooling (ProxySQL / MaxScale)

---

### 14.3 API Response Size

Response dari `/api/mhs/kurikulum` mengembalikan **seluruh kurikulum** sekaligus dalam satu JSON. Jika ada 150 matakuliah, response bisa 50KB+.

**Rekomendasi:**

- Pagination: `/api/mhs/kurikulum?page=1&per_page=20`
- Lazy load per semester

---

## 15. ROADMAP PENGEMBANGAN SELANJUTNYA

### Phase 1: Stabilisasi (1-2 minggu)

- [ ] Hapus debug code dari `AuthController`
- [ ] Fix password default mahasiswa
- [ ] Konfigurasi domain production di Sanctum
- [ ] Run `composer audit` dan update vulnerable packages

### Phase 2: Enhancement Akademik (3-4 minggu)

- [ ] Implementasi endpoint `/api/dosen/perwalian` (lihat mahasiswa bimbingan)
- [ ] Implementasi endpoint `/api/dosen/mengajar` (jadwal mengajar)
- [ ] Tambah pagination di `/api/staff/mahasiswa`
- [ ] Tambah filter advanced (search by nama, NIK, angkatan)

### Phase 3: Keamanan & Logging (2-3 minggu)

- [ ] Implementasi MFA/2FA via TOTP
- [ ] Comprehensive audit logging middleware
- [ ] API key authentication untuk aplikasi mobile
- [ ] Setup HTTPS + SSL certificate

### Phase 4: Data Export & Integrasi (2-3 minggu)

- [ ] Export KRS/KHS ke PDF
- [ ] Export transkip nilai ke PDF/Excel
- [ ] Integrasi dengan sistem email untuk notifikasi
- [ ] Webhook untuk sinkronisasi real-time dengan SISKA

### Phase 5: Testing & Dokumentasi (1-2 minggu)

- [ ] Unit tests untuk Services
- [ ] Integration tests untuk endpoints
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Deployment guide

---

## 16. KESIMPULAN

### Apa yang Sudah Dikerjakan dengan Baik

1. **Arsitektur Multi-Role Solid** — Implementasi guard terpisah sangat clean
2. **Service Layer Pattern** — Logika bisnis terpisah dari HTTP concern
3. **Keamanan Dasar** — CSRF, rate limiting, password hashing sudah ada
4. **Database Struktur** — Normalisasi baik, relasi jelas

### Apa yang Perlu Diperbaiki Urgent

1. **🔴 BUG KRITIS:** Debug code `die;` di staff login → blokir fitur staf
2. **🔴 KEAMANAN:** Password default `'password'` → semua akun baru berisiko
3. **🟠 PRODUCTION-READY:** Domain stateful hanya lokal → tidak bisa deployment

### Apa yang Bisa Ditambahkan untuk Maturity

1. MFA/2FA untuk keamanan extra
2. Comprehensive logging untuk audit
3. Endpoint perwalian dosen (sudah ada strukturnya di model)
4. Export PDF/Excel transkip
5. Mobile API dengan OAuth2

### Estimasi Keseluruhan

- **Code Quality:** 7/10 (baik, tapi ada beberapa rough edge)
- **Security:** 6/10 (dasar sudah ada, tapi perlu hardening)
- **Scalability:** 5/10 (single instance, perlu caching + load balancing)
- **Maintainability:** 8/10 (service layer + model relation jelas)
- **Documentation:** 4/10 (dokumentasi minimal di code)

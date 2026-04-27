# ANALISIS MENDALAM — API_SISKA_KEDOKTERAN

> Dokumen ini merupakan analisis menyeluruh tentang kapabilitas, arsitektur, alur kerja, serta kelemahan sistem.

---

## 1. GAMBARAN UMUM SISTEM

**API_SISKA_KEDOKTERAN** adalah sistem backend berbasis **Laravel 12** yang berfungsi sebagai jembatan antara aplikasi front-end (SPA) dengan data akademik Program Studi Kedokteran. Sistem ini juga terhubung ke sistem eksternal bernama **SISKA** (Sistem Informasi Akademik) untuk sinkronisasi data.

| Komponen | Detail |
|---|---|
| Framework | Laravel 12 |
| Database | MySQL |
| Autentikasi | Laravel Sanctum (Cookie-based session) |
| Pola API | RESTful JSON |
| Bahasa | PHP 8.x |
| Frontend Target | SPA (React/Vue/Blade) |

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

| Guard | Model | Digunakan Untuk |
|---|---|---|
| `web` | `User` | Default Laravel |
| `mahasiswa_web` | `Mahasiswa` | Session mahasiswa |
| `dosen_web` | `Dosen` | Session dosen |
| `staff_web` | `User` (role=staff) | Session staf |

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

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/auth/csrf-cookie` | Ambil CSRF token |
| POST | `/auth/mhs/login` | Login mahasiswa |
| POST | `/auth/dosen/login` | Login dosen |
| POST | `/auth/staff/login` | Login staf ⚠️ ADA BUG |

### 4.2 Endpoint Mahasiswa (Protected: `auth:mahasiswa_web`)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mhs/me` | Data mahasiswa saat ini |
| GET | `/api/mhs/profile` | Profil lengkap + daftar provinsi |
| PUT | `/api/mhs/profile/update` | Update profil mahasiswa |
| GET | `/api/mhs/menu` | Daftar menu/hak akses |
| GET | `/api/mhs/kurikulum` | Kurikulum sesuai angkatan |
| GET | `/api/mhs/krs?tahun_akademik=&semester=` | Kartu Rencana Studi |
| GET | `/api/mhs/khs?tahun_akademik=&semester=` | Kartu Hasil Studi |
| GET | `/api/mhs/petikannilai` | Petikan nilai seluruh semester |
| POST | `/api/mhs/logout` | Logout |

### 4.3 Endpoint Dosen (Protected: `auth:dosen_web`)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/dosen/me` | Data dosen saat ini |
| GET | `/api/dosen` | Daftar dosen (paginate 20) |
| GET | `/api/dosen/detail?kode_dosen=` | Detail satu dosen |
| PUT | `/api/dosen` | Update data dosen |
| POST | `/api/dosen/logout` | Logout |

### 4.4 Endpoint Staf (Protected: `auth:staff_web`)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/staff/me` | Data staf saat ini |
| GET | `/api/staff/mahasiswa?nim=&kode_prodi=&angkatan=` | Cari mahasiswa |
| GET | `/api/staff/kurikulum/nama` | Semua nama kurikulum |

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

| Model/Tabel | Indikasi Fitur | Status |
|---|---|---|
| `Mengajar` | Jadwal mengajar dosen | Model ada, controller/endpoint belum ada |
| `KelasMahasiswa` | Mahasiswa masuk kelas mana | Model ada, endpoint belum ada |
| `Kelas` + `NamaKelas` | Manajemen kelas | Model ada, Admin controller ada, API endpoint belum ada |
| `Api_Dosen/PerwalianController` | Perwalian dosen-mahasiswa | Controller ada tapi mungkin kosong/belum lengkap |
| `ApiSection` + `ApiEndpoint` | Dokumentasi API dinamis | Model ada, belum jelas digunakan untuk apa |

---

## 10. RINGKASAN KAPABILITAS vs KETERBATASAN

### Yang Sudah Bisa Dilakukan

| Fitur | Status |
|---|---|
| Login multi-role (mahasiswa, dosen, staf) | ✅ Berfungsi (kecuali staf ada bug) |
| Lihat KRS per semester | ✅ Berfungsi |
| Lihat KHS per semester | ✅ Berfungsi |
| Lihat kurikulum berdasarkan angkatan | ✅ Berfungsi |
| Lihat petikan nilai | ✅ Berfungsi |
| Update profil mahasiswa | ✅ Berfungsi |
| Update profil dosen | ✅ Berfungsi |
| Cari mahasiswa (staf) | ✅ Berfungsi (setelah bug login staf diperbaiki) |
| Sinkronisasi data dari SISKA | ✅ Berfungsi di admin panel |
| CSRF protection | ✅ Aktif |
| Rate limiting login | ✅ Aktif |
| Enkripsi ID sensitif | ✅ Aktif |

### Yang Belum Bisa Dilakukan

| Fitur | Status |
|---|---|
| Login staf via API | ❌ Bug kritis (debug code) |
| Tambah/ubah KRS oleh mahasiswa | ❌ Belum ada |
| Input/edit nilai KHS | ❌ Belum ada |
| Approve KRS oleh dosen | ❌ Belum ada |
| Jadwal mengajar dosen | ❌ Belum ada endpoint |
| Notifikasi (email/push) | ❌ Belum ada |
| Export PDF/Excel (transkip, KRS) | ❌ Belum ada |
| Siap production (domain config) | ❌ Belum dikonfigurasi |
| Audit trail / logging | ❌ Belum ada |
| Pagination endpoint staff/mahasiswa | ❌ Belum ada |

---

## 11. PRIORITAS PERBAIKAN

| Prioritas | Masalah | Lokasi |
|---|---|---|
| 🔴 KRITIS | Hapus debug code `echo json_encode($user);die;` | `AuthController.php` ~baris 88 |
| 🔴 KRITIS | Konfigurasi domain production di Sanctum | `config/sanctum.php` / `.env` |
| 🟠 TINGGI | Password default mahasiswa tidak aman | Admin/MahasiswaController.php |
| 🟠 TINGGI | Tambah pagination di endpoint staff/mahasiswa | `Api_Staff/AkademikController.php` |
| 🟡 SEDANG | Implementasi endpoint perwalian dosen | `Api_Dosen/PerwalianController.php` |
| 🟡 SEDANG | Tambah logging aktivitas | Middleware baru |
| 🟢 RENDAH | Konsolidasi middleware role | Hapus duplikasi EnsureIsAdmin/EnsureIsStaff |
| 🟢 RENDAH | Bersihkan kolom legacy `kode_matakuliah` | Database migration |

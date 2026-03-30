# Migrations & Setup Log — API SISKA KEDOKTERAN

## Daftar Migration yang Dibuat

| No  | File Migration                                              | Tabel                    | Keterangan                                                                                    |
| --- | ----------------------------------------------------------- | ------------------------ | --------------------------------------------------------------------------------------------- |
| 1   | `2026_03_13_000000_create_mahasiswa_table.php`              | `mahasiswa`              | Data mahasiswa lengkap (NIm, NIK, NPM, NISN, biodata, status, dll). Includes `softDeletes()`. |
| 2   | `2026_03_13_000001_create_tahun_akademik_table.php`         | `tahun_akademik`         | Tahun akademik dan semester.                                                                  |
| 3   | `2026_03_13_000002_create_matakuliah_table.php`             | `matakuliah`             | Data matakuliah semua jurusan.                                                                |
| 4   | `2026_03_13_000003_create_program_studi_table.php`          | `program_studi`          | Data program studi.                                                                           |
| 5   | `2026_03_13_000004_create_nama_kurikulum_table.php`         | `nama_kurikulum`         | Nama pengenal kurikulum per prodi. FK → `program_studi`.                                      |
| 6   | `2026_03_13_000005_create_kurikulum_table.php`              | `kurikulum`              | Matakuliah per kurikulum. FK → `nama_kurikulum`.                                              |
| 7   | `2026_03_13_000006_create_dosen_table.php`                  | `dosen`                  | Data dosen. FK `homebase` → `program_studi`.                                                  |
| 8   | `2026_03_13_000007_create_krs_table.php`                    | `krs`                    | Kartu Rencana Studi. FK → `tahun_akademik`.                                                   |
| 9   | `2026_03_13_000008_create_krs_detail_table.php`             | `krs_detail`             | Detail KRS per matakuliah. FK → `krs`, `matakuliah`.                                          |
| 10  | `2026_03_13_000009_create_khs_detail_table.php`             | `khs_detail`             | Nilai KHS mahasiswa. FK → `krs_detail`.                                                       |
| 11  | `2026_03_13_000010_create_personal_access_tokens_table.php` | `personal_access_tokens` | Token Sanctum dengan `expires_at`.                                                            |

---

## Perbaikan yang Dilakukan

### Timestamps

- Semua migration awalnya menggunakan `timestamp('created_at')->useCurrent()` manual.
- Diperbaiki ke **`$table->timestamps()`** standar Laravel agar kompatibel dengan Eloquent (`created_at` & `updated_at` otomatis).

### Soft Deletes

- Tabel `mahasiswa` ditambahkan **`$table->softDeletes()`** karena menyimpan data mahasiswa yang mungkin di-nonaktifkan tanpa dihapus permanen.

### Foreign Key Type Mismatch

- `nama_kurikulum.kode_program_studi`: diubah dari `smallInteger` → **`unsignedSmallInteger`** agar kompatibel dengan FK ke `program_studi.kode_program_studi` (primary key `smallIncrements` = unsigned).
- `kurikulum.kode_nama_kurikulum`: diubah dari `smallInteger` → **`unsignedSmallInteger`** agar kompatibel dengan FK ke `nama_kurikulum.kode_nama_kurikulum`.
- `krs_detail.id_matakuliah`: menggunakan `unsignedInteger` agar kompatibel dengan FK ke `matakuliah.id_matakuliah` (`increments` = unsigned).

---

## Konfigurasi Sanctum (Cookie-Based SPA)

### Langkah Setup Sanctum Cookie

1. **Install Sanctum** (jika belum):

    ```bash
    composer require laravel/sanctum
    ```

2. **Publish config Sanctum:**

    ```bash
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag="config"
    ```

3. **Tambahkan env di `.env`:**

    ```env
    SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000
    SESSION_DRIVER=cookie
    SESSION_DOMAIN=localhost
    ```

4. **Daftarkan middleware Sanctum di `bootstrap/app.php`:**

    ```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ```

5. **Tambahkan `HasApiTokens` di model yang digunakan (contoh Dosen/Mahasiswa):**

    ```php
    use Laravel\Sanctum\HasApiTokens;

    class Dosen extends Model
    {
        use HasApiTokens;
    }
    ```

6. **Jalankan migrasi:**
    ```bash
    php artisan migrate
    ```

---

## Urutan Migrasi (Dependency Order)

```
program_studi
    └── nama_kurikulum
            └── kurikulum
    └── dosen
tahun_akademik
    └── krs
            └── krs_detail
                    └── khs_detail
matakuliah
    └── krs_detail
personal_access_tokens
mahasiswa
```

---

## Perintah Berguna

```bash
# Jalankan semua migrasi dari awal
php artisan migrate:fresh

# Jalankan migrasi baru saja
php artisan migrate

# Rollback semua
php artisan migrate:rollback

# Cek status migrasi
php artisan migrate:status
```

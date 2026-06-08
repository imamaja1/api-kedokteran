# Standardisasi Encrypted Code Pattern

## Latar Belakang

Project API SISKA KEDOKTERAN (Laravel 12) menggunakan pattern **encrypted ID** — semua primary key di-encrypt dengan `Crypt::encryptString()` sebelum dikirim ke client sebagai field `code`, dan client mengirimkan `code` ini kembali untuk operasi UPDATE/DELETE.

**MASALAH:** Implementasinya INKONSISTEN antar entity:
- ✅ **Dosen, TahunAkademik, NamaKurikulum, KurikulumAngkatan** — POST/PUT/DELETE sudah return `code`
- ❌ **Mahasiswa, Matakuliah, ProgramStudi** — POST/PUT/DELETE TIDAK return `code`, client harus GET ulang

## Tujuan

Bikin semua entity **konsisten**: setiap POST (store), PUT (update), DELETE (destroy) WAJIB return `code` di response data.

## Cara Kerja (Step-by-step)

### 1. Buat trait `app/Models/Traits/HasCode`

```php
<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

trait HasCode
{
    public function toCode(): string
    {
        return Crypt::encryptString($this->{$this->getCodeKey()});
    }

    public static function findByCode(string $code): ?static
    {
        try {
            $id = Crypt::decryptString($code);
            return static::find($id);
        } catch (DecryptException) {
            return null;
        }
    }

    protected function getCodeKey(): string
    {
        return $this->primaryKey;
    }
}
```

### 2. Apply trait `HasCode` ke semua model berikut

| Model | File Path | Primary Key |
|-------|-----------|-------------|
| Mahasiswa | `app/Models/Mahasiswa.php` | `nim` |
| Dosen | `app/Models/Dosen.php` | `kode_dosen` |
| Matakuliah | `app/Models/Matakuliah.php` | `id_matakuliah` |
| ProgramStudi | `app/Models/ProgramStudi.php` | `kode_program_studi` |
| TahunAkademik | `app/Models/TahunAkademik.php` | `kode_tahun_akademik` |
| NamaKurikulum | `app/Models/NamaKurikulum.php` | `kode_nama_kurikulum` |
| KurikulumAngkatan | `app/Models/KurikulumAngkatan.php` | `kode_kurikulum_angkatan` |

Caranya: tambahkan `use App\Models\Traits\HasCode;` di bagian atas file, lalu `use HasCode;` di dalam class.

### 3. Update 3 Service — tambah `code` di response POST/PUT/DELETE

#### a. `app/Service/ServiceMahasiswa.php`

**storeMahasiswa:**
```php
// SEBELUM:
return ApiResponse::success([
    'nim' => $mahasiswa->nim,
    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
], 'Mahasiswa berhasil dibuat', 201);

// SESUDAH:
return ApiResponse::success([
    'code' => $mahasiswa->toCode(),
    'nim' => $mahasiswa->nim,
    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
], 'Mahasiswa berhasil dibuat', 201);
```

**updateMahasiswa:** tambah `'code' => $mahasiswa->toCode()`.
**deleteMahasiswa:** tambah `'code' => $mahasiswa->toCode()`.
**restoreMahasiswa:** tambah `'code' => $mahasiswa->toCode()`.
**forceDeleteMahasiswa:** tambah `'code' => $mahasiswa->toCode()`.

#### b. `app/Service/ServiceMatakuliah.php`

**storeMatakuliah:**
```php
// SEBELUM:
'data' => $matakuliah->only('kode_matakuliah', 'nama_matakuliah', 'sks_teori', 'sks_praktik', 'block'),

// SESUDAH:
'data' => [
    'code' => $matakuliah->toCode(),
    'kode_matakuliah' => $matakuliah->kode_matakuliah,
    'nama_matakuliah' => $matakuliah->nama_matakuliah,
    'sks_teori' => $matakuliah->sks_teori,
    'sks_praktik' => $matakuliah->sks_praktik,
    'block' => (bool) $matakuliah->block,
],
```

**updateMatakuliah:** ganti `->only(...)` dengan array eksplisit + `code`.
**deleteMatakuliah:** ganti `->only(...)` dengan array eksplisit + `code`.

#### c. `app/Service/ServiceProgramStudi.php`

**storeProgramStudi:**
```php
// SEBELUM:
'data' => [
    'nama_program_studi' => $programStudi->nama_program_studi,
    'singkatan_program_studi' => $programStudi->singkatan_program_studi,
    'kompetensi' => $programStudi->kompetensi,
],

// SESUDAH:
'data' => [
    'code' => $programStudi->toCode(),
    'nama_program_studi' => $programStudi->nama_program_studi,
    'singkatan_program_studi' => $programStudi->singkatan_program_studi,
    'kompetensi' => $programStudi->kompetensi,
],
```

**updateProgramStudi:** tambah `code`.
**deleteProgramStudi:** tambah `code`.

### 4. Refactor service yang SUDAH punya code — ganti `Crypt::encryptString()` manual dengan `$model->toCode()`

| File | Cari | Ganti |
|------|------|-------|
| `app/Service/ServiceDosen.php` | `Crypt::encryptString($dosen->kode_dosen)` | `$dosen->toCode()` |
| `app/Service/ServiceTahunAkademik.php` | `Crypt::encryptString($tahunAkademik->kode_tahun_akademik)` | `$tahunAkademik->toCode()` |
| `app/Service/ServiceKurikulum.php` | `Crypt::encryptString($namaKurikulum->kode_nama_kurikulum)` | `$namaKurikulum->toCode()` |
| `app/Service/ServiceKurikulumAngkatan.php` | `Crypt::encryptString($kurikulumAngkatan->kode_kurikulum_angkatan)` | `$kurikulumAngkatan->toCode()` |

### 5. (Opsional) Clean-up Controller — pake `findByCode()` biar gak repetitive

Contoh di `MatakuliahController.php`:
```php
// SEBELUM (destroy):
public function destroy(string $code): JsonResponse
{
    $id = Crypt::decryptString($code);
    return $this->service->deleteMatakuliah($id);
}

// SESUDAH:
public function destroy(string $code): JsonResponse
{
    $matakuliah = Matakuliah::findByCode($code);
    if (! $matakuliah) {
        return response()->json(['status' => false, 'message' => 'Invalid code'], 422);
    }
    return $this->service->deleteMatakuliah($matakuliah->id_matakuliah);
}
```

## Kriteria Sukses

1. `php artisan test` — semua test masih PASS
2. POST `/api/staff/master-data/mahasiswa` → response mengandung field `code`
3. POST `/api/staff/master-data/matakuliah` → response mengandung field `code`
4. POST `/api/staff/master-data/program-studi` → response mengandung field `code`
5. PUT/DELETE untuk Mahasiswa, Matakuliah, ProgramStudi → response mengandung field `code`
6. Tidak ada `Crypt::encryptString` dipanggil langsung di Service layer — semua lewat `$model->toCode()`

## Ringkasan File yang Dimodifikasi

| # | File | Perubahan |
|---|------|-----------|
| 1 | `app/Models/Traits/HasCode.php` | **BARU** — trait |
| 2 | `app/Models/Mahasiswa.php` | tambah `use HasCode` |
| 3 | `app/Models/Dosen.php` | tambah `use HasCode` |
| 4 | `app/Models/Matakuliah.php` | tambah `use HasCode` |
| 5 | `app/Models/ProgramStudi.php` | tambah `use HasCode` |
| 6 | `app/Models/TahunAkademik.php` | tambah `use HasCode` |
| 7 | `app/Models/NamaKurikulum.php` | tambah `use HasCode` |
| 8 | `app/Models/KurikulumAngkatan.php` | tambah `use HasCode` |
| 9 | `app/Service/ServiceMahasiswa.php` | tambah `code` di store/update/delete/restore/forceDelete |
| 10 | `app/Service/ServiceMatakuliah.php` | tambah `code` di store/update/delete |
| 11 | `app/Service/ServiceProgramStudi.php` | tambah `code` di store/update/delete |
| 12 | `app/Service/ServiceDosen.php` | refactor pake `$dosen->toCode()` |
| 13 | `app/Service/ServiceTahunAkademik.php` | refactor pake `$tahunAkademik->toCode()` |
| 14 | `app/Service/ServiceKurikulum.php` | refactor pake `$namaKurikulum->toCode()` |
| 15 | `app/Service/ServiceKurikulumAngkatan.php` | refactor pake `$kurikulumAngkatan->toCode()` |

## Catatan Penting

- **JANGAN** ubah response format selain nambah field `code`. Field lain harus tetap sama.
- **JANGAN** ubah cara controller decrypt `code` — biarkan tetap pakai `Crypt::decryptString()` di controller. Step 5 (findByCode di controller) hanya opsional.
- **JANGAN** ubah route definition — routes tetap seperti sekarang.
- import `Crypt` tetap dibutuhkan untuk controller (decrypt), namun bisa dihapus dari service jika sudah tidak ada `Crypt::encryptString` di service.
- Import `HasCode` yang benar: `use App\Models\Traits\HasCode;`

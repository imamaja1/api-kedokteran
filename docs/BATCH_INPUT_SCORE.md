# 📖 Dokumentasi Input Nilai Sekelas (Bulk / Batch Input)

> **Endpoint:** `POST /api/dosen/assessment-dosen/input-score`  
> **Auth Guard:** `dosen_web` (Sanctum SPA Session Cookie)  
> **Akses:** Dosen Pengampu yang ditugaskan pada node penilaian terkait  

---

## 📌 Ringkasan Fitur
Endpoint ini dirancang untuk mencatat nilai mahasiswa pada template penilaian (*Assessment Dosen*). Endpoint ini adalah **cara paling efisien** untuk menyimpan nilai banyak mahasiswa sekaligus (misal input nilai 1 kelas berisi 30–50 mahasiswa) dalam **1 kali request HTTP** dan dibungkus dalam **1 transaksi database atomik** (`DB::beginTransaction`).

Setiap mahasiswa memiliki **nilainya sendiri-sendiri** (*individual score*) sesuai inputan dosen pada baris tabel nilai.

---

## 🔐 Headers & Autentikasi

| Header Key | Nilai | Keterangan |
| :--- | :--- | :--- |
| `Accept` | `application/json` | Wajib |
| `Content-Type` | `application/json` | Wajib |
| `X-XSRF-TOKEN` | `{{XSRF_TOKEN}}` | Diambil otomatis dari cookie browser (Sanctum SPA) |
| `Cookie` | `laravel_session=...; XSRF-TOKEN=...` | Session cookie login dosen |

---

## 📦 Format Request (Payload)

Endpoint ini sangat fleksibel dan mendukung 3 format payload sekaligus sesuai kebutuhan UI Frontend:

### ⭐ Format 1: Multi-Component Matrix (Input Semua Komponen Sekaligus)
Format ini digunakan saat dosen pengampu/penilai tunggal menginput **seluruh nilai komponen sekaligus** (misalnya Tugas, UTS, UAS, Praktikum) untuk seluruh mahasiswa di kelas dalam satu layar tabel matriks:

```json
{
  "assessment_dosen_id": 12,
  "mahasiswa": [
    {
      "code_mahasiswa": "eyJpdiI6IlhYWC4uLiIs...",
      "scores": [
        { "node_key": "tugas", "score": 85 },
        { "node_key": "uts", "score": 80 },
        { "node_key": "uas", "score": 90 }
      ],
      "catatan": "Catatan evaluasi menyeluruh untuk mahasiswa ini"
    },
    {
      "code_mahasiswa": "eyJpdiI6IllZWS4uLiIs...",
      "scores": [
        { "node_key": "tugas", "score": 90 },
        { "node_key": "uts", "score": 88 },
        { "node_key": "uas", "score": 92 }
      ]
    }
  ]
}
```

> 💡 **Auto-resolution `code_kelas`:** Jika frontend hanya memiliki `code_kelas`, frontend dapat mengirim `"code_kelas": "..."` sebagai pengganti `assessment_dosen_id`. Sistem backend akan otomatis mencari `assessment_dosen_id` terkait.

---

### ⭐ Format 2: Flat Single-Component (Input Per Kolom Nilai)
Digunakan jika UI dosen menginput nilai per satu komponen tertentu (misalnya hanya menginput nilai UTS untuk seluruh mahasiswa sekelas):

```json
{
  "assessment_dosen_id": 12,
  "node_key": "uts",
  "mahasiswa": [
    {
      "code_mahasiswa": "eyJpdiI6IlhYWC4uLiIs...",
      "score": 78,
      "notes": "Cukup, perlu evaluasi bagian bab 2"
    },
    {
      "code_mahasiswa": "eyJpdiI6IllZWS4uLiIs...",
      "score": 85,
      "notes": "Bagus dan terstruktur"
    }
  ]
}
```

---

### ⭐ Format 3: Dictionary / Map Format
Format ringkas di mana komponen nilai dikirim dalam bentuk key-value object:

```json
{
  "assessment_dosen_id": 12,
  "mahasiswa": [
    {
      "code_mahasiswa": "eyJpdiI6IlhYWC4uLiIs...",
      "scores": {
        "tugas": 85,
        "uts": 80,
        "uas": 90
      }
    }
  ]
}
```

#### Parameter Body:
| Parameter | Tipe | Status | Keterangan |
| :--- | :--- | :--- | :--- |
| `assessment_dosen_id` | `integer` | **Wajib** | ID sesi assessment dosen dari endpoint `/my-nodes` |
| `node_key` | `string` | **Wajib** | Komponen penilaian (misal: `uts`, `uas`, `tugas_1`) |
| `mahasiswa` | `array` | **Wajib** | Array daftar mahasiswa (minimal 1 mahasiswa) |
| `mahasiswa.*.code_mahasiswa` | `string` | **Wajib** | Encrypted code mahasiswa |
| `mahasiswa.*.score` | `numeric` | **Wajib** | Nilai angka mahasiswa (rentang `0` s.d. `100`) |
| `mahasiswa.*.notes` | `string` | *Opsional* | Catatan atau umpan balik untuk mahasiswa tersebut |

---

### 🔄 Format Alternatif (Tetap Didukung Penuh)

#### 1. Format Nested Asli (*Backward-Compatible*)
```json
{
  "assessment_dosen_id": 12,
  "node_key": "uts",
  "mahasiswa": [
    {
      "code_mahasiswa": "eyJpdiI6IlhYWC4uLiIs...",
      "scores": [
        { "node_key": "uts", "score": 78, "notes": "Cukup" }
      ]
    }
  ]
}
```

#### 2. Format Nilai Seragam (Tugas Kelompok)
Jika ada beberapa mahasiswa yang berbagi nilai yang sama:
```json
{
  "assessment_dosen_id": 12,
  "node_key": "tugas_praktikum",
  "mahasiswa_codes": ["CODE_1", "CODE_2", "CODE_3"],
  "score": 88,
  "notes": "Kelompok 1"
}
```

---

## 📥 Format Response

### 1. Response Berhasil (HTTP 200 OK)
```json
{
  "success": true,
  "message": "Input nilai berhasil disimpan.",
  "data": {
    "node_key": "uts",
    "total_diproses": 3,
    "total_berhasil": 3,
    "detail": [
      {
        "code_mahasiswa": "eyJpdiI6IlhYWC4uLiIs...",
        "nim": "2024001",
        "nama_mahasiswa": "Budi Santoso",
        "scores_saved": 1,
        "status": "berhasil"
      },
      {
        "code_mahasiswa": "eyJpdiI6IllZWS4uLiIs...",
        "nim": "2024002",
        "nama_mahasiswa": "Siti Aminah",
        "scores_saved": 1,
        "status": "berhasil"
      },
      {
        "code_mahasiswa": "eyJpdiI6IlpaWi4uLiIs...",
        "nim": "2024003",
        "nama_mahasiswa": "Rudi Hermawan",
        "scores_saved": 1,
        "status": "berhasil"
      }
    ]
  }
}
```

### 2. Response Validasi Gagal (HTTP 422 Unprocessable Content)
Contoh jika nilai melebihi batas 100:
```json
{
  "message": "The mahasiswa.0.scores.0.score must not be greater than 100.",
  "errors": {
    "mahasiswa.0.scores.0.score": [
      "The mahasiswa.0.scores.0.score must not be greater than 100."
    ]
  }
}
```

### 3. Response Ditolak / Dosen Tidak Ditugaskan (HTTP 403 Forbidden)
```json
{
  "success": false,
  "message": "Anda tidak ditugaskan di node ini."
}
```

---

## 💻 Contoh Implementasi Frontend (Tabel Penilaian Sekelas)

Berikut contoh fungsi di frontend (React / Vue / Vanilla JS) untuk mengambil data dari tabel input dan mengirimkannya dalam 1 request efisien:

```javascript
import axios from 'axios';

/**
 * Menyimpan seluruh nilai mahasiswa di tabel penilaian kelas
 * @param {number} assessmentDosenId - ID sesi penilaian
 * @param {string} nodeKey - Komponen nilai yang dinilai (misal: 'uts')
 * @param {Array<{code_mahasiswa: string, score: number, notes?: string}>} studentList - Data dari baris tabel
 */
async function simpanNilaiTabelKelas(assessmentDosenId, nodeKey, studentList) {
  try {
    // Siapkan payload efisien (Flat Format)
    const payload = {
      assessment_dosen_id: assessmentDosenId,
      node_key: nodeKey,
      mahasiswa: studentList.map(item => ({
        code_mahasiswa: item.code_mahasiswa,
        score: parseFloat(item.score),
        notes: item.notes || null
      }))
    };

    const response = await axios.post('/api/dosen/assessment-dosen/input-score', payload, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      withCredentials: true // Wajib untuk Sanctum SPA
    });

    console.log('Berhasil disimpan:', response.data);
    alert(`Berhasil menyimpan nilai untuk ${response.data.data.total_berhasil} mahasiswa.`);
    return response.data;
  } catch (error) {
    console.error('Gagal menyimpan nilai:', error.response?.data || error.message);
    const pesanError = error.response?.data?.message || 'Terjadi kesalahan saat menyimpan nilai.';
    alert('Gagal: ' + pesanError);
    throw error;
  }
}

// ─── Contoh Pemanggilan Saat Tombol "Simpan Seluruh Nilai" Diklik ──────────────
const dataTabelMahasiswa = [
  { code_mahasiswa: "code_mhs_1", score: 78, notes: "Cukup" },
  { code_mahasiswa: "code_mhs_2", score: 85, notes: "Baik" },
  { code_mahasiswa: "code_mhs_3", score: 92, notes: "Sangat Baik" }
];

simpanNilaiTabelKelas(12, 'uts', dataTabelMahasiswa);
```

---

## ⚙️ Keuntungan Arsitektur Flat Bulk Input
1. **Performa Tinggi**: Hanya 1 round-trip HTTP request untuk seluruh mahasiswa di kelas.
2. **Transaksi Database Atomik**: Menggunakan `DB::beginTransaction()`. Jika koneksi terputus saat proses berjalan, database tidak akan menyimpan sebagian data secara acak.
---

## 🏛️ Alur Pasca Input: Validasi & Pengesahan KHS oleh Kaprodi

Setelah dosen selesai menginput seluruh skor menggunakan endpoint ini, status penilaian mahasiswa berada pada status `proses`. 

### Aturan Wewenang Pengesahan:
1. **Dosen Pengajar**: Hanya bertugas menginput skor / revisi catatan. Validasi yang dapat dilakukan dosen pengajar terbatas pada node sub-komponen non-root.
2. **Ketua Program Studi (Kaprodi)**: Memegang wewenang **mutlak** untuk validasi final pada Root Node dan penerbitan nilai ke Kartu Hasil Studi (KHS).
   - **Endpoint Validasi Individual:** `POST /api/dosen/kaprodi/assessment-dosen/validasi`
   - **Endpoint Validasi Massal Sekelas:** `POST /api/dosen/kaprodi/assessment-dosen/validasi-bulk`
   - **Endpoint Revisi Nilai:** `POST /api/dosen/kaprodi/assessment-dosen/revisi`
3. **Staff Akademik (Jalur Direct Input Tanpa Kaprodi)**:
   - Jika nilai ingin langsung dimasukkan ke KHS tanpa melalui alur dosen & Kaprodi (misal untuk kebutuhan administrasi khusus / susulan), staff menggunakan:
   - **Endpoint:** `POST /api/staff/akademik/khs/direct-input`

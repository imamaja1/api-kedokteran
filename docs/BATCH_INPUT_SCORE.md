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

### ⭐ Format Utama yang Direkomendasikan: Flat Format Per-Mahasiswa
Setiap objek mahasiswa langsung memuat nilai (`score`) dan catatan (`notes` opsional) masing-masing secara ringkas dan praktis:

**Endpoint:** `POST /api/dosen/assessment-dosen/input-score`

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
    },
    {
      "code_mahasiswa": "eyJpdiI6IlpaWi4uLiIs...",
      "score": 92,
      "notes": "Sangat memuaskan"
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
3. **Fleksibilitas Frontend**: Frontend tidak perlu repot menyusun array bertingkat `scores: [{ node_key, score }]` untuk setiap baris tabel.

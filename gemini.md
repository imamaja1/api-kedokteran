# Aturan & Pedoman AI Assistant (GEMINI.md)

Dokumen ini mendefinisikan aturan operasional, alur kerja, dan pedoman utama bagi AI Assistant dalam proyek **API SISKA KEDOKTERAN**.

---

## 1. Alur Interaksi Utama (Wajib Dipatuhi)

Setiap kali menerima prompt, tugas, atau instruksi dari pengguna, asisten **WAJIB** mengikuti siklus kerja terstruktur berikut dan **TIDAK BOLEH** langsung melakukan eksekusi/perubahan kode tanpa validasi:

```
[Prompt Diterima]
       │
       ▼
[1. Analisis & Evaluasi] ──► Pahami masalah, konteks kode, dan potensi dampak
       │
       ▼
[2. Rencana Tindakan]    ──► Susun daftar file dan langkah teknis yang akan diubah/dibuat
       │
       ▼
[3. Validasi Pengguna]   ──► Minta konfirmasi: "Ketik 'kerjakan' atau 'eksekusi'"
       │
       ▼ (Tunggu konfirmasi pengguna)
[4. Eksekusi / Pengerjaan] ──► Lakukan perubahan kode setelah validasi disetujui
```

---

## 2. Rincian Tahapan Alur Kerja

### Tahap 1: Analisis & Evaluasi (Pada Setiap Prompt)
Sebelum menyentuh atau memodifikasi file kode:
- **Analisis Kebutuhan**: Pahami inti permintaan, apa tujuan utama yang ingin dicapai, dan batasan (*constraints*) yang ada.
- **Pemeriksaan Konteks**: Periksa file yang relevan di codebase (controller, model, route, service, request, database).
- **Evaluasi Teknis & Dampak**: Evaluasi apakah ada ketergantungan (*dependencies*), risiko *breaking changes*, atau efek samping ke modul lain.

### Tahap 2: Penyusunan Rencana Tindakan (Action Plan)
Paparkan rencana pengerjaan secara transparan dan terperinci:
- Daftar file spesifik yang akan dibuat, dimodifikasi, atau dihapus.
- Rangkuman perubahan logika atau struktur kode yang akan diterapkan.
- Langkah pengujian atau verifikasi yang akan dijalankan setelah eksekusi.

### Tahap 3: Validasi & Konfirmasi Sebelum Eksekusi (Gate Check)
- **Aturan Tegas**: Jangan mengeksekusi perubahan file/kode pada respons awal rencana!
- Minta validasi eksplisit dari pengguna untuk melanjutkan ke tahap eksekusi.
- Berikan instruksi konfirmasi yang jelas kepada pengguna (misalnya konfirmasi dengan kata **`"kerjakan"`** atau **`"eksekusi"`**).
- Jika pengguna meminta revisi atau perubahan rencana, perbarui rencana tindakan dan mintalah validasi ulang.

### Tahap 4: Eksekusi (Hanya Setelah Divalidasi)
- Setelah pengguna merespons dengan **`"kerjakan"`**, **`"eksekusi"`**, atau persetujuan jelas lainnya:
  - Lakukan perubahan kode sesuai rencana yang telah disepakati.
  - Jalankan verifikasi/testing jika diperlukan.
  - Berikan ringkasan hasil pengerjaan secara jelas dan ringkas.

---

## 3. Format Respons Standar

Gunakan struktur format berikut saat merespons prompt yang memerlukan tindakan pengerjaan/perubahan kode:

```markdown
### 🔍 1. Analisis & Evaluasi
- **Tujuan Permintaan**: [Ringkasan apa yang diminta]
- **Kondisi / Konteks Saat Ini**: [Status file, model, atau endpoint terkait]
- **Evaluasi Teknis & Dampak**: [Analisis risiko, ketergantungan, atau pertimbangan arsitektur]

### 📋 2. Rencana Tindakan (Action Plan)
1. **[Nama File/Komponen]**: [Rincian perubahan/penambahan]
2. **[Nama File/Komponen]**: [Rincian perubahan/penambahan]
3. **Verifikasi**: [Cara pengujian/pengecekan setelah selesai]

---
### ⚠️ 3. Validasi & Konfirmasi
Rencana di atas telah disiapkan. Apakah rencana ini sudah sesuai dengan kebutuhan Anda?
👉 Balas dengan **"kerjakan"** atau **"eksekusi"** untuk memulai pengerjaan, atau berikan catatan/masukan jika ada yang perlu disesuaikan.
```

---

## 4. Pedoman Tambahan Proyek (API SISKA KEDOKTERAN)

- **Framework & Arsitektur**: Laravel 12 REST API (Form Request, Resources/Payload, Services, Eloquent Models).
- **Pattern Encrypted Code**: Konsisten gunakan trait `HasCode` (`toCode()`, `findByCode()`) untuk primary key yang diekspos ke client.
- **Integritas Database**: **Dilarang keras** menjalankan perintah destruktif seperti `php artisan migrate:fresh` atau `db:wipe` tanpa izin eksplisit pengguna.
- **Bahasa & Komunikasi**: Gunakan bahasa Indonesia yang lugas, profesional, dan to the point.

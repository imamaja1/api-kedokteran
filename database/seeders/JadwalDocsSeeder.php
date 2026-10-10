<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JadwalDocsSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::connection()->getSchemaBuilder()->hasTable('api_sections')) {
            return;
        }

        // 1. Section: Master Ruangan & Hari
        DB::table('api_sections')->updateOrInsert(
            ['title' => 'Staff - Master Ruangan & Hari'],
            ['sort_order' => 29, 'created_at' => now(), 'updated_at' => now()]
        );
        $sectionRuanganHariId = DB::table('api_sections')->where('title', 'Staff - Master Ruangan & Hari')->value('id');

        $endpointsRuanganHari = [
            [
                'title' => 'List Ruangan',
                'description' => 'Mendapatkan daftar master ruangan. Tiap item memuat `code` dan `code_ruangan` terenkripsi. Filter: search, jenis, status_aktif, per_page.',
                'method' => 'GET',
                'url' => '/api/staff/ruangan',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"search\"       : \"{RK}|nullable|string\",\n    \"status_aktif\" : \"true|nullable|boolean\",\n    \"jenis\"        : \"kuliah|nullable|string\",\n    \"per_page\"     : \"20|nullable|integer\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Daftar ruangan berhasil diambil.\",\n    \"data\": [\n        {\n            \"id\": 1,\n            \"code\": \"eyJpdiI6...(encrypted code)\",\n            \"code_ruangan\": \"eyJpdiI6...(encrypted code_ruangan)\",\n            \"kode_ruangan\": \"RK.01\",\n            \"nama_ruangan\": \"Ruang Kuliah 01\",\n            \"kapasitas\": 40,\n            \"jenis\": \"kuliah\",\n            \"lokasi\": \"Gedung Kedokteran Lt. 1\",\n            \"status_aktif\": true\n        }\n    ]\n}",
                'sort_order' => 1,
            ],
            [
                'title' => 'Tambah Ruangan',
                'description' => 'Menambahkan master ruangan baru.',
                'method' => 'POST',
                'url' => '/api/staff/ruangan',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"kode_ruangan\": \"RK.03|required|string|max:50|unique\",\n    \"nama_ruangan\": \"Ruang Kuliah 03|required|string|max:100\",\n    \"kapasitas\"   : \"40|required|integer|min:1\",\n    \"jenis\"       : \"kuliah|nullable|string\",\n    \"lokasi\"      : \"Gedung FK Lt. 2|nullable|string\",\n    \"status_aktif\": true\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil dibuat.\",\n    \"data\": {\n        \"id\": 6,\n        \"code\": \"eyJpdiI6...(encrypted code)\",\n        \"code_ruangan\": \"eyJpdiI6...(encrypted code_ruangan)\",\n        \"kode_ruangan\": \"RK.03\",\n        \"nama_ruangan\": \"Ruang Kuliah 03\",\n        \"kapasitas\": 40,\n        \"jenis\": \"kuliah\",\n        \"lokasi\": \"Gedung FK Lt. 2\",\n        \"status_aktif\": true\n    }\n}",
                'sort_order' => 2,
            ],
            [
                'title' => 'Detail Ruangan',
                'description' => 'Mendapatkan detail satu ruangan. Menerima encrypted `{code}` di URL parameter atau `?code=...` di query string.',
                'method' => 'GET',
                'url' => '/api/staff/ruangan/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Detail ruangan berhasil diambil.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_ruangan\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"nama_ruangan\": \"Ruang Kuliah 01\",\n        \"kapasitas\": 40,\n        \"jenis\": \"kuliah\",\n        \"lokasi\": \"Gedung Kedokteran Lt. 1\",\n        \"status_aktif\": true\n    }\n}",
                'sort_order' => 3,
            ],
            [
                'title' => 'Update Ruangan',
                'description' => 'Mengubah data master ruangan. Menerima parameter encrypted `{code}` di URL atau `code` di body payload.',
                'method' => 'PUT',
                'url' => '/api/staff/ruangan/{code}',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code\"        : \"{encrypted code}|nullable|string (jika PUT /api/staff/ruangan)\",\n    \"nama_ruangan\": \"Ruang Kuliah 01 (Renovasi)|sometimes|string\",\n    \"kapasitas\"   : \"45|sometimes|integer\",\n    \"lokasi\"      : \"Gedung Kedokteran Lt. 1|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil diperbarui.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_ruangan\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"nama_ruangan\": \"Ruang Kuliah 01 (Renovasi)\",\n        \"kapasitas\": 45,\n        \"status_aktif\": true\n    }\n}",
                'sort_order' => 4,
            ],
            [
                'title' => 'Toggle Status Ruangan',
                'description' => 'Mengaktifkan atau menonaktifkan ruangan dengan parameter encrypted `{code}`.',
                'method' => 'PATCH',
                'url' => '/api/staff/ruangan/{code}/toggle',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil dinonaktifkan.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"status_aktif\": false\n    }\n}",
                'sort_order' => 5,
            ],
            [
                'title' => 'Hapus Ruangan',
                'description' => 'Menghapus master ruangan jika belum memiliki jadwal. Menerima parameter encrypted `{code}`.',
                'method' => 'DELETE',
                'url' => '/api/staff/ruangan/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil dihapus.\",\n    \"data\": null\n}",
                'sort_order' => 6,
            ],
            [
                'title' => 'List Hari',
                'description' => 'Mendapatkan daftar hari (Senin–Minggu) beserta status aktif dan encrypted `code_hari`.',
                'method' => 'GET',
                'url' => '/api/staff/hari',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Daftar hari berhasil diambil.\",\n    \"data\": [\n        {\"id\": 1, \"code\": \"eyJpdiI6...\", \"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Senin\", \"urutan\": 1, \"status_aktif\": true},\n        {\"id\": 2, \"code\": \"eyJpdiI6...\", \"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Selasa\", \"urutan\": 2, \"status_aktif\": true},\n        {\"id\": 7, \"code\": \"eyJpdiI6...\", \"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Minggu\", \"urutan\": 7, \"status_aktif\": false}\n    ]\n}",
                'sort_order' => 7,
            ],
            [
                'title' => 'Toggle Status Hari',
                'description' => 'Mengaktifkan atau menonaktifkan hari aktif perkuliahan dengan parameter encrypted `{code}`.',
                'method' => 'PATCH',
                'url' => '/api/staff/hari/{code}/toggle',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Hari Sabtu berhasil diaktifkan.\",\n    \"data\": {\"id\": 6, \"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Sabtu\", \"status_aktif\": true}\n}",
                'sort_order' => 8,
            ],
        ];

        foreach ($endpointsRuanganHari as $ep) {
            DB::table('api_endpoints')->updateOrInsert(
                ['api_section_id' => $sectionRuanganHariId, 'url' => $ep['url'], 'method' => $ep['method']],
                array_merge($ep, ['api_section_id' => $sectionRuanganHariId, 'updated_at' => now(), 'created_at' => now()])
            );
        }

        // 2. Section: Penjadwalan Kelas
        DB::table('api_sections')->updateOrInsert(
            ['title' => 'Staff - Penjadwalan Kelas'],
            ['sort_order' => 30, 'created_at' => now(), 'updated_at' => now()]
        );
        $sectionJadwalId = DB::table('api_sections')->where('title', 'Staff - Penjadwalan Kelas')->value('id');

        $endpointsJadwal = [
            [
                'title' => 'Status Penjadwalan Kelas',
                'description' => 'Melihat status seluruh kelas kuliah (belum terjadwal vs sudah terjadwal) dengan encrypted `code_kelas`, `code_matakuliah`, `code_ruangan`, dll.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/status-kelas',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"status\": \"belum_terjadwal|sudah_terjadwal|nullable\",\n    \"semester\": \"2|nullable|string\",\n    \"code_tahun_akademik\": \"{encrypted kode_tahun_akademik}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Status penjadwalan kelas berhasil diambil.\",\n    \"data\": {\n        \"tahun_akademik\": \"2025/2026 Ganjil\",\n        \"summary\": {\n            \"total_kelas\": 6,\n            \"sudah_terjadwal\": 5,\n            \"belum_terjadwal\": 1\n        },\n        \"data\": [\n            {\n                \"kelas_id\": 11605,\n                \"code_kelas\": \"eyJpdiI6...(encrypted kelas_id)\",\n                \"nama_kelas\": \"Kelas A\",\n                \"matakuliah\": {\n                    \"id_matakuliah\": 12,\n                    \"code_matakuliah\": \"eyJpdiI6...\",\n                    \"kode_matakuliah\": \"BGKW1202\",\n                    \"nama_matakuliah\": \"Pancasila\"\n                },\n                \"jumlah_mahasiswa\": 26,\n                \"status_jadwal\": \"sudah_terjadwal\",\n                \"total_jadwal\": 1,\n                \"kapasitas_warning\": false\n            }\n        ]\n    }\n}",
                'sort_order' => 1,
            ],
            [
                'title' => 'List Jadwal Kelas',
                'description' => 'Mengambil seluruh jadwal kuliah. Mendukung filter encrypted `code_kelas`, `code_ruangan`, `code_hari`, `code_tahun_akademik`.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code_kelas\": \"{encrypted code_kelas}|nullable|string\",\n    \"code_ruangan\": \"{encrypted code_ruangan}|nullable|string\",\n    \"code_hari\": \"{encrypted code_hari}|nullable|string\",\n    \"semester\": \"2|nullable|string\",\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Daftar jadwal kelas berhasil diambil.\",\n    \"data\": [\n        {\n            \"id\": 1,\n            \"code\": \"eyJpdiI6...(encrypted code_jadwal)\",\n            \"code_jadwal\": \"eyJpdiI6...(encrypted code_jadwal)\",\n            \"kelas_id\": 11605,\n            \"code_kelas\": \"eyJpdiI6...(encrypted code_kelas)\",\n            \"nama_kelas\": \"Kelas A\",\n            \"code_ruangan\": \"eyJpdiI6...\",\n            \"code_hari\": \"eyJpdiI6...\",\n            \"code_tahun_akademik\": \"eyJpdiI6...\",\n            \"matakuliah\": {\n                \"code_matakuliah\": \"eyJpdiI6...\",\n                \"kode_matakuliah\": \"BGKW1202\",\n                \"nama_matakuliah\": \"Pancasila\"\n            },\n            \"ruangan\": {\n                \"code_ruangan\": \"eyJpdiI6...\",\n                \"kode_ruangan\": \"RK.01\",\n                \"nama_ruangan\": \"Ruang Kuliah 01\",\n                \"kapasitas\": 40\n            },\n            \"hari\": {\n                \"code_hari\": \"eyJpdiI6...\",\n                \"nama_hari\": \"Senin\",\n                \"urutan\": 1\n            },\n            \"jam_mulai\": \"08:00\",\n            \"jam_selesai\": \"09:40\",\n            \"kapasitas_warning\": false\n        }\n    ]\n}",
                'sort_order' => 2,
            ],
            [
                'title' => 'Tambah Jadwal Kelas (Encrypted code_*)',
                'description' => 'Menyusun jadwal kelas baru dengan parameter relasi terenkripsi (`code_kelas`, `code_ruangan`, `code_hari`, `code_tahun_akademik`). Otomatis cek bentrok (B-01, HTTP 409) dan peringatan kapasitas (B-03).',
                'method' => 'POST',
                'url' => '/api/staff/jadwal-kelas',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code_kelas\"   : \"{encrypted code_kelas}|required|string\",\n    \"code_ruangan\" : \"{encrypted code_ruangan}|required|string\",\n    \"code_hari\"    : \"{encrypted code_hari}|required|string\",\n    \"jam_mulai\"    : \"08:00|required|string\",\n    \"jam_selesai\"  : \"09:40|required|string\",\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\",\n    \"keterangan\"   : \"Tatap Muka di Kelas|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil dibuat.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_jadwal\": \"eyJpdiI6...\",\n        \"code_kelas\": \"eyJpdiI6...\",\n        \"nama_kelas\": \"Kelas A\",\n        \"code_ruangan\": \"eyJpdiI6...\",\n        \"ruangan\": {\"nama_ruangan\": \"Ruang Kuliah 01\", \"kapasitas\": 40},\n        \"code_hari\": \"eyJpdiI6...\",\n        \"hari\": {\"nama_hari\": \"Senin\"},\n        \"jam_mulai\": \"08:00\",\n        \"jam_selesai\": \"09:40\",\n        \"kapasitas_warning\": false\n    }\n}",
                'sort_order' => 3,
            ],
            [
                'title' => 'Detail Jadwal Kelas',
                'description' => 'Mendapatkan detail satu jadwal perkuliahan via encrypted parameter `{code}` di URL atau `?code=...`.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Detail jadwal kelas berhasil diambil.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_jadwal\": \"eyJpdiI6...\",\n        \"code_kelas\": \"eyJpdiI6...\",\n        \"nama_kelas\": \"Kelas A\",\n        \"ruangan\": {\"nama_ruangan\": \"Ruang Kuliah 01\"},\n        \"hari\": {\"nama_hari\": \"Senin\"},\n        \"jam_mulai\": \"08:00\",\n        \"jam_selesai\": \"09:40\"\n    }\n}",
                'sort_order' => 4,
            ],
            [
                'title' => 'Jadwal per Kelas Kuliah',
                'description' => 'Mengambil jadwal untuk satu kelas kuliah dengan parameter encrypted `{code}` (code_kelas).',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/kelas/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal per kelas berhasil diambil.\",\n    \"data\": {\n        \"kelas\": {\n            \"kelas_id\": 11605,\n            \"code_kelas\": \"eyJpdiI6...\",\n            \"nama_kelas\": \"Kelas A\",\n            \"jumlah_mahasiswa\": 26\n        },\n        \"total_jadwal\": 2,\n        \"jadwal_list\": [...],\n        \"jadwal_per_hari\": {\n            \"Senin\": [...],\n            \"Rabu\": [...]\n        }\n    }\n}",
                'sort_order' => 5,
            ],
            [
                'title' => 'Jadwal per Ruangan',
                'description' => 'Melihat jadwal penggunaan suatu ruangan dengan parameter encrypted `{code}` (code_ruangan).',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/ruangan/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\",\n    \"code_hari\": \"{encrypted code_hari}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal ruangan berhasil diambil.\",\n    \"data\": {\n        \"ruangan\": {\"code_ruangan\": \"eyJpdiI6...\", \"nama_ruangan\": \"Ruang Kuliah 01\"},\n        \"total_jadwal\": 3,\n        \"jadwal_per_hari\": {...}\n    }\n}",
                'sort_order' => 6,
            ],
            [
                'title' => 'Jadwal per Hari',
                'description' => 'Melihat seluruh jadwal perkuliahan pada hari tertentu dengan parameter encrypted `{code}` (code_hari).',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/hari/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal hari berhasil diambil.\",\n    \"data\": {\n        \"hari\": {\"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Senin\"},\n        \"total_jadwal\": 4,\n        \"jadwal_list\": [...]\n    }\n}",
                'sort_order' => 7,
            ],
            [
                'title' => 'Update Jadwal Kelas',
                'description' => 'Memperbarui ruangan, hari, atau jam jadwal kelas. Mendukung parameter encrypted `{code}` di URL atau `code` di body.',
                'method' => 'PUT',
                'url' => '/api/staff/jadwal-kelas/{code}',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code\": \"{encrypted code_jadwal}|nullable|string (jika PUT /api/staff/jadwal-kelas)\",\n    \"code_ruangan\": \"{encrypted code_ruangan}|sometimes|string\",\n    \"code_hari\": \"{encrypted code_hari}|sometimes|string\",\n    \"jam_mulai\": \"10:00|sometimes|string\",\n    \"jam_selesai\": \"11:40|sometimes|string\",\n    \"keterangan\": \"nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil diperbarui.\",\n    \"data\": {...}\n}",
                'sort_order' => 8,
            ],
            [
                'title' => 'Hapus Jadwal Kelas',
                'description' => 'Menghapus satu jadwal kelas dengan parameter encrypted `{code}`.',
                'method' => 'DELETE',
                'url' => '/api/staff/jadwal-kelas/{code}',
                'headers' => 'Cookie: laravel-session',
                'body' => null,
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil dihapus.\",\n    \"data\": null\n}",
                'sort_order' => 9,
            ],
            [
                'title' => 'Salin Jadwal Kelas',
                'description' => 'Menyalin jadwal ke hari lain, ruangan lain, atau kelas lain dengan parameter encrypted `{code}` dan body `code_*`.',
                'method' => 'POST',
                'url' => '/api/staff/jadwal-kelas/{code}/copy',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code_hari\": \"{encrypted code_hari}|nullable|string\",\n    \"code_ruangan\": \"{encrypted code_ruangan}|nullable|string\",\n    \"jam_mulai\": \"13:00|nullable|string\",\n    \"jam_selesai\": \"14:40|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil dibuat.\",\n    \"data\": {...}\n}",
                'sort_order' => 10,
            ],
        ];

        foreach ($endpointsJadwal as $ep) {
            DB::table('api_endpoints')->updateOrInsert(
                ['api_section_id' => $sectionJadwalId, 'url' => $ep['url'], 'method' => $ep['method']],
                array_merge($ep, ['api_section_id' => $sectionJadwalId, 'updated_at' => now(), 'created_at' => now()])
            );
        }

        // 3. Endpoint Mahasiswa: Jadwal Kuliah Mahasiswa
        $sectionMhsId = DB::table('api_sections')->where('title', 'Mahasiswa - Akademik')->value('id');
        if ($sectionMhsId) {
            DB::table('api_endpoints')->updateOrInsert(
                ['api_section_id' => $sectionMhsId, 'url' => '/api/mhs/jadwal', 'method' => 'GET'],
                [
                    'api_section_id' => $sectionMhsId,
                    'title' => 'Jadwal Kuliah Mahasiswa',
                    'description' => 'Mengambil jadwal kuliah yang diikuti oleh mahasiswa login. Mendukung filter encrypted `code_tahun_akademik` maupun ID integer.',
                    'method' => 'GET',
                    'url' => '/api/mhs/jadwal',
                    'headers' => 'Cookie: laravel-session',
                    'body' => "{\n    \"code_tahun_akademik\": \"{encrypted kode_tahun_akademik}|nullable|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kuliah mahasiswa berhasil diambil.\",\n    \"data\": {\n        \"tahun_akademik\": \"2025/2026 Ganjil\",\n        \"total_jadwal\": 5,\n        \"jadwal_per_hari\": {\n            \"Senin\": [\n                {\n                    \"code_jadwal\": \"eyJpdiI6...\",\n                    \"code_kelas\": \"eyJpdiI6...\",\n                    \"nama_kelas\": \"Kelas A\",\n                    \"matakuliah\": {\"code_matakuliah\": \"...\", \"kode_matakuliah\": \"BGKW1202\", \"nama_matakuliah\": \"Pancasila\"},\n                    \"ruangan\": {\"code_ruangan\": \"...\", \"nama_ruangan\": \"Ruang Kuliah 01\"},\n                    \"jam_mulai\": \"08:00\",\n                    \"jam_selesai\": \"09:40\"\n                }\n            ]\n        }\n    }\n}",
                    'sort_order' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

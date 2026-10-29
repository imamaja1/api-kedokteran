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
            ['sort_order' => 13, 'created_at' => now(), 'updated_at' => now()]
        );
        $sectionRuanganHariId = DB::table('api_sections')->where('title', 'Staff - Master Ruangan & Hari')->value('id');
        DB::table('api_endpoints')->where('api_section_id', $sectionRuanganHariId)->delete();

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
                'title' => 'Detail Ruangan (Query & Path Param)',
                'description' => 'Mendapatkan detail satu ruangan. Mendukung gaya master data umum via query string `?code=...` maupun path parameter `/{code}`.',
                'method' => 'GET',
                'url' => '/api/staff/ruangan/show',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Detail ruangan berhasil diambil.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_ruangan\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"nama_ruangan\": \"Ruang Kuliah 01\",\n        \"kapasitas\": 40,\n        \"jenis\": \"kuliah\",\n        \"lokasi\": \"Gedung Kedokteran Lt. 1\",\n        \"status_aktif\": true\n    }\n}",
                'sort_order' => 3,
            ],
            [
                'title' => 'Update Ruangan (Body Payload & Path Param)',
                'description' => 'Mengubah data master ruangan. Mendukung gaya master data umum `PUT /api/staff/ruangan` (kirim `code` dalam JSON body) maupun path parameter `PUT /api/staff/ruangan/{code}`.',
                'method' => 'PUT',
                'url' => '/api/staff/ruangan',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code\"        : \"{encrypted code}|required|string\",\n    \"nama_ruangan\": \"Ruang Kuliah 01 (Renovasi)|sometimes|string\",\n    \"kapasitas\"   : \"45|sometimes|integer\",\n    \"lokasi\"      : \"Gedung Kedokteran Lt. 1|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil diperbarui.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_ruangan\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"nama_ruangan\": \"Ruang Kuliah 01 (Renovasi)\",\n        \"kapasitas\": 45,\n        \"status_aktif\": true\n    }\n}",
                'sort_order' => 4,
            ],
            [
                'title' => 'Toggle Status Ruangan',
                'description' => 'Mengaktifkan atau menonaktifkan ruangan. Mendukung query param `?code=...` / body `code` maupun legacy path param `/{code}/toggle`.',
                'method' => 'PATCH',
                'url' => '/api/staff/ruangan/toggle',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Ruangan berhasil dinonaktifkan.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"kode_ruangan\": \"RK.01\",\n        \"status_aktif\": false\n    }\n}",
                'sort_order' => 5,
            ],
            [
                'title' => 'Hapus Ruangan',
                'description' => 'Menghapus master ruangan jika belum memiliki jadwal. Mendukung query param `?code=...` / body `code` maupun legacy path param `/{code}`.',
                'method' => 'DELETE',
                'url' => '/api/staff/ruangan',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
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
                'description' => 'Mengaktifkan atau menonaktifkan hari aktif perkuliahan via query param `?code=...` / body `code` maupun legacy path param `/{code}/toggle`.',
                'method' => 'PATCH',
                'url' => '/api/staff/hari/toggle',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
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
            ['sort_order' => 19, 'created_at' => now(), 'updated_at' => now()]
        );
        $sectionJadwalId = DB::table('api_sections')->where('title', 'Staff - Penjadwalan Kelas')->value('id');
        DB::table('api_endpoints')->where('api_section_id', $sectionJadwalId)->delete();

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
                'description' => 'Mendapatkan detail satu jadwal perkuliahan via query param `?code=...` maupun encrypted parameter `/{code}` di URL.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/show',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Detail jadwal kelas berhasil diambil.\",\n    \"data\": {\n        \"id\": 1,\n        \"code\": \"eyJpdiI6...\",\n        \"code_jadwal\": \"eyJpdiI6...\",\n        \"code_kelas\": \"eyJpdiI6...\",\n        \"nama_kelas\": \"Kelas A\",\n        \"ruangan\": {\"nama_ruangan\": \"Ruang Kuliah 01\"},\n        \"hari\": {\"nama_hari\": \"Senin\"},\n        \"jam_mulai\": \"08:00\",\n        \"jam_selesai\": \"09:40\"\n    }\n}",
                'sort_order' => 4,
            ],
            [
                'title' => 'Jadwal per Kelas Kuliah',
                'description' => 'Mengambil jadwal untuk satu kelas kuliah via query param `?code=...` maupun encrypted parameter `/{code}`.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/kelas',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code_kelas}|required|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal per kelas berhasil diambil.\",\n    \"data\": {\n        \"kelas\": {\n            \"kelas_id\": 11605,\n            \"code_kelas\": \"eyJpdiI6...\",\n            \"nama_kelas\": \"Kelas A\",\n            \"jumlah_mahasiswa\": 26\n        },\n        \"total_jadwal\": 2,\n        \"jadwal_list\": [...],\n        \"jadwal_per_hari\": {\n            \"Senin\": [...],\n            \"Rabu\": [...]\n        }\n    }\n}",
                'sort_order' => 5,
            ],
            [
                'title' => 'Jadwal per Ruangan',
                'description' => 'Melihat jadwal penggunaan suatu ruangan via query param `?code=...` maupun encrypted parameter `/{code}`.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/ruangan',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code_ruangan}|required|string\",\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\",\n    \"code_hari\": \"{encrypted code_hari}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal ruangan berhasil diambil.\",\n    \"data\": {\n        \"ruangan\": {\"code_ruangan\": \"eyJpdiI6...\", \"nama_ruangan\": \"Ruang Kuliah 01\"},\n        \"total_jadwal\": 3,\n        \"jadwal_per_hari\": {...}\n    }\n}",
                'sort_order' => 6,
            ],
            [
                'title' => 'Jadwal per Hari',
                'description' => 'Melihat seluruh jadwal perkuliahan pada hari tertentu via query param `?code=...` maupun encrypted parameter `/{code}`.',
                'method' => 'GET',
                'url' => '/api/staff/jadwal-kelas/hari',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code_hari}|required|string\",\n    \"code_tahun_akademik\": \"{encrypted code_tahun_akademik}|nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal hari berhasil diambil.\",\n    \"data\": {\n        \"hari\": {\"code_hari\": \"eyJpdiI6...\", \"nama_hari\": \"Senin\"},\n        \"total_jadwal\": 4,\n        \"jadwal_list\": [...]\n    }\n}",
                'sort_order' => 7,
            ],
            [
                'title' => 'Update Jadwal Kelas',
                'description' => 'Memperbarui ruangan, hari, atau jam jadwal kelas via `PUT /api/staff/jadwal-kelas` (body `code`) maupun legacy `PUT /api/staff/jadwal-kelas/{code}`.',
                'method' => 'PUT',
                'url' => '/api/staff/jadwal-kelas',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code\"         : \"{encrypted code}|required|string\",\n    \"code_ruangan\": \"{encrypted code_ruangan}|sometimes|string\",\n    \"code_hari\": \"{encrypted code_hari}|sometimes|string\",\n    \"jam_mulai\": \"10:00|sometimes|string\",\n    \"jam_selesai\": \"11:40|sometimes|string\",\n    \"keterangan\": \"nullable|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil diperbarui.\",\n    \"data\": {...}\n}",
                'sort_order' => 8,
            ],
            [
                'title' => 'Hapus Jadwal Kelas',
                'description' => 'Menghapus satu jadwal kelas via query param `?code=...` / body `code` maupun legacy path param `/{code}`.',
                'method' => 'DELETE',
                'url' => '/api/staff/jadwal-kelas',
                'headers' => 'Cookie: laravel-session',
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\"\n}",
                'response_example' => "{\n    \"status\": true,\n    \"message\": \"Jadwal kelas berhasil dihapus.\",\n    \"data\": null\n}",
                'sort_order' => 9,
            ],
            [
                'title' => 'Salin Jadwal Kelas',
                'description' => 'Menyalin jadwal ke hari lain, ruangan lain, atau kelas lain via `POST /api/staff/jadwal-kelas/copy` (body `code` dan `target_code_kelas`) maupun `POST /api/staff/jadwal-kelas/{code}/copy`.',
                'method' => 'POST',
                'url' => '/api/staff/jadwal-kelas/copy',
                'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                'body' => "{\n    \"code\": \"{encrypted code}|required|string\",\n    \"target_code_kelas\": \"{encrypted target_kelas_id}|nullable|string\",\n    \"code_hari\": \"{encrypted code_hari}|nullable|string\",\n    \"code_ruangan\": \"{encrypted code_ruangan}|nullable|string\",\n    \"jam_mulai\": \"13:00|nullable|string\",\n    \"jam_selesai\": \"14:40|nullable|string\"\n}",
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

        // 4. Update Section: Staff - Perwalian (Tambah Batch & Hapus)
        $sectionPerwalianId = DB::table('api_sections')->where('title', 'Staff - Perwalian')->value('id');
        if ($sectionPerwalianId) {
            DB::table('api_endpoints')->where('api_section_id', $sectionPerwalianId)->where('method', 'PUT')->update([
                'url' => '/api/staff/akademik/perwalian/{code}',
                'description' => 'Update data perwalian dengan encrypted code perwalian di URL path. Mendukung pengiriman `code_dosen_perwakilan: null` untuk melepas dosen perwakilan.',
                'body' => "{\n    \"code_dosen\"            : \"{encrypted atau integer ID}|nullable|string\",\n    \"code_dosen_perwakilan\" : \"{encrypted, integer ID, atau null}|nullable\",\n    \"code\"                  : \"{encrypted NIM}|nullable|string\"\n}",
                'updated_at' => now(),
            ]);

            DB::table('api_endpoints')->where('api_section_id', $sectionPerwalianId)->where('method', 'POST')->where('url', 'like', '%/perwalian/')->update([
                'url' => '/api/staff/akademik/perwalian',
                'description' => 'Buat perwalian baru. Mendukung fleksibilitas input code/nim dan encrypted code_dosen atau integer ID. Mengembalikan 201 Created.',
                'body' => "{\n    \"code\"                  : \"{encrypted NIM atau plaintext NIM}|required|string\",\n    \"code_dosen\"            : \"{encrypted kode_dosen atau ID}|required|string\",\n    \"code_dosen_perwakilan\" : \"{nullable|string}\"\n}",
                'updated_at' => now(),
            ]);

            DB::table('api_endpoints')->where('api_section_id', $sectionPerwalianId)->where('method', 'GET')->where('url', 'like', '%/perwalian/')->update([
                'url' => '/api/staff/akademik/perwalian',
                'updated_at' => now(),
            ]);

            DB::table('api_endpoints')->updateOrInsert(
                ['api_section_id' => $sectionPerwalianId, 'url' => '/api/staff/akademik/perwalian/batch', 'method' => 'POST'],
                [
                    'api_section_id' => $sectionPerwalianId,
                    'title' => 'Batch Tambah Perwalian',
                    'description' => 'Menetapkan dosen wali untuk banyak mahasiswa sekaligus dalam satu transaksi basis data.',
                    'method' => 'POST',
                    'url' => '/api/staff/akademik/perwalian/batch',
                    'headers' => "Cookie: laravel-session\nContent-Type: application/json",
                    'body' => "{\n    \"items\": [\n        {\n            \"code\": \"{encrypted NIM atau plaintext NIM}|required|string\",\n            \"code_dosen\": \"{encrypted kode_dosen atau ID}|required|string\",\n            \"code_dosen_perwakilan\": \"{nullable|string}\"\n        }\n    ]\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"2 perwalian berhasil ditambahkan.\",\n    \"data\": {\n        \"created_count\": 2,\n        \"perwalian\": [...]\n    }\n}",
                    'sort_order' => 6,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('api_endpoints')->updateOrInsert(
                ['api_section_id' => $sectionPerwalianId, 'url' => '/api/staff/akademik/perwalian/{code}', 'method' => 'DELETE'],
                [
                    'api_section_id' => $sectionPerwalianId,
                    'title' => 'Hapus Perwalian',
                    'description' => 'Menghapus data perwalian mahasiswa berdasarkan encrypted code perwalian.',
                    'method' => 'DELETE',
                    'url' => '/api/staff/akademik/perwalian/{code}',
                    'headers' => 'Cookie: laravel-session',
                    'body' => null,
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Data perwalian berhasil dihapus\",\n    \"data\": null\n}",
                    'sort_order' => 7,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Update Section: Staff - Audit Feeder PDDIKTI (5 Canonical Endpoints)
        DB::table('api_sections')->updateOrInsert(
            ['title' => 'Staff - Audit Feeder PDDIKTI'],
            ['sort_order' => 25, 'created_at' => now(), 'updated_at' => now()]
        );
        $sectionFeederId = DB::table('api_sections')->where('title', 'Staff - Audit Feeder PDDIKTI')->value('id');
        if ($sectionFeederId) {
            DB::table('api_endpoints')->where('api_section_id', $sectionFeederId)->delete();

            $endpointsFeeder = [
                [
                    'title' => 'Cek Status Koneksi Feeder',
                    'description' => 'Memeriksa ketersediaan koneksi Web Service Neo Feeder PDDIKTI dan masa aktif token.',
                    'method' => 'GET',
                    'url' => '/api/staff/feeder/status',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => null,
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Koneksi ke Feeder aktif.\",\n    \"data\": {\n        \"connected\": true,\n        \"token_valid_until\": \"2026-10-12 18:00:00\"\n    }\n}",
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Tarik Nilai Kelas (Pre-Fetch)',
                    'description' => 'Menarik data nilai kelas dari Feeder (GetDetailNilaiPerkuliahanKelas) dan menyimpannya di cache server (TTL 1 jam). Mendukung query param `?code=...` (code_kelas) maupun legacy path param `/{code}`.',
                    'method' => 'GET',
                    'url' => '/api/staff/feeder/pull/kelas',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"code\": \"{code_kelas}|required|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Data nilai kelas berhasil ditarik dari Feeder dan disimpan di cache.\",\n    \"data\": {\n        \"code_kelas\": \"eyJpdiI6...\",\n        \"source\": \"feeder_live\",\n        \"pulled_at\": \"2026-10-12T12:00:00+00:00\",\n        \"total_rows\": 45,\n        \"data\": [...]\n    }\n}",
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Audit Nilai Kelas (In-Memory Audit)',
                    'description' => 'Audit komparasi nilai kelas SISKA vs Feeder di memori (O(1)). Mendeteksi selisih angka desimal > 0.001, perbedaan huruf mutu, dan fuzzy matching matakuliah (>= 60%). Mendukung query param `?code=...` & `?refresh=1` maupun legacy path param `/{code}`.',
                    'method' => 'GET',
                    'url' => '/api/staff/feeder/audit/kelas',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"code\": \"{code_kelas}|required|string\",\n    \"refresh\": \"false|nullable|boolean\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Audit perbandingan nilai kelas berhasil diproses.\",\n    \"data\": {\n        \"summary\": {\n            \"total_mahasiswa_siska\": 45,\n            \"total_mahasiswa_feeder\": 45,\n            \"sesuai\": 44,\n            \"berbeda\": 1\n        },\n        \"audit_results\": [...]\n    }\n}",
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Tarik Transkrip Mahasiswa (Pre-Fetch)',
                    'description' => 'Menarik riwayat transkrip lengkap mahasiswa dari Feeder (GetTranskripMahasiswa) dan menyimpannya di cache server (TTL 1 jam). Mendukung query param `?nim=...` maupun legacy path param `/{nim}`.',
                    'method' => 'GET',
                    'url' => '/api/staff/feeder/pull/mahasiswa',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"nim\": \"{nim}|required|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Data transkrip mahasiswa berhasil ditarik dari Feeder dan disimpan di cache.\",\n    \"data\": {\n        \"nim\": \"25080110001\",\n        \"source\": \"feeder_live\",\n        \"pulled_at\": \"2026-10-12T12:00:00+00:00\",\n        \"total_rows\": 24,\n        \"data\": [...]\n    }\n}",
                    'sort_order' => 4,
                ],
                [
                    'title' => 'Audit Petikan Transkrip (In-Memory Audit)',
                    'description' => 'Audit petikan transkrip mahasiswa SISKA vs Feeder di memori. Menerapkan konsolidasi matakuliah retake (pilih nilai terbaik), Two-Tier Matching (Tier-1: Kode MK, Tier-2: Nama MK normalisasi), serta deteksi transfer/konversi MBKM. Mendukung query param `?nim=...` & `?refresh=1` maupun legacy path param `/{nim}`.',
                    'method' => 'GET',
                    'url' => '/api/staff/feeder/audit/mahasiswa',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"nim\": \"{nim}|required|string\",\n    \"refresh\": \"false|nullable|boolean\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Audit petikan transkrip mahasiswa berhasil diproses.\",\n    \"data\": {\n        \"summary\": {\n            \"total_matakuliah_siska\": 20,\n            \"total_matakuliah_feeder\": 20,\n            \"sesuai\": 19,\n            \"berbeda\": 1\n        },\n        \"audit_results\": [...]\n    }\n}",
                    'sort_order' => 5,
                ],
            ];

            foreach ($endpointsFeeder as $ep) {
                DB::table('api_endpoints')->updateOrInsert(
                    ['api_section_id' => $sectionFeederId, 'url' => $ep['url'], 'method' => $ep['method']],
                    array_merge($ep, ['api_section_id' => $sectionFeederId, 'updated_at' => now(), 'created_at' => now()])
                );
            }
        }

        // 6. Update Section: Staff - Assessment Dosen (Setup)
        $sectionAssessmentSetupId = DB::table('api_sections')->where('title', 'Staff - Assessment Dosen (Setup)')->value('id');
        if ($sectionAssessmentSetupId) {
            $endpointsAssessmentSetup = [
                [
                    'title' => 'Pre-check Kesiapan Assessment & Template Kelas',
                    'description' => 'Memeriksa status kesiapan kelas untuk assessment (validitas data kelas, ketersediaan template aktif mata kuliah, dan status instance AssessmentDosen). Mengembalikan HTTP 200 dengan flag diagnostik lengkap (anti-404 Nginx). Mendukung query param `?code_kelas=...`, `?code=...`, `?kelas_id=...`, route param `/{code}`, serta alias URL `/api/staff/assessment/dosen/status`.',
                    'method' => 'GET',
                    'url' => '/api/staff/assessment/dosen/check',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"code_kelas\": \"{code_kelas}|required|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Status assessment kelas berhasil diperiksa.\",\n    \"data\": {\n        \"exists\": true,\n        \"has_template\": true,\n        \"has_assessment\": true,\n        \"can_assign\": true,\n        \"action_needed\": \"ready\",\n        \"description\": \"Assessment dosen sudah aktif dan siap untuk penugasan.\",\n        \"kelas\": {\n            \"kelas_id\": 11609,\n            \"code_kelas\": \"eyJpdiI6...\",\n            \"nama_kelas\": \"A\",\n            \"semester\": \"5\",\n            \"matakuliah\": \"Blok Biomedik 1\",\n            \"kode_matakuliah\": \"KSKI1502\"\n        },\n        \"template\": {\n            \"id\": 1,\n            \"versi\": 1,\n            \"root_key\": \"pneilian_tutotiral\",\n            \"root_name\": \"Pneilian tutotiral\"\n        },\n        \"assessment_dosen_id\": 2,\n        \"assignments_count\": 0\n    }\n}",
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Inisialisasi AssessmentDosen (Bind Kelas ke Template)',
                    'description' => 'Mengikat template assessment aktif dari mata kuliah ke kelas perkuliahan. Mendukung parameter `code_kelas`, `code`, maupun `kelas_id`.',
                    'method' => 'POST',
                    'url' => '/api/staff/assessment/dosen',
                    'headers' => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"code_kelas\": \"{code_kelas}|required|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"AssessmentDosen berhasil dibuat.\",\n    \"data\": {\n        \"id\": 3,\n        \"assessment_dosen_id\": 3,\n        \"code_kelas\": \"eyJpdiI6...\",\n        \"nama_kelas\": \"A\",\n        \"matakuliah\": \"Blok Biomedik 1\"\n    }\n}",
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Lihat Struktur Pohon & Penugasan Dosen (Tree View)',
                    'description' => 'Mendapatkan struktur pohon (`tree_view`), daftar node wewenang yang tersedia (`available_nodes`), daftar dosen pengajar kelas yang valid (`dosen_pengajar`), dan contoh payload PUT siap pakai. Otomatis membuat record AssessmentDosen jika template aktif tersedia. Jika template belum tersedia, mengembalikan HTTP 422 terstruktur (bebas error 404 Nginx). Mendukung parameter `code_kelas`, `code`, `kelas_id`, path param `/{code}`, serta endpoint `/show`.',
                    'method' => 'GET',
                    'url' => '/api/staff/assessment/dosen/assignments',
                    'headers' => "Accept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"code_kelas\": \"{code_kelas}|required|string\"\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Daftar assignment berhasil diambil.\",\n    \"data\": {\n        \"assessment_dosen_id\": 2,\n        \"kelas\": {\n            \"code_kelas\": \"eyJpdiI6...\",\n            \"nama_kelas\": \"A\",\n            \"semester\": \"5\",\n            \"matakuliah\": \"Blok Biomedik 1\",\n            \"kode_matakuliah\": \"KSKI1502\"\n        },\n        \"template\": {\n            \"versi\": 1,\n            \"root_key\": \"pneilian_tutotiral\",\n            \"root_name\": \"Pneilian tutotiral\"\n        },\n        \"dosen_pengajar\": [\n            {\"kode_dosen\": 434, \"nama_dosen\": \"dr. Evan Christian Sumarno, M.Biomed\"}\n        ],\n        \"available_nodes\": [...],\n        \"tree_view\": {...},\n        \"payload_sample_put\": {\n            \"assessment_dosen_id\": 2,\n            \"assignments\": [\n                {\"node_key\": \"komponen_1\", \"kode_dosen\": 434}\n            ]\n        }\n    }\n}",
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Penugasan Dosen ke Node Kategori / Root (Plotting Dosen)',
                    'description' => 'Menugaskan dosen pengajar kelas ke node wewenang (kategori atau root). Dosen wajib terdaftar di tabel `mengajar` kelas tersebut. Mendukung nama field camelCase (`assessmentDosenId`, `nodeKey`, `kodeDosen`) dan resolusi ID otomatis via `code_kelas`.',
                    'method' => 'PUT',
                    'url' => '/api/staff/assessment/dosen/assign',
                    'headers' => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                    'body' => "{\n    \"assessment_dosen_id\": \"2|required|integer\",\n    \"assignments\": [\n        {\n            \"node_key\": \"komponen_1|required|string\",\n            \"kode_dosen\": \"434|required|integer\"\n        }\n    ]\n}",
                    'response_example' => "{\n    \"status\": true,\n    \"message\": \"Dosen berhasil ditugaskan ke node.\",\n    \"data\": [\n        {\n            \"id\": 1,\n            \"node_key\": \"komponen_1\",\n            \"kode_dosen\": 434,\n            \"nama_dosen\": \"dr. Evan Christian Sumarno, M.Biomed\"\n        }\n    ]\n}",
                    'sort_order' => 4,
                ],
            ];

            foreach ($endpointsAssessmentSetup as $ep) {
                DB::table('api_endpoints')->updateOrInsert(
                    ['api_section_id' => $sectionAssessmentSetupId, 'url' => $ep['url'], 'method' => $ep['method']],
                    array_merge($ep, ['api_section_id' => $sectionAssessmentSetupId, 'updated_at' => now(), 'created_at' => now()])
                );
            }
        }

        // 7. Sinkronisasi urutan seluruh seksi agar Staff & Dosen konsisten dan rapi
        $sectionOrderMap = [
            'Auth - Mahasiswa' => 1,
            'Auth - Staff' => 2,
            'Auth - Dosen' => 3,
            'Mahasiswa - Akademik' => 4,
            'Staff - Pencarian' => 5,
            'Staff - Master Data (Mahasiswa)' => 6,
            'Staff - Master Data (Dosen)' => 7,
            'Staff - Master Data (Matakuliah)' => 8,
            'Staff - Master Data (Prodi)' => 9,
            'Staff - Master Data (Nama Kurikulum)' => 10,
            'Staff - Master Data (Tahun Akademik)' => 11,
            'Staff - Master Data (Kurikulum Angkatan)' => 12,
            'Staff - Master Ruangan & Hari' => 13,
            'Staff - Data Default' => 14,
            'Staff - SKS Rule' => 15,
            'Staff - Akademik' => 16,
            'Staff - Perwalian' => 17,
            'Staff - Konfig Kelas' => 18,
            'Staff - Penjadwalan Kelas' => 19,
            'Staff - Pembayaran' => 20,
            'Staff - Assessment (Templates)' => 21,
            'Staff - Assessment (Scores)' => 22,
            'Staff - Assessment (Grade)' => 23,
            'Staff - Assessment Dosen (Setup)' => 24,
            'Staff - Audit Feeder PDDIKTI' => 25,
            'Dosen - Data Dosen' => 26,
            'Dosen - Kurikulum' => 27,
            'Dosen - Perwalian' => 28,
            'Dosen - Penilaian' => 29,
            'Dosen - Kaprodi' => 30,
            'Dosen - Assessment Dosen (Multi-level)' => 31,
        ];

        foreach ($sectionOrderMap as $title => $sortOrder) {
            DB::table('api_sections')
                ->where('title', $title)
                ->update(['sort_order' => $sortOrder, 'updated_at' => now()]);
        }
    }
}

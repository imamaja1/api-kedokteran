<?php

namespace Database\Seeders;

use App\Models\ApiSection;
use App\Models\ApiEndpoint;
use Illuminate\Database\Seeder;

class ApiDocSeeder extends Seeder
{
    public function run(): void
    {
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');

        $sections = [
            [
                'title'      => 'Auth Siska',
                'sort_order' => 1,
                'endpoints'  => [
                    [
                        'title'            => 'Get CSRF Cookie',
                        'description'      => 'Wajib dipanggil sebelum login untuk mendapatkan XSRF-TOKEN cookie.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/sanctum/csrf-cookie',
                        'headers'          => null,
                        'body'             => null,
                        'response_example' => '// HTTP 204 No Content — cookie XSRF-TOKEN otomatis diset',
                        'sort_order'       => 1,
                    ],
                    [
                        'title'            => 'Login',
                        'description'      => 'Login sebagai mahasiswa atau dosen. Mengembalikan data user dan set session cookie.',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/auth/login',
                        'headers'          => '"Content-Type"    : "application/json"
"Accept"           : "application/json"
"X-XSRF-TOKEN"    : "{xsrf_token}"',
                        'body'             => '"identifier" : "{nim_atau_email}"
"password"   : "{password}"
"role"       : "mahasiswa"    // atau "dosen"',
                        'response_example' => '{
  "status"  : "success",
  "message" : "Login berhasil",
  "data"    : {
    "nim"           : "2021111001",
    "nama_mahasiswa": "Budi Santoso",
    "role"          : "mahasiswa"
  }
}',
                        'sort_order' => 2,
                    ],
                    [
                        'title'            => 'Logout',
                        'description'      => 'Invalidasi session dan hapus cookie autentikasi.',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/auth/logout',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status"  : "success",
  "message" : "Logout berhasil"
}',
                        'sort_order' => 3,
                    ],
                    [
                        'title'            => 'Me (Data User Login)',
                        'description'      => 'Mengembalikan data user yang sedang login berdasarkan session cookie.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/me',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : {
    "nim"           : "2021111001",
    "nama_mahasiswa": "Budi Santoso"
  }
}',
                        'sort_order' => 4,
                    ],
                ],
            ],

            [
                'title'      => 'Mahasiswa',
                'sort_order' => 2,
                'endpoints'  => [
                    [
                        'title'            => 'Get All Mahasiswa',
                        'description'      => 'Mengambil daftar semua mahasiswa. Mendukung filter pencarian.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/mahasiswa',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "nim": "2021111001", "nama_mahasiswa": "..." }, ... ]
}',
                        'sort_order' => 1,
                    ],
                    [
                        'title'            => 'Get Mahasiswa by NIM',
                        'description'      => 'Mengambil data satu mahasiswa berdasarkan NIM.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/mahasiswa/{nim}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : { "nim": "2021111001", "nama_mahasiswa": "Budi Santoso" }
}',
                        'sort_order' => 2,
                    ],
                    [
                        'title'            => 'Tambah Mahasiswa',
                        'description'      => 'Menambahkan data mahasiswa baru.',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/mahasiswa',
                        'headers'          => '"Content-Type"  : "application/json"
"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => '"nim"           : "2021111001"
"nama_mahasiswa": "Budi Santoso"
"email"         : "budi@email.com"
"program_studi_kode": 1',
                        'response_example' => '{
  "status"  : "success",
  "message" : "Mahasiswa berhasil ditambahkan",
  "data"    : { "nim": "2021111001" }
}',
                        'sort_order' => 3,
                    ],
                    [
                        'title'            => 'Update Mahasiswa',
                        'description'      => 'Memperbarui data mahasiswa berdasarkan NIM.',
                        'method'           => 'PUT',
                        'url'              => $baseUrl . '/api/mahasiswa/{nim}',
                        'headers'          => '"Content-Type"  : "application/json"
"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => '"nama_mahasiswa": "Budi Santoso Updated"
"email"         : "budi_baru@email.com"',
                        'response_example' => '{
  "status"  : "success",
  "message" : "Mahasiswa berhasil diperbarui"
}',
                        'sort_order' => 4,
                    ],
                    [
                        'title'            => 'Hapus Mahasiswa (Soft Delete)',
                        'description'      => 'Menghapus mahasiswa secara soft delete — data masih ada di database.',
                        'method'           => 'DELETE',
                        'url'              => $baseUrl . '/api/mahasiswa/{nim}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status"  : "success",
  "message" : "Mahasiswa berhasil dihapus"
}',
                        'sort_order' => 5,
                    ],
                    [
                        'title'            => 'Restore Mahasiswa',
                        'description'      => 'Memulihkan data mahasiswa yang sudah soft delete.',
                        'method'           => 'PATCH',
                        'url'              => $baseUrl . '/api/mahasiswa/{nim}/restore',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status"  : "success",
  "message" : "Mahasiswa berhasil dipulihkan"
}',
                        'sort_order' => 6,
                    ],
                    [
                        'title'            => 'Force Delete Mahasiswa',
                        'description'      => 'Menghapus mahasiswa secara permanen dari database.',
                        'method'           => 'DELETE',
                        'url'              => $baseUrl . '/api/mahasiswa/{nim}/force',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status"  : "success",
  "message" : "Mahasiswa berhasil dihapus permanen"
}',
                        'sort_order' => 7,
                    ],
                ],
            ],

            [
                'title'      => 'Matakuliah',
                'sort_order' => 3,
                'endpoints'  => [
                    [
                        'title'            => 'Get All Matakuliah',
                        'description'      => 'Mengambil daftar matakuliah. Mendukung filter search dan prodi.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/matakuliah',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "id_matakuliah": 1, "nama_matakuliah": "Anatomi" }, ... ]
}',
                        'sort_order' => 1,
                    ],
                    [
                        'title'            => 'Get Matakuliah by ID',
                        'description'      => 'Mengambil detail satu matakuliah.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/matakuliah/{id}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : { "id_matakuliah": 1, "nama_matakuliah": "Anatomi", "sks": 3 }
}',
                        'sort_order' => 2,
                    ],
                ],
            ],

            [
                'title'      => 'Program Studi',
                'sort_order' => 4,
                'endpoints'  => [
                    [
                        'title'            => 'Get All Program Studi',
                        'description'      => 'Mengambil daftar semua program studi.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/program-studi',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "kode_program_studi": 1, "nama_program_studi": "Kedokteran" } ]
}',
                        'sort_order' => 1,
                    ],
                    [
                        'title'            => 'Get Program Studi by Kode',
                        'description'      => 'Mengambil detail satu program studi.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/program-studi/{kode}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : { "kode_program_studi": 1, "nama_program_studi": "Kedokteran" }
}',
                        'sort_order' => 2,
                    ],
                ],
            ],

            [
                'title'      => 'KRS (Kartu Rencana Studi)',
                'sort_order' => 5,
                'endpoints'  => [
                    [
                        'title'            => 'Get KRS',
                        'description'      => 'Mengambil daftar KRS milik mahasiswa yang sedang login.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/krs',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "kode_krs": 1, "nim": "2021111001", "kode_tahun_akademik": 2 } ]
}',
                        'sort_order' => 1,
                    ],
                    [
                        'title'            => 'Buat KRS',
                        'description'      => 'Membuat KRS baru untuk tahun akademik tertentu.',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/krs',
                        'headers'          => '"Content-Type"  : "application/json"
"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => '"kode_tahun_akademik" : 2',
                        'response_example' => '{
  "status"  : "success",
  "message" : "KRS berhasil dibuat",
  "data"    : { "kode_krs": 1 }
}',
                        'sort_order' => 2,
                    ],
                    [
                        'title'            => 'Get Detail KRS',
                        'description'      => 'Mengambil detail matakuliah yang ada di dalam satu KRS.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/krs/{id}/detail',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "kode_krs_detail": 1, "id_matakuliah": 3 } ]
}',
                        'sort_order' => 3,
                    ],
                    [
                        'title'            => 'Tambah Detail KRS',
                        'description'      => 'Menambahkan matakuliah ke dalam KRS.',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/krs/{id}/detail',
                        'headers'          => '"Content-Type"  : "application/json"
"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => '"id_matakuliah" : 3',
                        'response_example' => '{
  "status"  : "success",
  "message" : "Matakuliah berhasil ditambahkan ke KRS"
}',
                        'sort_order' => 4,
                    ],
                    [
                        'title'            => 'Hapus Detail KRS',
                        'description'      => 'Menghapus satu matakuliah dari KRS.',
                        'method'           => 'DELETE',
                        'url'              => $baseUrl . '/api/krs/{id}/detail/{detailId}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status"  : "success",
  "message" : "Detail KRS berhasil dihapus"
}',
                        'sort_order' => 5,
                    ],
                ],
            ],

            [
                'title'      => 'KHS (Kartu Hasil Studi)',
                'sort_order' => 6,
                'endpoints'  => [
                    [
                        'title'            => 'Get KHS',
                        'description'      => 'Mengambil daftar KHS mahasiswa yang sedang login.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/khs',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : [ { "kode_khs_detail": 1, "nilai_angka": 85.5 } ]
}',
                        'sort_order' => 1,
                    ],
                    [
                        'title'            => 'Input / Update Nilai KHS',
                        'description'      => 'Menyimpan atau memperbarui nilai pada detail KHS (updateOrCreate).',
                        'method'           => 'POST',
                        'url'              => $baseUrl . '/api/khs',
                        'headers'          => '"Content-Type"  : "application/json"
"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => '"kode_krs_detail" : 1
"nilai_angka"     : 85.5
"nilai_huruf"     : "A"
"bobot"           : 4.0',
                        'response_example' => '{
  "status"  : "success",
  "message" : "Nilai berhasil disimpan"
}',
                        'sort_order' => 2,
                    ],
                    [
                        'title'            => 'Get KHS by ID',
                        'description'      => 'Mengambil detail KHS berdasarkan ID.',
                        'method'           => 'GET',
                        'url'              => $baseUrl . '/api/khs/{id}',
                        'headers'          => '"Accept"        : "application/json"
"X-XSRF-TOKEN"  : "{xsrf_token}"',
                        'body'             => null,
                        'response_example' => '{
  "status" : "success",
  "data"   : { "kode_khs_detail": 1, "nilai_angka": 85.5, "nilai_huruf": "A" }
}',
                        'sort_order' => 3,
                    ],
                ],
            ],
        ];

        foreach ($sections as $sectionData) {
            $endpoints = $sectionData['endpoints'];
            unset($sectionData['endpoints']);

            $section = ApiSection::create($sectionData);

            foreach ($endpoints as $ep) {
                $ep['api_section_id'] = $section->id;
                ApiEndpoint::create($ep);
            }
        }
    }
}

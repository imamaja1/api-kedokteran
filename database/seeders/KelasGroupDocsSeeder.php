<?php

namespace Database\Seeders;

use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;
use App\Models\ApiSection;
use Illuminate\Database\Seeder;

class KelasGroupDocsSeeder extends Seeder
{
    public function run(): void
    {
        $maxOrder = ApiSection::max('sort_order') ?? 0;

        $section = ApiSection::firstOrCreate(
            ['title' => 'Staff - Penjabaran Kelas (Group Asesmen)'],
            ['sort_order' => $maxOrder + 1]
        );

        $endpoints = [
            [
                'title'       => 'Daftar Node Asesmen Aktif (ON) Kelas',
                'description' => 'Mengambil semua node asesmen dari template kelas (level 1, 2, atau 3) yang berstatus ON/aktif, lengkap dengan status gerbang asesmen (aktif/non-aktif) dan ringkasan group yang sudah dibuat.',
                'method'      => 'GET',
                'url'         => '/api/staff/kelas-group/active-nodes',
                'headers'     => "Accept: application/json\nCookie: laravel-session",
                'body'        => json_encode(['code_kelas' => '<encrypted code_kelas>'], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => 'Daftar node asesmen kelas berhasil diambil.',
                    'data'    => [
                        'kelas' => [
                            'code_kelas'      => 'eyJpdiI6Ikx3...',
                            'kelas_id'        => 1,
                            'nama_kelas'      => 'A',
                            'matakuliah'      => 'Blok 1.1 Biomedik Dasar',
                            'kode_matakuliah' => 'KD101',
                        ],
                        'assessment' => [
                            'status'           => 'aktif',
                            'is_active'        => true,
                            'can_assign_dosen' => true,
                            'description'      => 'Asesmen aktif. Dosen dan group dapat dimasukkan/diatur.',
                        ],
                        'nodes' => [
                            [
                                'key'            => 'kurikulum_a',
                                'name'           => 'Kurikulum A (Tutorial Skenario 1)',
                                'level'          => 2,
                                'type'           => 'category',
                                'status'         => 'on',
                                'is_active_node' => true,
                                'groups_count'   => 2,
                                'total_assigned_mahasiswa' => 25,
                                'groups'         => [
                                    ['nama_group' => 'Group A', 'dosen' => ['nama_dosen' => 'dr. Budi'], 'jumlah_mahasiswa' => 13],
                                    ['nama_group' => 'Group B', 'dosen' => ['nama_dosen' => 'dr. Siti'], 'jumlah_mahasiswa' => 12],
                                ]
                            ],
                            [
                                'key'            => 'praktikum',
                                'name'           => 'Praktikum Anatomi',
                                'level'          => 1,
                                'type'           => 'category',
                                'status'         => 'on',
                                'is_active_node' => true,
                                'groups_count'   => 2,
                                'total_assigned_mahasiswa' => 25,
                                'groups'         => [
                                    ['nama_group' => 'Group A', 'dosen' => ['nama_dosen' => 'dr. Andi'], 'jumlah_mahasiswa' => 10],
                                    ['nama_group' => 'Group B', 'dosen' => ['nama_dosen' => 'dr. Maya'], 'jumlah_mahasiswa' => 15],
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sort_order' => 1,
            ],
            [
                'title'       => 'Daftar Group & Anggota per Node',
                'description' => 'Mengambil daftar group, dosen pengampu, dan daftar mahasiswa per kelompok pada node asesmen tertentu atau seluruh kelas.',
                'method'      => 'GET',
                'url'         => '/api/staff/kelas-group',
                'headers'     => "Accept: application/json\nCookie: laravel-session",
                'body'        => json_encode(['code_kelas' => '<encrypted code_kelas>', 'node_key' => 'kurikulum_a'], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => 'Data group kelas berhasil diambil.',
                    'data'    => [
                        [
                            'code_group'      => 'eyJpdiI6...',
                            'node_key'        => 'kurikulum_a',
                            'nama_group'      => 'Group A',
                            'dosen'           => [
                                'code_dosen' => 'eyJpdiI6...',
                                'nama_dosen' => 'dr. Budi, M.Biomed',
                            ],
                            'total_mahasiswa' => 13,
                            'mahasiswa'       => [
                                ['nim' => '202601001', 'nama_mahasiswa' => 'Ahmad Faiz'],
                                ['nim' => '202601002', 'nama_mahasiswa' => 'Siti Nurhaliza'],
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sort_order' => 2,
            ],
            [
                'title'       => 'Buat Group Baru pada Node Aktif',
                'description' => 'Membuat group baru di bawah node yang ON, menugaskan dosen pengampu, dan memasukkan mahasiswa anggota group. Hanya diizinkan jika status asesmen kelas adalah AKTIF.',
                'method'      => 'POST',
                'url'         => '/api/staff/kelas-group',
                'headers'     => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                'body'        => json_encode([
                    'code_kelas' => 'eyJpdiI6Ikx3...',
                    'node_key'   => 'kurikulum_a',
                    'nama_group' => 'Group A',
                    'code_dosen' => 'eyJpdiI6...',
                    'nim_list'   => ['202601001', '202601002', '202601003'],
                ], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => 'Group berhasil dibuat.',
                    'data'    => [
                        'code_group'      => 'eyJpdiI6...',
                        'nama_group'      => 'Group A',
                        'node_key'        => 'kurikulum_a',
                        'total_mahasiswa' => 3,
                    ]
                ], JSON_PRETTY_PRINT),
                'sort_order' => 3,
            ],
            [
                'title'       => 'Perbarui Group, Dosen, atau Mahasiswa',
                'description' => 'Mengubah nama group, mengganti dosen pengampu, atau memperbarui susunan mahasiswa anggota group pada node tersebut.',
                'method'      => 'PUT',
                'url'         => '/api/staff/kelas-group',
                'headers'     => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                'body'        => json_encode([
                    'code_group' => 'eyJpdiI6...',
                    'nama_group' => 'Group A Revisi',
                    'code_dosen' => 'eyJpdiI6...',
                    'nim_list'   => ['202601001', '202601002'],
                ], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => 'Group berhasil diperbarui.',
                ], JSON_PRETTY_PRINT),
                'sort_order' => 4,
            ],
            [
                'title'       => 'Hapus Group',
                'description' => 'Menghapus group dan melepaskan plotting mahasiswa di dalamnya.',
                'method'      => 'DELETE',
                'url'         => '/api/staff/kelas-group',
                'headers'     => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                'body'        => json_encode([
                    'code_group' => 'eyJpdiI6...',
                ], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => 'Group berhasil dihapus.',
                ], JSON_PRETTY_PRINT),
                'sort_order' => 5,
            ],
            [
                'title'       => 'Salin Group Antar Node',
                'description' => 'Menyalin seluruh komposisi group dan anggota mahasiswa dari satu node ke node lain pada kelas yang sama (menghemat waktu input manual admin).',
                'method'      => 'POST',
                'url'         => '/api/staff/kelas-group/copy',
                'headers'     => "Content-Type: application/json\nAccept: application/json\nCookie: laravel-session",
                'body'        => json_encode([
                    'code_kelas'    => 'eyJpdiI6Ikx3...',
                    'from_node_key' => 'kurikulum_a',
                    'to_node_key'   => 'kurikulum_b',
                ], JSON_PRETTY_PRINT),
                'response_example' => json_encode([
                    'status'  => true,
                    'message' => "Berhasil menyalin 2 group ke node 'kurikulum_b'.",
                    'data'    => [
                        'copied_groups' => 2,
                        'from_node'     => 'kurikulum_a',
                        'to_node'       => 'kurikulum_b',
                    ]
                ], JSON_PRETTY_PRINT),
                'sort_order' => 6,
            ],
        ];

        foreach ($endpoints as $epData) {
            ApiEndpoint::updateOrCreate(
                [
                    'api_section_id' => $section->id,
                    'url'            => $epData['url'],
                    'method'         => $epData['method'],
                ],
                $epData
            );
        }
    }
}

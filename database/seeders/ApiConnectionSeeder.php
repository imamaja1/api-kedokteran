<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiConnectionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::connection()->getSchemaBuilder()->hasTable('api_connections')) {
            return;
        }

        $connections = [
            [
                'id' => 1,
                'name' => 'CSRF Cookie',
                'description' => 'api-siska Testing',
                'base_url' => 'http://127.0.0.1:8080/sanctum/csrf-cookie',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-24 22:10:29'),
                'updated_at' => Carbon::parse('2026-04-30 00:06:47'),
            ],
            [
                'id' => 2,
                'name' => 'Credential Api Siska',
                'description' => 'username dan password',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/login',
                'username' => 'Akademik',
                'password' => 'eyJpdiI6IlFnMFR4aVRyRjNNOERnbkNNTzFUdFE9PSIsInZhbHVlIjoiSXZYNHhvYStLdEdBOThqcExYMlMxZz09IiwibWFjIjoiZDVmMTAxNmU1ODE3NjY2Y2E0NGE2ZmRiZWUwMjI1Y2NmYzA5OWQ1Y2M1NmE4Y2I5MWQzNTc0ODI2ZTA1NTZkNyIsInRhZyI6IiJ9',
                'cookie' => 'XSRF-TOKEN=eyJpdiI6IkpaTTFub0dXSjFpVlhGbmZrdEpZbFE9PSIsInZhbHVlIjoiM25nOGdmNlFLYXhkaGRDcjZxOS9pSlRyODNlYTNjams0R0lBM1IwSDZJRkF0dDg5d043VkJEYlFGcU90MnlIMnpEeUliS1c4UE9LeFRtcHNSRkFPa2VyS0M5SUxFYkx2WFBXclEwZ2lFRGI0Mm5ZbTE3VlV3dUEySndjVlo2MjMiLCJtYWMiOiI1ZmU5MThhYTI3MWRiZTUwZGM4Njk5ZmE0MDExZjQwYWEwMGFkMWFkYTQ1YzBmZjZlM2JlNmYxZGNjZGJiZjllIiwidGFnIjoiIn0%3D; laravel-session=eyJpdiI6IjR2WTZCNUt2V1FzTWR0dUlJdHlXRHc9PSIsInZhbHVlIjoiNC9WVFVrZ05VY0Y2THhTT0hZZVhpemE2V2pJcThjMlJnTElOYUlSQlFIRlNEdG5jVnhyMkQzYWFRNlE2TCsxMHkzbmMwZzBseWIvd3B5RE1KbTZhRU5wVUhtVUwyTVRKQUFmbWg3VHdTVzFFUFlVc0R0UjVBV2dJNnhsTXM5azgiLCJtYWMiOiJmYmJkNzkzOWRmMDU2Y2VjMzI4YWQzY2UwYjk3MGZhMTAxOGUwOGE0MzY5NjcxMmU2N2FkNWZiNzlkZGNjNmVlIiwidGFnIjoiIn0%3D',
                'extra_headers' => null,
                'cookie_expires_at' => Carbon::parse('2026-05-23 12:56:37'),
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-26 19:09:00'),
                'updated_at' => Carbon::parse('2026-05-23 04:56:37'),
            ],
            [
                'id' => 3,
                'name' => 'Get Mhs Kedokteran API-SISKA',
                'description' => 'Get MHS kedokteran saja',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-mhs-kedokteran',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-26 19:10:10'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:01'),
            ],
            [
                'id' => 4,
                'name' => 'Get Dosen Kedokteran API-SISKA',
                'description' => 'api-siska Testing',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-dosen-kedokteran',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-26 23:20:24'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:04'),
            ],
            [
                'id' => 5,
                'name' => 'Get Tahun Akademik API-SISKA',
                'description' => 'ambil data tahun akademik',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-tahun-akademik',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-30 18:29:16'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:08'),
            ],
            [
                'id' => 6,
                'name' => 'Get KRS API-SISKA',
                'description' => 'get krs dan khs siska',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-krs-khs',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-30 21:29:31'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:13'),
            ],
            [
                'id' => 7,
                'name' => 'Get Matakuliah API-SISKA',
                'description' => 'Ambil matakuliah',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-matakuliah',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-30 22:16:47'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:16'),
            ],
            [
                'id' => 8,
                'name' => 'Get Kelas API-SISKA',
                'description' => 'get kelas ini',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-kelas',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-03-31 21:54:13'),
                'updated_at' => Carbon::parse('2026-04-30 00:07:21'),
            ],
            [
                'id' => 9,
                'name' => 'Get Kurikulum',
                'description' => 'ambil semua kurikulum',
                'base_url' => 'http://127.0.0.1:8080/api/v1/divisi/get-kurikulum',
                'username' => null,
                'password' => null,
                'cookie' => null,
                'extra_headers' => null,
                'cookie_expires_at' => null,
                'is_active' => 1,
                'created_at' => Carbon::parse('2026-04-29 23:55:09'),
                'updated_at' => Carbon::parse('2026-04-30 00:09:30'),
            ],
        ];

        foreach ($connections as $connection) {
            DB::table('api_connections')->updateOrInsert(
                ['id' => $connection['id']],
                $connection
            );
        }
    }
}

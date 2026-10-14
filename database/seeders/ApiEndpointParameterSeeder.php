<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;

class ApiEndpointParameterSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::connection()->getSchemaBuilder()->hasTable('api_endpoint_parameters')) {
            return;
        }

        // Hapus data parameter lama untuk sinkronisasi bersih
        ApiEndpointParameter::truncate();

        $endpoints = ApiEndpoint::with('section')->get();

        // Siapkan lookup map untuk endpoint master penghasil data
        $lookupEndpoints = [
            'ruangan'        => $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/ruangan')?->id,
            'hari'           => $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/hari')?->id,
            'status_kelas'   => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'jadwal-kelas/status-kelas'))?->id,
            'kelas'          => $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/kelas')?->id,
            'tahun_akademik' => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'tahun-akademik'))?->id,
            'matakuliah'     => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'matakuliah'))?->id,
            'dosen'          => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'master-data/dosen'))?->id ?? $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/dosen')?->id,
            'mahasiswa'      => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'master-data/mahasiswa'))?->id,
            'prodi'          => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'program-studi'))?->id,
            'kurikulum'      => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'nama-kurikulum'))?->id,
            'perwalian'      => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'perwalian'))?->id,
            'templates'      => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'assessment/templates'))?->id,
            'feeder_status'  => $endpoints->first(fn($e) => $e->method === 'GET' && str_contains($e->url, 'feeder/status'))?->id,
        ];

        foreach ($endpoints as $ep) {
            $parsedParams = [];
            $url = $ep->url;
            $bodyStr = $ep->body;

            // 1. Deteksi Path Parameter dari URL (misal: /{code}, /{nim}, /{id})
            if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $url, $matches)) {
                foreach ($matches[1] as $pName) {
                    $parsedParams[$pName] = [
                        'location' => 'path',
                        'type' => 'string',
                        'is_required' => true,
                        'example' => '{' . $pName . '}',
                    ];
                }
            }

            // 2. Deteksi Body & Query Parameters dari kolom `body`
            if (!empty($bodyStr)) {
                $decoded = json_decode($bodyStr, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $val) {
                        $isReq = true;
                        $type = 'string';
                        $example = is_scalar($val) ? (string)$val : json_encode($val);

                        if (is_string($val) && str_contains($val, '|')) {
                            $parts = explode('|', $val);
                            $example = $parts[0] ?? '';
                            $isReq = !in_array('nullable', $parts);
                            $type = in_array('integer', $parts) ? 'integer' : (in_array('boolean', $parts) ? 'boolean' : (in_array('array', $parts) ? 'array' : 'string'));
                        }

                        $parsedParams[$key] = [
                            'location' => in_array($ep->method, ['GET', 'DELETE']) ? 'query' : 'body',
                            'type' => $type,
                            'is_required' => $isReq,
                            'example' => $example,
                        ];
                    }
                }
            }

            // 3. Klasifikasi Heuristik Sumber Nilai (source_type & source_endpoint_id)
            $sort = 1;
            foreach ($parsedParams as $paramName => $meta) {
                $sourceType = 'input';
                $sourceEndpointId = null;
                $sourceField = null;
                $sourceNote = null;
                $lowerName = strtolower($paramName);

                // --- Spesifik: Assessment Dosen (Staff & Dosen Flow) ---
                if (str_contains($lowerName, 'assessment_dosen_id')) {
                    if (str_contains($ep->url, '/api/staff/')) {
                        $sourceType = 'endpoint';
                        $sourceEndpointId = $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/assessment/dosen/assignments')?->id
                            ?? $endpoints->first(fn($e) => $e->method === 'POST' && $e->url === '/api/staff/assessment/dosen')?->id;
                        $sourceField = 'assessment_dosen_id';
                        $sourceNote = 'ID AssessmentDosen dari GET /api/staff/assessment/dosen/assignments atau response POST /api/staff/assessment/dosen';
                    } else {
                        $sourceType = 'endpoint';
                        $sourceEndpointId = $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/dosen/assessment-dosen/my-nodes')?->id;
                        $sourceField = 'assessment_dosen_id';
                        $sourceNote = 'Nilai assessment_dosen_id tugas dosen dari GET /api/dosen/assessment-dosen/my-nodes';
                    }
                } elseif (str_contains($lowerName, 'node_key')) {
                    if (str_contains($ep->url, '/api/staff/')) {
                        $sourceType = 'endpoint';
                        $sourceEndpointId = $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/assessment/dosen/assignments')?->id
                            ?? $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/assessment/templates/show')?->id;
                        $sourceField = 'available_nodes[].node_key';
                        $sourceNote = 'Kunci node kategori dari GET /api/staff/assessment/dosen/assignments atau template assessment';
                    } else {
                        $sourceType = 'endpoint';
                        $sourceEndpointId = $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/dosen/assessment-dosen/my-nodes')?->id;
                        $sourceField = 'node_key';
                        $sourceNote = 'Node key tugas validasi dosen dari GET /api/dosen/assessment-dosen/my-nodes';
                    }
                } elseif ($ep->url === '/api/staff/assessment/dosen/assign' && str_contains($lowerName, 'kode_dosen')) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $endpoints->first(fn($e) => $e->method === 'GET' && $e->url === '/api/staff/assessment/dosen/assignments')?->id
                        ?? $lookupEndpoints['dosen'];
                    $sourceField = 'dosen_pengajar[].kode_dosen';
                    $sourceNote = 'Kode dosen pengajar kelas dari GET /api/staff/assessment/dosen/assignments';
                } elseif (str_contains($ep->url, 'assessment/dosen') && str_contains($lowerName, 'kelas')) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['kelas'];
                    $sourceField = 'code';
                    $sourceNote = 'Nilai terenkripsi code_kelas dari master kelas perkuliahan (GET /api/staff/kelas)';
                } elseif (str_contains($lowerName, 'ruangan') || ($lowerName === 'code' && str_contains($ep->url, 'ruangan'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['ruangan'];
                    $sourceField = 'code_ruangan';
                    $sourceNote = 'Nilai terenkripsi code_ruangan dari master ruangan';
                } elseif (str_contains($lowerName, 'hari') || ($lowerName === 'code' && str_contains($ep->url, 'hari'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['hari'];
                    $sourceField = 'code_hari';
                    $sourceNote = 'Nilai terenkripsi code_hari dari master hari';
                } elseif (str_contains($lowerName, 'kelas') || ($lowerName === 'code' && str_contains($ep->url, 'jadwal-kelas')) || ($lowerName === 'code' && str_contains($ep->url, 'kelas'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = str_contains($ep->url, 'jadwal-kelas') ? ($lookupEndpoints['status_kelas'] ?? $lookupEndpoints['kelas']) : $lookupEndpoints['kelas'];
                    $sourceField = 'code_kelas';
                    $sourceNote = 'Nilai terenkripsi code_kelas dari daftar kelas';
                } elseif (str_contains($lowerName, 'tahun_akademik') || ($lowerName === 'code' && str_contains($ep->url, 'tahun-akademik'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['tahun_akademik'];
                    $sourceField = 'code_tahun_akademik';
                    $sourceNote = 'Nilai terenkripsi code_tahun_akademik dari master tahun akademik';
                } elseif (str_contains($lowerName, 'matakuliah') || ($lowerName === 'code' && str_contains($ep->url, 'matakuliah'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['matakuliah'];
                    $sourceField = 'code_matakuliah';
                    $sourceNote = 'Nilai terenkripsi code_matakuliah dari master matakuliah';
                } elseif (str_contains($lowerName, 'dosen') || ($lowerName === 'code' && str_contains($ep->url, 'dosen'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['dosen'];
                    $sourceField = 'code_dosen';
                    $sourceNote = 'Nilai terenkripsi code_dosen dari master data dosen';
                } elseif (str_contains($lowerName, 'mahasiswa') || $lowerName === 'nim' || ($lowerName === 'code' && str_contains($ep->url, 'mahasiswa'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['mahasiswa'];
                    $sourceField = 'nim';
                    $sourceNote = 'NIM atau encrypted code mahasiswa dari master data mahasiswa';
                } elseif (str_contains($lowerName, 'prodi') || str_contains($lowerName, 'program_studi') || ($lowerName === 'code' && str_contains($ep->url, 'program-studi'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['prodi'];
                    $sourceField = 'code_prodi';
                    $sourceNote = 'Nilai terenkripsi program studi';
                } elseif (str_contains($lowerName, 'perwalian') || ($lowerName === 'code' && str_contains($ep->url, 'perwalian'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['perwalian'];
                    $sourceField = 'code';
                    $sourceNote = 'Nilai terenkripsi kode perwalian';
                } elseif (str_contains($lowerName, 'template') || ($lowerName === 'code' && str_contains($ep->url, 'template'))) {
                    $sourceType = 'endpoint';
                    $sourceEndpointId = $lookupEndpoints['templates'];
                    $sourceField = 'code';
                    $sourceNote = 'Nilai terenkripsi template penilaian';
                } elseif (in_array($lowerName, ['token', 'csrf', 'xsrf', 'session'])) {
                    $sourceType = 'session';
                    $sourceNote = 'Diisi otomatis oleh cookie session Sanctum';
                } else {
                    $sourceType = 'input';
                    $sourceNote = 'Input manual pengguna';
                }

                // Jangan menunjuk ke diri sendiri jika ini adalah endpoint master itu sendiri
                if ($sourceEndpointId === $ep->id) {
                    $sourceType = 'input';
                    $sourceEndpointId = null;
                    $sourceField = null;
                    $sourceNote = 'Identifier entitas saat ini';
                }

                ApiEndpointParameter::create([
                    'api_endpoint_id'    => $ep->id,
                    'name'               => $paramName,
                    'type'               => $meta['type'],
                    'location'           => $meta['location'],
                    'is_required'        => $meta['is_required'],
                    'source_type'        => $sourceType,
                    'source_endpoint_id' => $sourceEndpointId,
                    'source_field'       => $sourceField,
                    'source_note'        => $sourceNote,
                    'example'            => $meta['example'],
                    'sort_order'         => $sort++,
                ]);
            }
        }
    }
}

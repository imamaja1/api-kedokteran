<?php

namespace App\Service;

use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;
use Illuminate\Support\Facades\DB;

class DocsSyncService
{
    /**
     * Bersihkan data dokumentasi (duplikat validasi, format body JSON, URL)
     * lalu bangun ulang api_endpoint_parameters dengan pemetaan role-aware yang akurat.
     *
     * @return array{cleaned_endpoints: int, removed_duplicates: int, synced_parameters: int}
     */
    public function sync(): array
    {
        $this->normalizeSectionOrder();
        $removedDuplicates = $this->cleanupEndpoints();
        $cleanedEndpoints = $this->normalizeEndpointBodies();
        $syncedParameters = $this->rebuildParameters();

        return [
            'cleaned_endpoints' => $cleanedEndpoints,
            'removed_duplicates' => $removedDuplicates,
            'synced_parameters' => $syncedParameters,
        ];
    }

    /**
     * Rapikan penamaan & urutan api_sections agar terkelompok jelas per role:
     * Mahasiswa -> Staff -> Dosen -> Kaprodi.
     */
    private function normalizeSectionOrder(): void
    {
        $kaprodiSection = \App\Models\ApiSection::where('title', 'Dosen - Kaprodi')->first();
        if ($kaprodiSection && ! \App\Models\ApiSection::where('title', 'Kaprodi - Validasi Penilaian & KHS')->exists()) {
            $kaprodiSection->update([
                'title' => 'Kaprodi - Validasi Penilaian & KHS',
                'description' => 'Khusus Ketua Program Studi (Kaprodi) untuk mereview nilai akhir kelas dan mengesahkan nilai masuk ke KHS mahasiswa.',
            ]);
        }

        $sectionOrderMap = [
            // 1. Mahasiswa
            'Auth - Mahasiswa'                          => 1,
            'Mahasiswa - Akademik'                      => 2,
            // 2. Staff
            'Auth - Staff'                              => 3,
            'Staff - Pencarian'                         => 4,
            'Staff - Master Data (Mahasiswa)'           => 5,
            'Staff - Master Data (Dosen)'               => 6,
            'Staff - Master Data (Matakuliah)'          => 7,
            'Staff - Master Data (Prodi)'               => 8,
            'Staff - Master Data (Nama Kurikulum)'      => 9,
            'Staff - Master Data (Tahun Akademik)'      => 10,
            'Staff - Master Data (Kurikulum Angkatan)'  => 11,
            'Staff - Master Ruangan & Hari'             => 12,
            'Staff - Data Default'                      => 13,
            'Staff - SKS Rule'                          => 14,
            'Staff - Akademik'                          => 15,
            'Staff - Perwalian'                         => 16,
            'Staff - Konfig Kelas'                      => 17,
            'Staff - Penjadwalan Kelas'                 => 18,
            'Staff - Penjabaran Kelas (Group Asesmen)'  => 19,
            'Staff - Pembayaran'                        => 20,
            'Staff - Assessment (Templates)'            => 21,
            'Staff - Assessment (Scores)'               => 22,
            'Staff - Assessment (Grade)'                => 23,
            'Staff - Assessment Dosen (Setup)'          => 24,
            'Staff - Audit Feeder PDDIKTI'              => 25,
            // 3. Dosen
            'Auth - Dosen'                              => 26,
            'Dosen - Data Dosen'                        => 27,
            'Dosen - Kurikulum'                         => 28,
            'Dosen - Perwalian'                         => 29,
            'Dosen - Penilaian'                         => 30,
            'Dosen - Assessment Dosen (Multi-level)'    => 31,
            // 4. Kaprodi
            'Kaprodi - Validasi Penilaian & KHS'        => 32,
        ];

        foreach ($sectionOrderMap as $title => $sortOrder) {
            \App\Models\ApiSection::where('title', $title)->update(['sort_order' => $sortOrder]);
        }
    }

    private function cleanupEndpoints(): int
    {
        $removed = 0;

        // Hapus entry contoh error/validasi duplikat yang berbagi method+url dengan endpoint utama
        $validationEntries = ApiEndpoint::where('title', 'like', 'Validasi:%')->get();
        foreach ($validationEntries as $entry) {
            $hasPrimary = ApiEndpoint::where('id', '!=', $entry->id)
                ->where('method', $entry->method)
                ->where('url', $entry->url)
                ->where('title', 'not like', 'Validasi:%')
                ->exists();

            if ($hasPrimary) {
                $entry->delete();
                $removed++;
            }
        }

        $dosenAssessSectionId = \App\Models\ApiSection::where('title', 'Dosen - Assessment Dosen (Multi-level)')->value('id');

        // Normalisasi URL tanpa leading '/' dan placeholder <encrypted_code>
        foreach (ApiEndpoint::all() as $ep) {
            $newUrl = '/' . ltrim($ep->url, '/');
            $newUrl = str_replace(['<encrypted_code>', '<encrypted>', '<code_kelas>'], ['{code}', '{code}', '{code_kelas}'], $newUrl);

            $updates = [];
            if ($newUrl !== $ep->url) {
                $updates['url'] = $newUrl;
            }

            // Kembalikan endpoint assessment-dosen validasi/revisi ke ranah & section Dosen (bukan Kaprodi)
            $dosenEndpointMap = [
                '/api/dosen/kaprodi/assessment-dosen/validasi' => '/api/dosen/assessment-dosen/validasi',
                '/api/dosen/kaprodi/assessment-dosen/validasi-bulk' => '/api/dosen/assessment-dosen/validasi-bulk',
                '/api/dosen/kaprodi/assessment-dosen/revisi' => '/api/dosen/assessment-dosen/revisi',
            ];

            $dosenEndpointMeta = [
                '/api/dosen/assessment-dosen/validasi' => [
                    'url' => '/api/dosen/assessment-dosen/validasi',
                    'title' => 'Validasi Nilai Assessment Mahasiswa (Dosen)',
                    'description' => 'Dosen pengampu/koordinator node memvalidasi nilai komponen mahasiswa di kelas. Pengesahan akhir ke KHS dilakukan terpisah oleh Kaprodi melalui POST /api/dosen/kaprodi/penilaian/validasi.',
                    'body' => json_encode([
                        'assessment_dosen_id' => 1,
                        'node_key' => 'kompetensi_inti',
                        'code_mahasiswa' => '<encrypted code_mahasiswa>',
                        'catatan' => 'Nilai komponen sudah sesuai',
                    ], JSON_PRETTY_PRINT),
                    'sort_order' => 5,
                ],
                '/api/dosen/assessment-dosen/validasi-bulk' => [
                    'url' => '/api/dosen/assessment-dosen/validasi-bulk',
                    'title' => 'Validasi Massal Sekelas Assessment (Dosen)',
                    'description' => 'Dosen memvalidasi nilai node untuk seluruh mahasiswa pada kelas/group yang diampu. Nilai baru diterbitkan ke KHS setelah disahkan Kaprodi melalui POST /api/dosen/kaprodi/penilaian/validasi.',
                    'body' => json_encode([
                        'assessment_dosen_id' => 1,
                        'node_key' => 'kompetensi_inti',
                        'catatan' => 'Validasi massal komponen oleh dosen',
                    ], JSON_PRETTY_PRINT),
                    'sort_order' => 6,
                ],
                '/api/dosen/assessment-dosen/revisi' => [
                    'url' => '/api/dosen/assessment-dosen/revisi',
                    'title' => 'Revisi Nilai Assessment Mahasiswa (Dosen)',
                    'description' => 'Dosen mengembalikan status nilai node mahasiswa menjadi revisi untuk perbaikan nilai di kelas.',
                    'body' => json_encode([
                        'assessment_dosen_id' => 1,
                        'node_key' => 'kompetensi_inti',
                        'code_mahasiswa' => '<encrypted code_mahasiswa>',
                        'catatan' => 'Perlu dicek ulang nilai tugas 2',
                    ], JSON_PRETTY_PRINT),
                    'sort_order' => 7,
                ],
            ];

            $resolvedDosenUrl = $dosenEndpointMap[$newUrl] ?? (isset($dosenEndpointMeta[$newUrl]) ? $newUrl : null);
            if ($resolvedDosenUrl !== null) {
                if (isset($dosenEndpointMap[$newUrl])) {
                    $duplicateTargetExists = ApiEndpoint::where('id', '!=', $ep->id)
                        ->where('method', $ep->method)
                        ->where('url', $resolvedDosenUrl)
                        ->exists();

                    if ($duplicateTargetExists) {
                        $ep->delete();
                        $removed++;
                        continue;
                    }
                }

                $metaUpdates = $dosenEndpointMeta[$resolvedDosenUrl];
                if ($dosenAssessSectionId) {
                    $metaUpdates['api_section_id'] = $dosenAssessSectionId;
                }
                foreach ($metaUpdates as $col => $val) {
                    if ($ep->{$col} !== $val) {
                        $updates[$col] = $val;
                    }
                }
            }

            // Pastikan endpoint kelas-group yang butuh code_kelas memiliki body/query schema
            if ($newUrl === '/api/staff/kelas-group/active-nodes' && empty($ep->body)) {
                $updates['body'] = json_encode(['code_kelas' => '<encrypted code_kelas>'], JSON_PRETTY_PRINT);
            } elseif ($newUrl === '/api/staff/kelas-group' && $ep->method === 'GET' && empty($ep->body)) {
                $updates['body'] = json_encode(['code_kelas' => '<encrypted code_kelas>', 'node_key' => 'kurikulum_a'], JSON_PRETTY_PRINT);
            }

            if ($updates !== []) {
                $ep->update($updates);
            }
        }

        return $removed;
    }

    /**
     * Konversi body berformat "{contoh}|required|string|..." menjadi JSON valid siap kirim,
     * sambil menyimpan metadata rule sementara ke memori untuk rebuildParameters().
     */
    private function normalizeEndpointBodies(): int
    {
        $cleaned = 0;

        foreach (ApiEndpoint::all() as $ep) {
            if (empty($ep->body) || trim($ep->body) === '-') {
                continue;
            }

            $rawBody = trim($ep->body);
            $decoded = $this->decodeLenientJson($rawBody);

            if (! is_array($decoded)) {
                continue;
            }

            $hasRuleStrings = false;
            $cleanBody = $this->cleanBodyRecursive($decoded, '', $hasRuleStrings);

            // Simpan rule metadata di parameter sebelum menimpa body dengan JSON bersih
            if ($hasRuleStrings) {
                $this->seedParametersFromDecodedBody($ep, $decoded);

                $cleanJson = json_encode($cleanBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($cleanJson !== false && $cleanJson !== $ep->body) {
                    $ep->update(['body' => $cleanJson]);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    private function cleanBodyRecursive(mixed $value, string $key, bool &$hasRuleStrings): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $childKey => $childVal) {
                $nextKey = is_string($childKey) ? $childKey : $key;
                $result[$childKey] = $this->cleanBodyRecursive($childVal, $nextKey, $hasRuleStrings);
            }

            return $result;
        }

        if (is_string($value) && str_contains($value, '|')) {
            $hasRuleStrings = true;

            return $this->extractCleanExampleValue($value, $key);
        }

        return $value;
    }

    /**
     * Bangun ulang parameter dan relasi source_endpoint_id secara akurat & ringkas (tanpa rantai lintas-role).
     */
    private function rebuildParameters(): int
    {
        $endpoints = ApiEndpoint::with(['section', 'parameters'])->get();

        // Simpan metadata tipe/required yang sudah ada sebelumnya agar tidak hilang setelah body dibersihkan
        $existingMeta = [];
        foreach ($endpoints as $ep) {
            foreach ($ep->parameters as $p) {
                $existingMeta[$ep->id][$p->name] = [
                    'type' => $p->type,
                    'location' => $p->location,
                    'is_required' => (bool) $p->is_required,
                    'example' => $p->example,
                ];
            }
        }

        ApiEndpointParameter::truncate();

        $lookup = $this->buildLookupEndpoints($endpoints);
        $totalSynced = 0;

        foreach ($endpoints as $ep) {
            $parsedParams = $this->extractParamsForEndpoint($ep, $existingMeta[$ep->id] ?? []);
            $role = $ep->auth_role;
            $sort = 1;

            foreach ($parsedParams as $paramName => $meta) {
                [$sourceType, $sourceEndpointId, $sourceField, $sourceNote] = $this->resolveParamSource(
                    $ep,
                    $role,
                    $paramName,
                    $lookup,
                    $endpoints
                );

                // Hindari self-reference
                if ($sourceEndpointId === $ep->id) {
                    $sourceType = 'input';
                    $sourceEndpointId = null;
                    $sourceField = null;
                    $sourceNote = 'Identifier entitas saat ini';
                }

                // Hindari prerequisite lintas role
                if ($sourceEndpointId !== null) {
                    $srcEp = $endpoints->firstWhere('id', $sourceEndpointId);
                    if ($srcEp && $srcEp->auth_role !== $role && $srcEp->auth_role !== 'Public / Session') {
                        $sourceType = 'input';
                        $sourceEndpointId = null;
                        $sourceField = null;
                        $sourceNote = 'Input atau diambil dari endpoint role ' . $role;
                    }
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
                $totalSynced++;
            }
        }

        return $totalSynced;
    }

    private function seedParametersFromDecodedBody(ApiEndpoint $ep, array $decoded): void
    {
        $sort = 1;
        foreach ($decoded as $key => $val) {
            $meta = $this->parseRuleValue($key, $val, $ep->method);
            ApiEndpointParameter::updateOrCreate(
                ['api_endpoint_id' => $ep->id, 'name' => $key],
                [
                    'type' => $meta['type'],
                    'location' => $meta['location'],
                    'is_required' => $meta['is_required'],
                    'example' => $meta['example'],
                    'sort_order' => $sort++,
                ]
            );
        }
    }

    private function extractParamsForEndpoint(ApiEndpoint $ep, array $existingMeta): array
    {
        $parsedParams = [];

        // 1. Path parameter dari URL ({code}, {nim}, {id})
        if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $ep->url, $matches)) {
            foreach ($matches[1] as $pName) {
                $parsedParams[$pName] = [
                    'location' => 'path',
                    'type' => 'string',
                    'is_required' => true,
                    'example' => $existingMeta[$pName]['example'] ?? '{' . $pName . '}',
                ];
            }
        }

        // 2. Body / query parameter dari body JSON
        $bodyStr = trim((string) $ep->body);
        if ($bodyStr !== '' && $bodyStr !== '-') {
            $decoded = $this->decodeLenientJson($bodyStr);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $val) {
                    $meta = $this->parseRuleValue($key, $val, $ep->method);

                    // Pertahankan is_required/type dari metadata sebelumnya jika body sudah dibersihkan
                    if (isset($existingMeta[$key]) && ! (is_string($val) && str_contains($val, '|'))) {
                        $meta['is_required'] = $existingMeta[$key]['is_required'];
                        $meta['type'] = $existingMeta[$key]['type'];
                    }

                    $parsedParams[$key] = $meta;
                }
            }
        }

        // 3. Jika belum ter-ekstrak dari body (misal endpoint sebelumnya gagal JSON decode), pertahankan parameter lama
        if ($parsedParams === [] && $existingMeta !== []) {
            foreach ($existingMeta as $pName => $meta) {
                $parsedParams[$pName] = $meta;
            }
        }

        return $parsedParams;
    }

    private function parseRuleValue(string $key, mixed $val, string $method): array
    {
        $location = in_array($method, ['GET', 'DELETE'], true) ? 'query' : 'body';

        if (is_string($val) && str_contains($val, '|')) {
            $parts = explode('|', $val);
            $rawExample = trim($parts[0], '{} ');
            $rules = array_slice($parts, 1);

            $isReq = ! in_array('nullable', $rules, true) && ! in_array('sometimes', $rules, true);
            $type = 'string';
            if (in_array('integer', $rules, true)) {
                $type = 'integer';
            } elseif (in_array('numeric', $rules, true)) {
                $type = 'number';
            } elseif (in_array('boolean', $rules, true)) {
                $type = 'boolean';
            } elseif (in_array('array', $rules, true)) {
                $type = 'array';
            }

            return [
                'location' => $location,
                'type' => $type,
                'is_required' => $isReq,
                'example' => $rawExample !== '' ? $rawExample : $key,
            ];
        }

        $type = match (true) {
            is_bool($val) => 'boolean',
            is_int($val) => 'integer',
            is_float($val) => 'number',
            is_array($val) => 'array',
            default => 'string',
        };

        $example = is_scalar($val) ? (string) $val : json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Deteksi opsional untuk filter GET jika belum ada penanda eksplisit
        $isRequired = true;
        if ($method === 'GET' && in_array($key, ['search', 'status', 'status_aktif', 'jenis', 'per_page', 'semester', 'angkatan', 'versi', 'huruf', 'refresh', 'kode_program_studi', 'code_program_studi', 'kode_tahun_akademik', 'code_tahun_akademik', 'code_kelas', 'code_ruangan', 'code_hari', 'code_dosen', 'nim', 'nama_dosen', 'alamat_email', 'node_key'], true)) {
            $isRequired = false;
        }

        return [
            'location' => $location,
            'type' => $type,
            'is_required' => $isRequired,
            'example' => $example,
        ];
    }

    private function extractCleanExampleValue(string $val, string $key): mixed
    {
        $parts = explode('|', $val);
        $raw = trim($parts[0], '{} ');
        $rules = array_slice($parts, 1);

        if ($raw === '' || in_array($raw, ['nullable', 'sometimes', 'required'], true)) {
            return null;
        }

        if (in_array('integer', $rules, true) && is_numeric($raw)) {
            return (int) $raw;
        }
        if (in_array('numeric', $rules, true) && is_numeric($raw)) {
            return str_contains($raw, '.') ? (float) $raw : (int) $raw;
        }
        if (in_array('boolean', $rules, true)) {
            return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }
        if (in_array('array', $rules, true) || $raw === '[...]') {
            if (str_contains($key, 'matakuliah')) {
                return ['<encrypted id_matakuliah>'];
            }
            return [];
        }

        // Rapikan placeholder agar jelas bahwa nilainya encrypted atau contoh valid
        if (str_contains(strtolower($raw), 'encrypted')) {
            return '<' . trim($raw, '<>') . '>';
        }

        return $raw;
    }

    private function decodeLenientJson(string $raw): ?array
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Perbaiki regex Laravel dengan backslash tunggal seperti regex:/^\d{4}\/\d{4}$/
        $sanitized = preg_replace('/\\\\(?!["\\\\\/bfnrtu])/', '\\\\\\\\', $raw);
        $decoded = json_decode((string) $sanitized, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function buildLookupEndpoints($endpoints): array
    {
        $find = fn (string $method, string $url) => $endpoints->first(
            fn ($e) => $e->method === $method && '/' . ltrim($e->url, '/') === $url
        )?->id;

        return [
            // Mahasiswa
            'mhs_kurikulum'          => $find('GET', '/api/mhs/kurikulum'),
            'mhs_krs'                => $find('GET', '/api/mhs/krs'),
            // Dosen
            'dosen_list'             => $find('GET', '/api/dosen'),
            'dosen_kurikulum_kelas'  => $find('GET', '/api/dosen/kurikulum/kelas'),
            'dosen_perwalian_daftar' => $find('GET', '/api/dosen/perwalian/daftar'),
            'dosen_perwalian_riwayat'=> $find('GET', '/api/dosen/perwalian/riwayat'),
            'dosen_penilaian_kelas'  => $find('GET', '/api/dosen/penilaian/kelas') ?? $find('GET', '/api/dosen/penilaian'),
            'dosen_penilaian_mhs'    => $find('GET', '/api/dosen/penilaian/mahasiswa'),
            'kaprodi_kelas'          => $find('GET', '/api/dosen/kaprodi/penilaian/kelas'),
            'kaprodi_mhs'            => $find('GET', '/api/dosen/kaprodi/penilaian/mahasiswa'),
            'dosen_my_nodes'         => $find('GET', '/api/dosen/assessment-dosen/my-nodes'),
            'dosen_node_mhs'         => $find('GET', '/api/dosen/assessment-dosen/mahasiswa'),
            // Staff
            'staff_ruangan'          => $find('GET', '/api/staff/ruangan'),
            'staff_hari'             => $find('GET', '/api/staff/hari'),
            'staff_kelas'            => $find('GET', '/api/staff/kelas'),
            'staff_status_kelas'     => $find('GET', '/api/staff/jadwal-kelas/status-kelas'),
            'staff_jadwal'           => $find('GET', '/api/staff/jadwal-kelas'),
            'staff_kelas_group'      => $find('GET', '/api/staff/kelas-group'),
            'staff_tahun_akademik'   => $find('GET', '/api/staff/master-data/tahun-akademik'),
            'staff_matakuliah'       => $find('GET', '/api/staff/master-data/matakuliah'),
            'staff_dosen'            => $find('GET', '/api/staff/master-data/dosen'),
            'staff_mahasiswa'        => $find('GET', '/api/staff/master-data/mahasiswa'),
            'staff_prodi'            => $find('GET', '/api/staff/master-data/program-studi'),
            'staff_nama_kurikulum'   => $find('GET', '/api/staff/master-data/nama-kurikulum'),
            'staff_kurikulum_angk'   => $find('GET', '/api/staff/master-data/kurikulum-angkatan'),
            'staff_perwalian'        => $find('GET', '/api/staff/akademik/perwalian'),
            'staff_krs'              => $find('GET', '/api/staff/akademik/krs'),
            'staff_penempatan_belum' => $find('GET', '/api/staff/penempatan/belum-kelas'),
            'staff_penempatan'       => $find('GET', '/api/staff/penempatan'),
            'staff_pembayaran'       => $find('GET', '/api/staff/pembayaran'),
            'staff_sks_rule'         => $find('GET', '/api/staff/sks-rule'),
            'staff_templates'        => $find('GET', '/api/staff/assessment/templates'),
            'staff_grade'            => $find('GET', '/api/staff/assessment/grade'),
            'staff_assess_assign'    => $find('GET', '/api/staff/assessment/dosen/assignments'),
            'staff_assess_create'    => $find('POST', '/api/staff/assessment/dosen'),
        ];
    }

    /**
     * @return array{0: string, 1: ?int, 2: ?string, 3: string}
     */
    private function resolveParamSource(ApiEndpoint $ep, string $role, string $paramName, array $lookup, $endpoints): array
    {
        $url = '/' . ltrim($ep->url, '/');
        $lower = strtolower($paramName);

        // 1. Endpoint Auth selalu manual input
        if (str_starts_with($url, '/api/auth/')) {
            return ['input', null, null, 'Input kredensial / parameter auth pengguna'];
        }

        // 2. Field non-relasional (input manual) jangan pernah diarahkan ke endpoint
        $pureInputNames = [
            'nama', 'nama_mahasiswa', 'nama_dosen', 'nama_matakuliah', 'nama_program_studi',
            'singkatan_program_studi', 'nama_kurikulum', 'nama_ruangan', 'nama_kelas', 'nama_group',
            'kode_matakuliah', 'kode_ruangan', 'status_dosen', 'status_krs', 'status_mahasiswa',
            'status_pendaftaran', 'status_aktif', 'status_kpat', 'status', 'aktif', 'kompetensi',
            'jenis', 'block', 'sks_teori', 'sks_praktik', 'sks_yang_dapat_diambil', 'sks_override',
            'sks_override_reason', 'ip_min', 'ip_max', 'nilai_min', 'nilai_max', 'nilai_akhir',
            'huruf', 'grade', 'skor', 'score', 'notes', 'catatan', 'keterangan', 'jam_mulai',
            'jam_selesai', 'angkatan', 'angkatan1', 'ekstensi', 'ekstensi1', 'paket', 'paket1',
            'semester', 'per_page', 'search', 'refresh', 'nik', 'nisn', 'npm', 'nomor_pendaftaran',
            'nomor_pendaftaran_ulang', 'tempat_lahir', 'tanggal_lahir', 'tanggal_mulai',
            'tanggal_berakhir', 'tanggal_buka_krs', 'tanggal_tutup_krs', 'tanggal_bayar',
            'alamat', 'kota', 'propinsi', 'telepon', 'no_telp', 'jenis_kelamin', 'agama',
            'golongan_darah', 'kewarganegaraan', 'nama_instansi', 'email', 'alamat_email',
            'nama_ayah', 'agama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'agama_ibu', 'pekerjaan_ibu',
            'alamat_orangtua', 'kota_orangtua', 'propinsi_orangtua', 'telepon_orangtua',
            'foto', 'sandi', 'sandi_pengguna', 'password', 'field_studi', 'alumni', 'chatid',
            'kapasitas', 'lokasi', 'versi', 'structure', 'assignments', 'mahasiswa', 'nim_list',
            'from_node_key', 'to_node_key', 'tahun_akademik',
        ];

        if (in_array($lower, $pureInputNames, true) || str_ends_with($lower, '.score') || str_ends_with($lower, '.scores') || str_ends_with($lower, '.notes') || str_ends_with($lower, '.catatan')) {
            return ['input', null, null, 'Input manual pengguna'];
        }

        // 3. Pemetaan Mahasiswa (/api/mhs/*)
        if ($role === 'Mahasiswa') {
            if (in_array($lower, ['matakuliah', 'matakuliah.*'], true)) {
                return ['endpoint', $lookup['mhs_kurikulum'], 'data_kurikulum[].matakuliah[].code_matakuliah', 'Kode matakuliah dari GET /api/mhs/kurikulum'];
            }
            if ($lower === 'code_krs_detail') {
                return ['endpoint', $lookup['mhs_krs'], 'krs.detail[].code', 'Kode detail KRS dari GET /api/mhs/krs'];
            }
            return ['input', null, null, 'Input manual / parameter opsional mahasiswa'];
        }

        // 4. Pemetaan Dosen (/api/dosen/*)
        if ($role === 'Dosen') {
            if ($url === '/api/dosen/detail' && $lower === 'code') {
                return ['endpoint', $lookup['dosen_list'], 'data[].code', 'Kode dosen dari GET /api/dosen'];
            }
            if ($url === '/api/dosen' && $ep->method === 'PUT' && $lower === 'code') {
                return ['endpoint', $lookup['dosen_list'], 'data[].code', 'Kode dosen milik sendiri dari GET /api/dosen/me'];
            }
            if ($url === '/api/dosen/kurikulum/detail' && $lower === 'code') {
                return ['endpoint', $lookup['dosen_kurikulum_kelas'], 'data[].code_kelas', 'Kode kelas dari GET /api/dosen/kurikulum/kelas'];
            }
            if (str_contains($url, '/dosen/perwalian/krs') && $lower === 'code') {
                return ['endpoint', $lookup['dosen_perwalian_daftar'], 'perwalian[].code', 'Kode KRS mahasiswa bimbingan dari GET /api/dosen/perwalian/daftar'];
            }
            if (str_contains($url, '/dosen/perwalian/validasi') && $lower === 'code_mahasiswa') {
                return ['endpoint', $lookup['dosen_perwalian_daftar'], 'perwalian[].code', 'Kode mahasiswa bimbingan dari GET /api/dosen/perwalian/daftar'];
            }
            if (str_contains($url, '/dosen/perwalian/batal') && $lower === 'code_perwalian') {
                return ['endpoint', $lookup['dosen_perwalian_riwayat'], 'perwalian[].code', 'Kode perwalian dari GET /api/dosen/perwalian/riwayat'];
            }
            if (str_contains($url, '/dosen/penilaian/')) {
                if ($lower === 'code_kelas') {
                    return ['endpoint', $lookup['dosen_penilaian_kelas'], 'data[].code_kelas', 'Kode kelas dari GET /api/dosen/penilaian/kelas'];
                }
                if ($lower === 'code_mahasiswa' || str_ends_with($lower, '.code_mahasiswa')) {
                    return ['endpoint', $lookup['dosen_penilaian_mhs'], 'data[].code_mahasiswa', 'Kode mahasiswa dari GET /api/dosen/penilaian/mahasiswa'];
                }
                if (str_ends_with($lower, '.node_key')) {
                    return ['endpoint', $lookup['dosen_my_nodes'], 'data[].node_key', 'Node key dari template / GET /api/dosen/assessment-dosen/my-nodes'];
                }
            }
            if (str_contains($url, '/dosen/kaprodi/penilaian/')) {
                if ($lower === 'code_kelas') {
                    return ['endpoint', $lookup['kaprodi_kelas'], 'data[].code_kelas', 'Kode kelas prodi dari GET /api/dosen/kaprodi/penilaian/kelas'];
                }
                if ($lower === 'code_mahasiswa') {
                    return ['endpoint', $lookup['kaprodi_mhs'], 'data[].code_mahasiswa', 'Kode mahasiswa dari GET /api/dosen/kaprodi/penilaian/mahasiswa'];
                }
            }
            if (str_contains($url, '/dosen/assessment')) {
                if ($lower === 'assessment_dosen_id') {
                    return ['endpoint', $lookup['dosen_my_nodes'], 'data[].assessment_dosen_id', 'ID AssessmentDosen dari GET /api/dosen/assessment-dosen/my-nodes'];
                }
                if ($lower === 'node_key') {
                    return ['endpoint', $lookup['dosen_my_nodes'], 'data[].node_key', 'Node key tugas dari GET /api/dosen/assessment-dosen/my-nodes'];
                }
                if ($lower === 'code_mahasiswa' || str_ends_with($lower, '.code_mahasiswa')) {
                    return ['endpoint', $lookup['dosen_node_mhs'], 'mahasiswa[].code_mahasiswa', 'Kode mahasiswa dari GET /api/dosen/assessment-dosen/mahasiswa'];
                }
            }
            return ['input', null, null, 'Input manual dosen'];
        }

        // 5. Pemetaan Staff (/api/staff/*)
        if ($lower === 'assessment_dosen_id') {
            return ['endpoint', $lookup['staff_assess_assign'] ?? $lookup['staff_assess_create'], 'assessment_dosen_id', 'Dari GET /api/staff/assessment/dosen/assignments atau POST /api/staff/assessment/dosen'];
        }
        if ($lower === 'node_key' || str_ends_with($lower, '.node_key')) {
            if (str_contains($url, 'kelas-group')) {
                return ['endpoint', $endpoints->firstWhere('url', '/api/staff/kelas-group/active-nodes')?->id, 'nodes[].key', 'Node key aktif dari GET /api/staff/kelas-group/active-nodes'];
            }
            return ['endpoint', $lookup['staff_assess_assign'], 'available_nodes[].node_key', 'Node key dari GET /api/staff/assessment/dosen/assignments'];
        }
        if ($lower === 'assignments.*.kode_dosen') {
            return ['endpoint', $lookup['staff_assess_assign'], 'dosen_pengajar[].kode_dosen', 'Kode dosen pengajar kelas dari GET /api/staff/assessment/dosen/assignments'];
        }
        if ($lower === 'code_group') {
            return ['endpoint', $lookup['staff_kelas_group'], 'data[].code_group', 'Kode group dari GET /api/staff/kelas-group'];
        }
        if ($lower === 'code_krs_detail') {
            return ['endpoint', $lookup['staff_penempatan_belum'], 'data[].code_krs_detail', 'Kode detail KRS dari GET /api/staff/penempatan/belum-kelas'];
        }
        if ($lower === 'code_krs') {
            return ['endpoint', $lookup['staff_krs'], 'data[].code_krs', 'Kode KRS dari GET /api/staff/akademik/krs'];
        }
        if ($lower === 'code_ruangan' || ($lower === 'code' && str_contains($url, '/ruangan'))) {
            return ['endpoint', $lookup['staff_ruangan'], 'data[].code_ruangan', 'Kode ruangan dari GET /api/staff/ruangan'];
        }
        if ($lower === 'code_hari' || ($lower === 'code' && str_contains($url, '/hari'))) {
            return ['endpoint', $lookup['staff_hari'], 'data[].code_hari', 'Kode hari dari GET /api/staff/hari'];
        }
        if ($lower === 'code_kelas' || $lower === 'target_code_kelas' || $lower === 'kelas_id' || ($lower === 'code' && str_contains($url, '/kelas'))) {
            return ['endpoint', $lookup['staff_kelas'], 'data[].code_kelas', 'Kode/ID kelas dari GET /api/staff/kelas'];
        }
        if ($lower === 'code' && str_contains($url, '/jadwal-kelas')) {
            if (str_contains($url, '/jadwal-kelas/kelas')) {
                return ['endpoint', $lookup['staff_kelas'], 'data[].code_kelas', 'Kode kelas dari GET /api/staff/kelas'];
            }
            if (str_contains($url, '/jadwal-kelas/ruangan')) {
                return ['endpoint', $lookup['staff_ruangan'], 'data[].code_ruangan', 'Kode ruangan dari GET /api/staff/ruangan'];
            }
            if (str_contains($url, '/jadwal-kelas/hari')) {
                return ['endpoint', $lookup['staff_hari'], 'data[].code_hari', 'Kode hari dari GET /api/staff/hari'];
            }
            return ['endpoint', $lookup['staff_jadwal'], 'data[].code_jadwal', 'Kode jadwal dari GET /api/staff/jadwal-kelas'];
        }
        if ($lower === 'code' && str_contains($url, '/feeder/') && str_contains($url, '/kelas')) {
            return ['endpoint', $lookup['staff_kelas'], 'data[].code_kelas', 'Kode kelas dari GET /api/staff/kelas'];
        }
        if (($lower === 'nim' || $lower === 'code') && str_contains($url, '/feeder/') && str_contains($url, '/mahasiswa')) {
            return ['endpoint', $lookup['staff_mahasiswa'], 'data[].nim', 'NIM mahasiswa dari GET /api/staff/master-data/mahasiswa'];
        }
        if (in_array($lower, ['code_tahun_akademik', 'kode_tahun_akademik'], true) || ($lower === 'code' && str_contains($url, 'tahun-akademik'))) {
            return ['endpoint', $lookup['staff_tahun_akademik'], 'data[].code', 'Tahun akademik dari GET /api/staff/master-data/tahun-akademik'];
        }
        if ($lower === 'code_matakuliah' || ($lower === 'code' && str_contains($url, 'matakuliah'))) {
            return ['endpoint', $lookup['staff_matakuliah'], 'data[].code', 'Kode matakuliah dari GET /api/staff/master-data/matakuliah'];
        }
        if (in_array($lower, ['code_dosen', 'kode_dosen', 'code_dosen_kaprodi', 'code_dosen_perwakilan'], true) || ($lower === 'code' && str_contains($url, 'master-data/dosen'))) {
            return ['endpoint', $lookup['staff_dosen'], 'data[].code', 'Kode dosen dari GET /api/staff/master-data/dosen'];
        }
        if (in_array($lower, ['code_program_studi', 'kode_program_studi', 'program_studi_kode'], true) || ($lower === 'code' && str_contains($url, 'program-studi'))) {
            return ['endpoint', $lookup['staff_prodi'], 'data[].code', 'Kode program studi dari GET /api/staff/master-data/program-studi'];
        }
        if (in_array($lower, ['code_nama_kurikulum', 'kode_nama_kurikulum'], true) || ($lower === 'code' && str_contains($url, 'nama-kurikulum'))) {
            return ['endpoint', $lookup['staff_nama_kurikulum'], 'data[].code_nama_kurikulum', 'Kode nama kurikulum dari GET /api/staff/master-data/nama-kurikulum'];
        }
        if ($lower === 'code_kurikulum_angkatan' || ($lower === 'code' && str_contains($url, 'kurikulum-angkatan'))) {
            return ['endpoint', $lookup['staff_kurikulum_angk'], 'data[].code', 'Kode kurikulum angkatan dari GET /api/staff/master-data/kurikulum-angkatan'];
        }
        if (in_array($lower, ['code_template', 'template_code'], true) || ($lower === 'code' && str_contains($url, 'templates'))) {
            return ['endpoint', $lookup['staff_templates'], 'data[].code', 'Kode template dari GET /api/staff/assessment/templates'];
        }
        if ($lower === 'code' && str_contains($url, 'sks-rule')) {
            return ['endpoint', $lookup['staff_sks_rule'], 'data[].code', 'Kode SKS rule dari GET /api/staff/sks-rule'];
        }
        if ($lower === 'code' && str_contains($url, 'assessment/grade')) {
            return ['endpoint', $lookup['staff_grade'], 'data[].code', 'Kode grade dari GET /api/staff/assessment/grade'];
        }
        if ($lower === 'code' && str_contains($url, 'pembayaran')) {
            return ['endpoint', $lookup['staff_pembayaran'], 'data[].code', 'Kode pembayaran dari GET /api/staff/pembayaran'];
        }
        if ($lower === 'code' && str_contains($url, 'penempatan')) {
            return ['endpoint', $lookup['staff_penempatan'], 'data[].kelas_mahasiswa_id', 'ID penempatan dari GET /api/staff/penempatan'];
        }
        if ($lower === 'code' && str_contains($url, 'perwalian')) {
            if ($ep->method === 'POST') {
                return ['endpoint', $lookup['staff_mahasiswa'], 'data[].code', 'Encrypted NIM mahasiswa dari GET /api/staff/master-data/mahasiswa'];
            }
            return ['endpoint', $lookup['staff_perwalian'], 'data[].code', 'Kode perwalian dari GET /api/staff/akademik/perwalian'];
        }
        if ($lower === 'nim' || $lower === 'code_mahasiswa' || str_ends_with($lower, '.code_mahasiswa') || ($lower === 'code' && (str_contains($url, 'mahasiswa') || str_contains($url, '/akademik/') || str_contains($url, 'assessment/scores') || str_contains($url, 'students/score')))) {
            if ($ep->method === 'POST' && str_contains($url, 'master-data/mahasiswa')) {
                return ['input', null, null, 'Input NIM baru oleh staff'];
            }
            return ['endpoint', $lookup['staff_mahasiswa'], 'data[].nim', 'NIM / code mahasiswa dari GET /api/staff/master-data/mahasiswa'];
        }
        if (in_array($lower, ['token', 'csrf', 'xsrf', 'session'], true)) {
            return ['session', null, null, 'Diisi otomatis oleh cookie session Sanctum'];
        }

        return ['input', null, null, 'Input manual pengguna'];
    }
}

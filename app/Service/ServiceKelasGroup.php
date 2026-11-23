<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentDosen;
use App\Models\AssessmentTemplate;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasGroup;
use App\Models\KelasGroupMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceKelasGroup
{
    /**
     * Mengambil daftar node asesmen yang statusnya ON/aktif untuk suatu kelas.
     * Mendukung node di level 1, 2, atau 3.
     */
    public function getActiveNodes(string $codeKelas): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $assessment = AssessmentDosen::where('kelas_id', $kelas->kelas_id)->first();
        $assessmentStatus = $assessment?->status ?? 'belum_dibuat';
        $isAssessmentActive = ($assessmentStatus === 'aktif');

        $template = AssessmentTemplate::where('id_matakuliah', $kelas->id_matakuliah)
            ->active()
            ->orderByDesc('versi')
            ->first();

        if (! $template) {
            return ApiResponse::notFound('Template asesmen untuk matakuliah kelas ini belum tersedia.');
        }

        // Ambil semua node dari struktur template asesmen
        $allNodes = [];
        $this->traverseNodes($template->structure, null, 0, $allNodes);

        // Ambil data group yang sudah dibuat di kelas ini
        $existingGroups = KelasGroup::with(['dosen', 'groupMahasiswa'])
            ->where('kelas_id', $kelas->kelas_id)
            ->get()
            ->groupBy('node_key');

        $resultNodes = array_map(function ($node) use ($existingGroups) {
            $nodeKey = $node['key'];
            $groups = $existingGroups->get($nodeKey, collect());

            return [
                'key'            => $nodeKey,
                'name'           => $node['name'],
                'level'          => $node['level'],
                'type'           => $node['type'],
                'weight'         => $node['weight'],
                'parent_key'     => $node['parent_key'],
                'status'         => $node['status'], // 'on' atau 'off'
                'is_active_node' => ($node['status'] === 'on'),
                'groups_count'   => $groups->count(),
                'total_assigned_mahasiswa' => $groups->sum(fn ($g) => $g->groupMahasiswa->count()),
                'groups'         => $groups->map(fn ($g) => [
                    'code_group'       => $g->toCode(),
                    'kelas_group_id'   => $g->id,
                    'nama_group'       => $g->nama_group,
                    'dosen'            => $g->dosen ? [
                        'code_dosen' => $g->dosen->toCode(),
                        'kode_dosen' => $g->dosen->kode_dosen,
                        'nama_dosen' => $g->dosen->nama_dosen,
                    ] : null,
                    'jumlah_mahasiswa' => $g->groupMahasiswa->count(),
                ])->values()->toArray(),
            ];
        }, $allNodes);

        return ApiResponse::success([
            'kelas' => [
                'code_kelas'      => $kelas->toCode(),
                'kelas_id'        => $kelas->kelas_id,
                'nama_kelas'      => $kelas->namaKelas?->nama_kelas,
                'semester'        => $kelas->semester,
                'matakuliah'      => $kelas->matakuliah?->nama_matakuliah,
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah,
            ],
            'assessment' => [
                'assessment_dosen_id' => $assessment?->id,
                'status'              => $assessmentStatus,
                'is_active'           => $isAssessmentActive,
                'can_assign_dosen'    => $isAssessmentActive,
                'description'         => $isAssessmentActive
                    ? 'Asesmen aktif. Dosen dan group dapat dimasukkan/diatur.'
                    : 'Asesmen non-aktif. Penugasan dosen dan group dikunci.',
            ],
            'nodes' => $resultNodes,
        ], 'Daftar node asesmen kelas berhasil diambil.');
    }

    /**
     * Mengambil daftar group beserta dosen dan mahasiswa pada node tertentu (atau seluruh kelas).
     */
    public function getGroups(string $codeKelas, ?string $nodeKey = null): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $query = KelasGroup::with(['dosen', 'groupMahasiswa.mahasiswa'])
            ->where('kelas_id', $kelas->kelas_id);

        if (! empty($nodeKey)) {
            $query->where('node_key', $nodeKey);
        }

        $groups = $query->orderBy('node_key')->orderBy('nama_group')->get();

        $data = $groups->map(fn ($g) => [
            'code_group'     => $g->toCode(),
            'kelas_group_id' => $g->id,
            'kelas_id'       => $g->kelas_id,
            'node_key'       => $g->node_key,
            'nama_group'     => $g->nama_group,
            'dosen'          => $g->dosen ? [
                'code_dosen' => $g->dosen->toCode(),
                'kode_dosen' => $g->dosen->kode_dosen,
                'nama_dosen' => $g->dosen->nama_dosen,
            ] : null,
            'total_mahasiswa' => $g->groupMahasiswa->count(),
            'mahasiswa'       => $g->groupMahasiswa->map(fn ($gm) => [
                'nim'            => $gm->nim,
                'nama_mahasiswa' => $gm->mahasiswa?->nama_mahasiswa,
            ])->values()->toArray(),
        ])->values()->toArray();

        return ApiResponse::success($data, 'Data group kelas berhasil diambil.');
    }

    /**
     * Membuat group baru pada suatu node yang ON di kelas.
     * WAJIB cek status asesmen: hanya jika status === 'aktif'.
     */
    public function storeGroup(array $data): JsonResponse
    {
        $kelas = Kelas::findByCode($data['code_kelas']);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        // Cek status assessment: jika non-aktif, tolak penugasan
        $assessment = AssessmentDosen::where('kelas_id', $kelas->kelas_id)->first();
        if (! $assessment || $assessment->status !== 'aktif') {
            return ApiResponse::error(
                'Asesmen kelas sedang non-aktif. Tidak dapat membuat group atau menugaskan dosen.',
                422
            );
        }

        $nodeKey = trim($data['node_key']);
        $namaGroup = trim($data['nama_group']);

        // Cek duplikasi nama group di node yang sama pada kelas ini
        $exists = KelasGroup::where('kelas_id', $kelas->kelas_id)
            ->where('node_key', $nodeKey)
            ->where('nama_group', $namaGroup)
            ->exists();

        if ($exists) {
            return ApiResponse::error("Group '{$namaGroup}' sudah ada untuk node '{$nodeKey}' pada kelas ini.", 422);
        }

        $kodeDosen = null;
        if (! empty($data['code_dosen'])) {
            $dosen = Dosen::findByCode($data['code_dosen']);
            if (! $dosen) {
                return ApiResponse::notFound('Data dosen tidak ditemukan.');
            }
            $kodeDosen = $dosen->kode_dosen;
        }

        DB::beginTransaction();
        try {
            $group = KelasGroup::create([
                'kelas_id'   => $kelas->kelas_id,
                'node_key'   => $nodeKey,
                'nama_group' => $namaGroup,
                'kode_dosen' => $kodeDosen,
            ]);

            $nimList = $data['nim_list'] ?? [];
            if (! empty($nimList)) {
                $insertData = [];
                foreach (array_unique($nimList) as $nim) {
                    $insertData[] = [
                        'kelas_group_id' => $group->id,
                        'nim'            => $nim,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
                KelasGroupMahasiswa::insert($insertData);
            }

            DB::commit();

            $group->load(['dosen', 'groupMahasiswa.mahasiswa']);

            return ApiResponse::success([
                'code_group'      => $group->toCode(),
                'kelas_group_id'  => $group->id,
                'kelas_id'        => $group->kelas_id,
                'node_key'        => $group->node_key,
                'nama_group'      => $group->nama_group,
                'dosen'           => $group->dosen ? [
                    'code_dosen' => $group->dosen->toCode(),
                    'kode_dosen' => $group->dosen->kode_dosen,
                    'nama_dosen' => $group->dosen->nama_dosen,
                ] : null,
                'total_mahasiswa' => $group->groupMahasiswa->count(),
            ], 'Group berhasil dibuat.', 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal membuat group: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui group (mengubah dosen, nama group, atau komposisi mahasiswa).
     * WAJIB cek status asesmen: hanya jika status === 'aktif'.
     */
    public function updateGroup(string $codeGroup, array $data): JsonResponse
    {
        $group = KelasGroup::findByCode($codeGroup);
        if (! $group) {
            return ApiResponse::notFound('Group tidak ditemukan.');
        }

        // Cek status assessment
        $assessment = AssessmentDosen::where('kelas_id', $group->kelas_id)->first();
        if (! $assessment || $assessment->status !== 'aktif') {
            return ApiResponse::error(
                'Asesmen kelas sedang non-aktif. Tidak dapat mengubah group atau dosen.',
                422
            );
        }

        DB::beginTransaction();
        try {
            if (isset($data['nama_group'])) {
                $group->nama_group = trim($data['nama_group']);
            }

            if (array_key_exists('code_dosen', $data)) {
                if (! empty($data['code_dosen'])) {
                    $dosen = Dosen::findByCode($data['code_dosen']);
                    if (! $dosen) {
                        return ApiResponse::notFound('Data dosen tidak ditemukan.');
                    }
                    $group->kode_dosen = $dosen->kode_dosen;
                } else {
                    $group->kode_dosen = null;
                }
            }

            $group->save();

            // Sync mahasiswa jika nim_list dikirimkan
            if (isset($data['nim_list']) && is_array($data['nim_list'])) {
                $group->groupMahasiswa()->delete();
                $insertData = [];
                foreach (array_unique($data['nim_list']) as $nim) {
                    $insertData[] = [
                        'kelas_group_id' => $group->id,
                        'nim'            => $nim,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
                if (! empty($insertData)) {
                    KelasGroupMahasiswa::insert($insertData);
                }
            }

            DB::commit();

            $group->load(['dosen', 'groupMahasiswa.mahasiswa']);

            return ApiResponse::success([
                'code_group'      => $group->toCode(),
                'kelas_group_id'  => $group->id,
                'nama_group'      => $group->nama_group,
                'node_key'        => $group->node_key,
                'dosen'           => $group->dosen ? [
                    'code_dosen' => $group->dosen->toCode(),
                    'kode_dosen' => $group->dosen->kode_dosen,
                    'nama_dosen' => $group->dosen->nama_dosen,
                ] : null,
                'total_mahasiswa' => $group->groupMahasiswa->count(),
            ], 'Group berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal memperbarui group: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus group.
     */
    public function deleteGroup(string $codeGroup): JsonResponse
    {
        $group = KelasGroup::findByCode($codeGroup);
        if (! $group) {
            return ApiResponse::notFound('Group tidak ditemukan.');
        }

        $assessment = AssessmentDosen::where('kelas_id', $group->kelas_id)->first();
        if (! $assessment || $assessment->status !== 'aktif') {
            return ApiResponse::error(
                'Asesmen kelas sedang non-aktif. Tidak dapat menghapus group.',
                422
            );
        }

        $group->delete();

        return ApiResponse::success(null, 'Group berhasil dihapus.');
    }

    /**
     * Menyalin komposisi group dan mahasiswa dari satu node ke node lain pada kelas yang sama.
     */
    public function copyGroups(string $codeKelas, string $fromNodeKey, string $toNodeKey): JsonResponse
    {
        $kelas = Kelas::findByCode($codeKelas);
        if (! $kelas) {
            return ApiResponse::notFound('Kelas tidak ditemukan.');
        }

        $assessment = AssessmentDosen::where('kelas_id', $kelas->kelas_id)->first();
        if (! $assessment || $assessment->status !== 'aktif') {
            return ApiResponse::error(
                'Asesmen kelas sedang non-aktif. Tidak dapat menyalin group.',
                422
            );
        }

        $sourceGroups = KelasGroup::with('groupMahasiswa')
            ->where('kelas_id', $kelas->kelas_id)
            ->where('node_key', $fromNodeKey)
            ->get();

        if ($sourceGroups->isEmpty()) {
            return ApiResponse::notFound("Tidak ada group sumber pada node '{$fromNodeKey}'.");
        }

        DB::beginTransaction();
        try {
            // Hapus group target yang sudah ada jika ada
            KelasGroup::where('kelas_id', $kelas->kelas_id)
                ->where('node_key', $toNodeKey)
                ->delete();

            $copiedCount = 0;
            foreach ($sourceGroups as $src) {
                $newGroup = KelasGroup::create([
                    'kelas_id'   => $kelas->kelas_id,
                    'node_key'   => $toNodeKey,
                    'nama_group' => $src->nama_group,
                    'kode_dosen' => $src->kode_dosen, // Dosen awal disalin, bisa diubah nanti
                ]);

                $mhsData = [];
                foreach ($src->groupMahasiswa as $gm) {
                    $mhsData[] = [
                        'kelas_group_id' => $newGroup->id,
                        'nim'            => $gm->nim,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }

                if (! empty($mhsData)) {
                    KelasGroupMahasiswa::insert($mhsData);
                }

                $copiedCount++;
            }

            DB::commit();

            return ApiResponse::success([
                'copied_groups' => $copiedCount,
                'from_node'     => $fromNodeKey,
                'to_node'       => $toNodeKey,
            ], "Berhasil menyalin {$copiedCount} group ke node '{$toNodeKey}'.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal menyalin group: ' . $e->getMessage());
        }
    }

    // ─── Private Helpers ────────────────────────────────────────────────────────

    private function traverseNodes(array $node, ?string $parentKey, int $level, array &$nodes): void
    {
        $key = $node['key'] ?? '';
        $name = $node['name'] ?? '';
        $type = $node['type'] ?? 'category';
        $weight = $node['weight'] ?? 0;

        // Tentukan status ON/OFF node
        // Jika ada status eksplisit ('on' / 'off') atau boolean is_active
        $status = 'on';
        if (isset($node['status'])) {
            $status = strtolower($node['status']) === 'off' ? 'off' : 'on';
        } elseif (isset($node['is_active']) && $node['is_active'] === false) {
            $status = 'off';
        } elseif (isset($node['is_on']) && $node['is_on'] === false) {
            $status = 'off';
        }

        // Jangan masukkan root node ke daftar assignable nodes jika root hanya nama blok
        if ($level > 0) {
            $nodes[] = [
                'key'        => $key,
                'name'       => $name,
                'level'      => $level,
                'type'       => $type,
                'weight'     => $weight,
                'parent_key' => $parentKey,
                'status'     => $status,
            ];
        }

        if (! empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->traverseNodes($child, $key, $level + 1, $nodes);
            }
        }
    }
}

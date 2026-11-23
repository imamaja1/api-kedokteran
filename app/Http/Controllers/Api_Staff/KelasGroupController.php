<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceKelasGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasGroupController extends Controller
{
    public function __construct(
        private readonly ServiceKelasGroup $service,
    ) {}

    /**
     * GET /api/staff/kelas-group/active-nodes
     * Mengambil daftar node asesmen (ON/OFF) untuk suatu kelas.
     */
    public function activeNodes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => ['required', 'string'],
        ]);

        return $this->service->getActiveNodes($validated['code_kelas']);
    }

    /**
     * GET /api/staff/kelas-group
     * Mengambil daftar group dan anggotanya pada node tertentu atau seluruh kelas.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => ['required', 'string'],
            'node_key'   => ['nullable', 'string'],
        ]);

        return $this->service->getGroups($validated['code_kelas'], $validated['node_key'] ?? null);
    }

    /**
     * POST /api/staff/kelas-group
     * Membuat group baru pada node yang ON dan menugaskan dosen serta mahasiswa.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas' => ['required', 'string'],
            'node_key'   => ['required', 'string'],
            'nama_group' => ['required', 'string', 'max:50'],
            'code_dosen' => ['nullable', 'string'],
            'nim_list'   => ['nullable', 'array'],
            'nim_list.*' => ['required', 'string', 'max:15'],
        ]);

        return $this->service->storeGroup($validated);
    }

    /**
     * PUT /api/staff/kelas-group
     * Memperbarui group (ganti nama, ganti dosen, atau ubah susunan mahasiswa).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_group' => ['required', 'string'],
            'nama_group' => ['nullable', 'string', 'max:50'],
            'code_dosen' => ['nullable', 'string'],
            'nim_list'   => ['nullable', 'array'],
            'nim_list.*' => ['required', 'string', 'max:15'],
        ]);

        $codeGroup = $validated['code_group'];
        unset($validated['code_group']);

        return $this->service->updateGroup($codeGroup, $validated);
    }

    /**
     * DELETE /api/staff/kelas-group
     * Menghapus group.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_group' => ['required', 'string'],
        ]);

        return $this->service->deleteGroup($validated['code_group']);
    }

    /**
     * POST /api/staff/kelas-group/copy
     * Menyalin komposisi group dan mahasiswa dari satu node ke node lain.
     */
    public function copy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_kelas'    => ['required', 'string'],
            'from_node_key' => ['required', 'string'],
            'to_node_key'   => ['required', 'string'],
        ]);

        return $this->service->copyGroups(
            $validated['code_kelas'],
            $validated['from_node_key'],
            $validated['to_node_key']
        );
    }
}

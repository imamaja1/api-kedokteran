<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ProgramStudiController extends Controller
{
    public function __construct(
        private readonly ServiceProgramStudi $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->service->getAllProgramStudi();
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($request->query('code'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code program studi tidak valid',
            ], 422);
        }

        return $this->service->getOneProgramStudi($id);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
            'code_dosen_kaprodi' => ['nullable', 'string'],
        ]);

        if (! empty($validasi['code_dosen_kaprodi'])) {
            try {
                $validasi['kode_dosen_kaprodi'] = (int) Crypt::decryptString($validasi['code_dosen_kaprodi']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Format code_dosen_kaprodi tidak valid',
                ], 422);
            }
        }
        unset($validasi['code_dosen_kaprodi']);

        return $this->service->storeProgramStudi($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
            'code_dosen_kaprodi' => ['nullable', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($validasi['code']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code program studi tidak valid',
            ], 422);
        }
        unset($validasi['code']);

        if (array_key_exists('code_dosen_kaprodi', $validasi)) {
            if (! empty($validasi['code_dosen_kaprodi'])) {
                try {
                    $validasi['kode_dosen_kaprodi'] = (int) Crypt::decryptString($validasi['code_dosen_kaprodi']);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Format code_dosen_kaprodi tidak valid',
                    ], 422);
                }
            } else {
                $validasi['kode_dosen_kaprodi'] = null;
            }
            unset($validasi['code_dosen_kaprodi']);
        }

        return $this->service->updateProgramStudi($id, $validasi);
    }

    public function getKaprodi(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($request->query('code'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code program studi tidak valid',
            ], 422);
        }

        return $this->service->getKaprodi($id);
    }

    public function setKaprodi(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'code_dosen' => ['nullable', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($validasi['code']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code program studi tidak valid',
            ], 422);
        }

        $kodeDosen = null;
        if (! empty($validasi['code_dosen'])) {
            try {
                $kodeDosen = (int) Crypt::decryptString($validasi['code_dosen']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Format code dosen tidak valid',
                ], 422);
            }
        }

        return $this->service->setKaprodi($id, $kodeDosen);
    }

    public function destroy(Request $request, ?string $code = null): JsonResponse
    {
        $code = $code ?? $request->input('code');
        if (! $code) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter code wajib diisi',
            ], 422);
        }

        try {
            $id = Crypt::decryptString($code);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->deleteProgramStudi($id);
    }
}

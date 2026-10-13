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

        $id = Crypt::decryptString($request->query('code'));

        return $this->service->getOneProgramStudi($id);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
        ]);

        return $this->service->storeProgramStudi($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return $this->service->updateProgramStudi($id, $validasi);
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

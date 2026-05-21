<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceKurikulum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class NamaKurikulumController extends Controller
{
    public function __construct(
        private readonly ServiceKurikulum $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->service->nama_kurikulum();
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $decrypted = $this->decryptWithErrorHandling($request->query('code'));
        if ($decrypted instanceof JsonResponse) {
            return $decrypted;  // Error response
        }

        return $this->service->getOneNamaKurikulum($decrypted);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nama_kurikulum' => ['required', 'string', 'max:20'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
            'angkatan1' => ['nullable', 'string', 'max:255'],
            'ekstensi1' => ['nullable', 'in:Y,N'],
            'paket1' => ['nullable', 'in:Y,N'],
        ]);

        return $this->service->storeNamaKurikulum($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_kurikulum' => ['required', 'string', 'max:20'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
            'angkatan1' => ['nullable', 'string', 'max:255'],
            'ekstensi1' => ['nullable', 'in:Y,N'],
            'paket1' => ['nullable', 'in:Y,N'],
        ]);

        $decrypted = $this->decryptWithErrorHandling($validasi['code']);
        if ($decrypted instanceof JsonResponse) {
            return $decrypted;  // Error response
        }

        return $this->service->updateNamaKurikulum($decrypted, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $decrypted = $this->decryptWithErrorHandling($code);
        if ($decrypted instanceof JsonResponse) {
            return $decrypted;  // Error response
        }

        return $this->service->deleteNamaKurikulum($decrypted);
    }

    /**
     * Decrypt dengan error handling
     * @return string|JsonResponse - Returns decrypted string atau error response
     */
    private function decryptWithErrorHandling(string $encrypted): string|JsonResponse
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }
    }
}

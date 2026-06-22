<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServicePenempatan;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PenempatanController extends Controller
{
    public function __construct(
        private readonly ServicePenempatan $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'kelas_id' => ['nullable', 'integer'],
            'nim' => ['nullable', 'string', 'max:11'],
        ]);

        return $this->service->index($validasi);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code_krs_detail' => ['required', 'string'],
            'kelas_id' => ['required', 'integer'],
        ]);

        try {
            $kode_krs_detail = (int) Crypt::decryptString($validasi['code_krs_detail']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_krs_detail' => 'Format kode detail KRS tidak valid.']);
        }

        return $this->service->store([
            'kode_krs_detail' => $kode_krs_detail,
            'kelas_id' => $validasi['kelas_id'],
        ]);
    }

    public function destroy(Request $request, ?int $id = null): JsonResponse
    {
        $penempatanId = $id;

        if (! $penempatanId) {
            $request->validate([
                'code' => ['required', 'string'],
            ]);

            try {
                $penempatanId = (int) Crypt::decryptString($request->input('code'));
            } catch (DecryptException) {
                return ApiResponse::validation(['code' => 'Format kode penempatan tidak valid.']);
            }
        }

        return $this->service->destroy($penempatanId);
    }
}

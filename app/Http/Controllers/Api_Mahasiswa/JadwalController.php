<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceJadwalKelas;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class JadwalController extends Controller
{
    public function __construct(
        private readonly ServiceJadwalKelas $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! empty($payload['code_tahun_akademik'])) {
            try {
                $payload['kode_tahun_akademik'] = (int) Crypt::decryptString($payload['code_tahun_akademik']);
            } catch (DecryptException) {
                return ApiResponse::validation(['code_tahun_akademik' => 'Format code_tahun_akademik tidak valid.']);
            }
        }

        $validated = $request->validate([
            'kode_tahun_akademik' => ['nullable', 'integer'],
        ]);

        $nim = Auth::guard('mahasiswa_web')->user()->nim;
        $kodeTahunAkademik = $payload['kode_tahun_akademik'] ?? $validated['kode_tahun_akademik'] ?? null;

        return $this->service->getJadwalMahasiswa($nim, $kodeTahunAkademik);
    }
}

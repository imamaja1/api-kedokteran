<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceKelas;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class KelasController extends Controller
{
    public function __construct(
        private readonly ServiceKelas $service,
    ) {}

    public function matakuliahKonfigurasi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'kode_program_studi' => ['nullable', 'string', 'max:2'],
            'semester' => ['nullable', 'string', 'max:2'],
        ]);

        return $this->service->matakuliahKonfigurasi($validated);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'kode_program_studi' => ['nullable', 'string', 'max:2'],
            'semester' => ['nullable', 'string', 'max:2'],
            'code_matakuliah' => ['nullable', 'string'],
        ]);

        $filters = $validated;
        if (! empty($validated['code_matakuliah'])) {
            $decrypted = $this->decryptInteger($validated['code_matakuliah'], 'code_matakuliah');
            if ($decrypted instanceof JsonResponse) {
                return $decrypted;
            }
            $filters['id_matakuliah'] = $decrypted;
        }

        return $this->service->index($filters);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_matakuliah' => ['required', 'string'],
            'nama_kelas' => ['required', 'string', 'max:2'],
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'semester' => ['nullable', 'string', 'max:2'],
            'kode_program_studi' => ['nullable', 'string', 'max:2', 'exists:program_studi,kode_program_studi'],
        ]);

        $idMatakuliah = $this->decryptInteger($validated['code_matakuliah'], 'code_matakuliah');
        if ($idMatakuliah instanceof JsonResponse) {
            return $idMatakuliah;
        }

        $payload = $validated;
        $payload['id_matakuliah'] = $idMatakuliah;
        unset($payload['code_matakuliah']);

        return $this->service->store($payload);
    }

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer'],
        ]);

        return $this->service->show((int) $validated['kelas_id']);
    }

    public function mahasiswaBelumKelas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_matakuliah' => ['required', 'string'],
            'kode_tahun_akademik' => ['nullable', 'integer'],
        ]);

        $idMatakuliah = $this->decryptInteger($validated['code_matakuliah'], 'code_matakuliah');
        if ($idMatakuliah instanceof JsonResponse) {
            return $idMatakuliah;
        }

        return $this->service->mahasiswaBelumKelas([
            'id_matakuliah' => $idMatakuliah,
            'kode_tahun_akademik' => $validated['kode_tahun_akademik'] ?? null,
        ]);
    }

    public function storeDosen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer'],
            'code_dosen' => ['required', 'string'],
        ]);

        $kodeDosen = $this->decryptInteger($validated['code_dosen'], 'code_dosen');
        if ($kodeDosen instanceof JsonResponse) {
            return $kodeDosen;
        }

        return $this->service->storeDosen([
            'kelas_id' => (int) $validated['kelas_id'],
            'kode_dosen' => $kodeDosen,
        ]);
    }

    public function destroyDosen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mengajar_id' => ['required', 'integer'],
        ]);

        return $this->service->destroyDosen((int) $validated['mengajar_id']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer'],
        ]);

        return $this->service->destroy((int) $validated['kelas_id']);
    }

    private function decryptInteger(string $value, string $field): int|JsonResponse
    {
        try {
            return (int) Crypt::decryptString($value);
        } catch (DecryptException) {
            return ApiResponse::validation([$field => "Format {$field} tidak valid."]);
        }
    }
}

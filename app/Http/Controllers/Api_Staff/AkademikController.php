<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceKHS;
use App\Service\ServiceKRS;
use App\Service\ServiceKurikulum;
use App\Service\ServicePerwalian;
use App\Service\ServicePetikanNilai;
use App\Service\ServiceProgramStudi;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AkademikController extends Controller
{
    public function __construct(
        private readonly ServiceProgramStudi $programStudiService,
        private readonly ServiceKurikulum $kurikulumService,
        private readonly ServiceKRS $krsService,
        private readonly ServiceKHS $khsService,
        private readonly ServicePetikanNilai $petikanNilaiService,
        private readonly ServicePerwalian $perwalianService,
    ) {}

    public function program_studi(): JsonResponse
    {
        return $this->programStudiService->getAllProgramStudi();
    }

    public function nama_kurikulum(): JsonResponse
    {
        return $this->kurikulumService->nama_kurikulum();
    }

    public function kurikulum(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_nama_kurikulum' => ['required', 'string'],
        ]);

        try {
            $kode_nama_kurikulum = Crypt::decryptString($validated['code_nama_kurikulum']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_nama_kurikulum' => 'Format code tidak valid']);
        }

        return $this->kurikulumService->kurikulum_by_nama_kurikulum($kode_nama_kurikulum);
    }

    public function krs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->krsService->getAllKRS($nim);
    }

    public function krs_detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        try {
            $kode_krs = Crypt::decryptString($validated['code_krs']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_krs' => 'Format code tidak valid']);
        }

        return $this->krsService->getKRSDetail($kode_krs);
    }

    public function krs_matakuliah_tahun_akademik(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tahun_akademik' => ['required', 'string'],
        ]);

        return $this->krsService->getMatakuliahByTahunAkademik($validated['tahun_akademik']);
    }

    public function khs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->khsService->getAllKHS($nim);
    }

    public function khs_detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        try {
            $kode_krs = Crypt::decryptString($validated['code_krs']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_krs' => 'Format code tidak valid']);
        }

        return $this->khsService->getKHSDetail($kode_krs);
    }

    public function petikan_nilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->petikanNilaiService->getTranskrip($nim);
    }

    public function storePerwalian(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'code_dosen' => ['required', 'string'],
            'code_dosen_perwakilan' => ['nullable', 'string'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        $kodeDosen = $this->decryptIntegerCode($validated['code_dosen'], 'code_dosen');
        if ($kodeDosen instanceof JsonResponse) {
            return $kodeDosen;
        }

        $kodeDosenPerwakilan = null;
        if (isset($validated['code_dosen_perwakilan'])) {
            $kodeDosenPerwakilan = $this->decryptIntegerCode(
                $validated['code_dosen_perwakilan'],
                'code_dosen_perwakilan',
            );
            if ($kodeDosenPerwakilan instanceof JsonResponse) {
                return $kodeDosenPerwakilan;
            }
        }

        return $this->perwalianService->storePerwalian([
            'nim' => $nim,
            'kode_dosen' => $kodeDosen,
            'kode_dosen_perwakilan' => $kodeDosenPerwakilan,
        ]);
    }

    public function storeBatchPerwalian(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_dosen' => ['required', 'string'],
            'code_dosen_perwakilan' => ['nullable', 'string'],
            'codes' => ['required', 'array', 'min:1'],
            'codes.*' => ['required', 'string'],
        ]);

        $kodeDosen = $this->decryptIntegerCode($validated['code_dosen'], 'code_dosen');
        if ($kodeDosen instanceof JsonResponse) {
            return $kodeDosen;
        }

        $kodeDosenPerwakilan = null;
        if (isset($validated['code_dosen_perwakilan'])) {
            $kodeDosenPerwakilan = $this->decryptIntegerCode(
                $validated['code_dosen_perwakilan'],
                'code_dosen_perwakilan',
            );
            if ($kodeDosenPerwakilan instanceof JsonResponse) {
                return $kodeDosenPerwakilan;
            }
        }

        $nims = [];
        foreach ($validated['codes'] as $idx => $codeItem) {
            try {
                $nims[] = Crypt::decryptString($codeItem);
            } catch (DecryptException) {
                return ApiResponse::validation(["codes.{$idx}" => "Format code pada index {$idx} tidak valid"]);
            }
        }

        return $this->perwalianService->storeBatchPerwalian($kodeDosen, $kodeDosenPerwakilan, $nims);
    }

    public function perwalian(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'], // encrypted nim
            'code_dosen' => ['nullable', 'string'], // encrypted kode_dosen
            'program_studi' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $nim = null;
        $kode_dosen = null;
        $programStudi = $validated['program_studi'] ?? null;

        if (isset($validated['code'])) {
            try {
                $nim = Crypt::decryptString($validated['code']);
            } catch (DecryptException) {
                return ApiResponse::validation(['code' => 'Format code tidak valid']);
            }
        }

        if (isset($validated['code_dosen'])) {
            $kodeDosen = $this->decryptIntegerCode($validated['code_dosen'], 'code_dosen');
            if ($kodeDosen instanceof JsonResponse) {
                return $kodeDosen;
            }

            $kode_dosen = $kodeDosen;
        }

        $perPage = $validated['per_page'] ?? 20;

        return $this->perwalianService->getAllPerwalian($nim, $kode_dosen, $programStudi, $perPage);
    }

    public function perwalianByDosen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'nama' => ['nullable', 'string'],
        ]);

        if (! ($validated['code'] ?? null) && ! ($validated['nama'] ?? null)) {
            return ApiResponse::validation(['search' => 'Harus ada parameter']);
        }

        if (! empty($validated['nama'])) {
            return $this->perwalianService->getPerwalianByDosenName($validated['nama']);
        }

        $kodeDosen = $this->decryptIntegerCode($validated['code'], 'code');
        if ($kodeDosen instanceof JsonResponse) {
            return $kodeDosen;
        }

        return $this->perwalianService->getPerwalianByDosen($kodeDosen);
    }

    public function perwalianByMahasiswa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'nama' => ['nullable', 'string'],
        ]);

        if (! ($validated['code'] ?? null) && ! ($validated['nama'] ?? null)) {
            return ApiResponse::validation(['search' => 'Harus ada parameter']);
        }

        if (! empty($validated['nama'])) {
            return $this->perwalianService->getPerwalianByMahasiswaName($validated['nama']);
        }

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->perwalianService->getPerwalianByMahasiswa($nim);
    }

    public function updatePerwalian(Request $request, ?string $code = null): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'code_perwalian' => ['nullable', 'string'],
            'nim' => ['nullable', 'string'],
            'code_nim' => ['nullable', 'string'],
            'code_dosen' => ['nullable', 'string'],
            'kode_dosen' => ['nullable', 'string'],
            'code_dosen_perwakilan' => ['nullable', 'string'],
            'kode_dosen_perwakilan' => ['nullable', 'string'],
        ]);

        $targetCode = $code ?? $validated['code_perwalian'] ?? $validated['code'] ?? null;
        if (! $targetCode) {
            return ApiResponse::validation(['code' => 'Parameter code perwalian wajib diisi']);
        }

        try {
            $kode_perwalian = Crypt::decryptString($targetCode);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        $payload = [];

        // Support both 'code_nim' / 'nim', or 'code' if $code was passed via path param
        $rawNim = $validated['nim'] ?? $validated['code_nim'] ?? null;
        if ($rawNim === null && $code !== null && isset($validated['code'])) {
            $rawNim = $validated['code'];
        }
        if ($rawNim !== null) {
            try {
                $payload['nim'] = Crypt::decryptString($rawNim);
            } catch (DecryptException) {
                return ApiResponse::validation(['nim' => 'Format code nim tidak valid']);
            }
        }

        // Support both 'code_dosen' and 'kode_dosen'
        $rawDosen = $validated['code_dosen'] ?? $validated['kode_dosen'] ?? null;
        if ($rawDosen !== null) {
            $decoded = $this->decryptIntegerCode($rawDosen, 'kode_dosen');
            if ($decoded instanceof JsonResponse) {
                return $decoded;
            }
            $payload['kode_dosen'] = $decoded;
        }

        // Support both 'code_dosen_perwakilan' and 'kode_dosen_perwakilan'
        $hasPerwakilan = array_key_exists('code_dosen_perwakilan', $request->all())
            || array_key_exists('kode_dosen_perwakilan', $request->all());

        if ($hasPerwakilan) {
            $rawPerwakilan = $validated['code_dosen_perwakilan'] ?? $validated['kode_dosen_perwakilan'] ?? null;
            if ($rawPerwakilan !== null) {
                $decoded = $this->decryptIntegerCode($rawPerwakilan, 'kode_dosen_perwakilan');
                if ($decoded instanceof JsonResponse) {
                    return $decoded;
                }
                $payload['kode_dosen_perwakilan'] = $decoded;
            } else {
                $payload['kode_dosen_perwakilan'] = null;
            }
        }

        return $this->perwalianService->updatePerwalian((int) $kode_perwalian, $payload);
    }

    public function destroyPerwalian(Request $request, ?string $code = null): JsonResponse
    {
        $targetCode = $code ?? $request->input('code_perwalian') ?? $request->input('code');
        if (! $targetCode) {
            return ApiResponse::validation(['code' => 'Parameter code perwalian wajib diisi']);
        }

        try {
            $kode_perwalian = Crypt::decryptString($targetCode);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->perwalianService->deletePerwalian((int) $kode_perwalian);
    }

    private function decryptIntegerCode(string $code, string $field): int|JsonResponse
    {
        try {
            $decoded = Crypt::decryptString($code);
        } catch (DecryptException) {
            return ApiResponse::validation([$field => "Format {$field} tidak valid"]);
        }

        $value = filter_var($decoded, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($value === false) {
            return ApiResponse::validation([$field => "Format {$field} tidak valid"]);
        }

        return $value;
    }
}

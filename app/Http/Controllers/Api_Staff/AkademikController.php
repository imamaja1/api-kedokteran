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
            'code_dosen' => ['required', 'integer'],
            'code_dosen_perwakilan' => ['nullable', 'integer'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->perwalianService->storePerwalian([
            'nim' => $nim,
            'kode_dosen' => $validated['code_dosen'],
            'kode_dosen_perwakilan' => $validated['code_dosen_perwakilan'] ?? null,
        ]);
    }

    public function perwalian(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'], // encrypted nim
            'code_dosen' => ['nullable', 'string'], // encrypted kode_dosen
            'per_page' => ['nullable', 'integer'],
        ]);

        $nim = null;
        $kode_dosen = null;

        if (isset($validated['code'])) {
            try {
                $nim = Crypt::decryptString($validated['code']);
            } catch (DecryptException) {
                return ApiResponse::validation(['code' => 'Format code tidak valid']);
            }
        }

        if (isset($validated['code_dosen'])) {
            try {
                $kode_dosen = (int) Crypt::decryptString($validated['code_dosen']);
            } catch (DecryptException) {
                return ApiResponse::validation(['code_dosen' => 'Format code_dosen tidak valid']);
            }
        }

        $perPage = $validated['per_page'] ?? 20;

        return $this->perwalianService->getAllPerwalian($nim, $kode_dosen, $perPage);
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

        if ($validated['nama']) {
            return $this->perwalianService->getPerwalianByDosenName($validated['nama']);
        }

        try {
            $kode_dosen = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->perwalianService->getPerwalianByDosen((int) $kode_dosen);
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

        if ($validated['nama']) {
            return $this->perwalianService->getPerwalianByMahasiswaName($validated['nama']);
        }

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->perwalianService->getPerwalianByMahasiswa($nim);
    }

    public function updatePerwalian(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'kode_dosen' => ['nullable', 'integer'],
            'kode_dosen_perwakilan' => ['nullable', 'integer'],
            'nim' => ['nullable', 'string'],
        ]);

        try {
            $kode_perwalian = Crypt::decryptString($code);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        if (isset($validated['nim'])) {
            try {
                $validated['nim'] = Crypt::decryptString($validated['nim']);
            } catch (DecryptException) {
                return ApiResponse::validation(['nim' => 'Format nim tidak valid']);
            }
        }

        return $this->perwalianService->updatePerwalian((int) $kode_perwalian, array_filter($validated, fn ($value) => $value !== null));
    }
}

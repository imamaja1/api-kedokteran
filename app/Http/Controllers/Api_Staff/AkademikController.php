<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceKHS;
use App\Service\ServiceKRS;
use App\Service\ServiceKurikulum;
use App\Service\ServicePetikanNilai;
use App\Service\ServiceProgramStudi;
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

        $kode_nama_kurikulum = Crypt::decryptString($validated['code_nama_kurikulum']);

        return $this->kurikulumService->kurikulum_by_nama_kurikulum($kode_nama_kurikulum);
    }

    public function krs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return $this->krsService->getAllKRS($validated['nim']);
    }

    public function krs_detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        $kode_krs = Crypt::decryptString($validated['code_krs']);

        return $this->krsService->getKRSDetail($kode_krs);
    }

    public function khs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return $this->khsService->getAllKHS($validated['nim']);
    }

    public function khs_detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_krs' => ['required', 'string'],
        ]);

        $kode_krs = Crypt::decryptString($validated['code_krs']);

        return $this->khsService->getKHSDetail($kode_krs);
    }

    public function petikan_nilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
        ]);

        return $this->petikanNilaiService->getTranskrip($validated['nim']);
    }
}

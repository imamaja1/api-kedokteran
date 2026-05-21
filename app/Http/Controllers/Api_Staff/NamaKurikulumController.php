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

        $id = Crypt::decryptString($request->query('code'));

        return $this->service->getOneNamaKurikulum($id);
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

        $id = Crypt::decryptString($validasi['code']);

        return $this->service->updateNamaKurikulum($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $id = Crypt::decryptString($code);

        return $this->service->deleteNamaKurikulum($id);
    }
}

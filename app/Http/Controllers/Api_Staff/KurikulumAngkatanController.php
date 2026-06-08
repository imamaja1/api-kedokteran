<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceKurikulumAngkatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class KurikulumAngkatanController extends Controller
{
    public function __construct(
        private readonly ServiceKurikulumAngkatan $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'angkatan' => ['nullable', 'string', 'max:4'],
            'kode_nama_kurikulum' => ['nullable', 'string'],
            'ekstensi' => ['nullable', 'string', 'max:50'],
            'paket' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->service->getAllKurikulumAngkatan($validasi);
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return $this->service->getOneKurikulumAngkatan($id);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'angkatan' => ['required', 'string', 'max:4'],
            'kode_nama_kurikulum' => ['required', 'string', 'exists:nama_kurikulum,kode_nama_kurikulum'],
            'ekstensi' => ['nullable', 'string', 'max:50'],
            'paket' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->service->storeKurikulumAngkatan($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'angkatan' => ['required', 'string', 'max:4'],
            'kode_nama_kurikulum' => ['required', 'string', 'exists:nama_kurikulum,kode_nama_kurikulum'],
            'ekstensi' => ['nullable', 'string', 'max:50'],
            'paket' => ['nullable', 'string', 'max:50'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return $this->service->updateKurikulumAngkatan($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $id = Crypt::decryptString($code);

        return $this->service->deleteKurikulumAngkatan($id);
    }
}

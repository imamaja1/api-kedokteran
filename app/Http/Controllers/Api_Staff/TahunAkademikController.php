<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceTahunAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TahunAkademikController extends Controller
{
    public function __construct(
        private readonly ServiceTahunAkademik $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['nullable', 'in:1,2'],
            'status' => ['nullable', 'in:A,N'],
        ]);

        return $this->service->getAllTahunAkademik($validasi);
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return $this->service->getOneTahunAkademik($id);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['required', 'in:A,N'],
            'status_kpat' => ['nullable', 'in:A,N'],
        ]);

        return $this->service->storeTahunAkademik($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'tahun_akademik' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['required', 'in:A,N'],
            'status_kpat' => ['nullable', 'in:A,N'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return $this->service->updateTahunAkademik($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $id = Crypt::decryptString($code);

        return $this->service->deleteTahunAkademik($id);
    }
}

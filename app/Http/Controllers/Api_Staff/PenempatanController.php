<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServicePenempatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'kode_krs_detail' => ['required', 'integer'],
            'kelas_id' => ['required', 'integer'],
        ]);

        return $this->service->store($validasi);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->service->destroy($id);
    }
}

<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServicePembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PembayaranController extends Controller
{
    public function __construct(
        private readonly ServicePembayaran $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nim' => ['nullable', 'string', 'max:11'],
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:lunas,belum'],
        ]);

        return $this->service->index($validasi);
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        return $this->service->show($request->query('code'));
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nim' => ['required', 'string', 'max:11'],
            'kode_tahun_akademik' => ['required', 'integer'],
            'status' => ['nullable', 'in:lunas,belum'],
            'tanggal_bayar' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->service->store($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'status' => ['nullable', 'in:lunas,belum'],
            'tanggal_bayar' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->service->update($validasi);
    }

    public function getSksLimit(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nim' => ['required', 'string', 'max:11'],
            'kode_tahun_akademik' => ['required', 'integer'],
        ]);

        return $this->service->getSksLimit($validasi);
    }

    public function setSksOverride(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'nim' => ['required', 'string', 'max:11'],
            'kode_tahun_akademik' => ['required', 'integer'],
            'sks_override' => ['required', 'integer', 'min:25', 'max:32'],
            'sks_override_reason' => ['required', 'string', 'max:255'],
        ]);

        $staffId = Auth::guard('staff_web')->user()->id;

        return $this->service->setSksOverride($validasi, $staffId);
    }
}

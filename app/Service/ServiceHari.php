<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Hari;
use Illuminate\Http\JsonResponse;

class ServiceHari
{
    public function formatHari(Hari $hari): array
    {
        return [
            'id' => $hari->id,
            'code' => $hari->toCode(),
            'code_hari' => $hari->toCode(),
            'nama_hari' => $hari->nama_hari,
            'urutan' => $hari->urutan,
            'status_aktif' => (bool) $hari->status_aktif,
            'created_at' => $hari->created_at,
            'updated_at' => $hari->updated_at,
        ];
    }

    public function index(): JsonResponse
    {
        $hari = Hari::orderBy('urutan')->get()->map(fn ($h) => $this->formatHari($h));
        return ApiResponse::success($hari, 'Daftar hari berhasil diambil.');
    }

    public function show(int $id): JsonResponse
    {
        $hari = Hari::find($id);
        if (! $hari) {
            return ApiResponse::notFound('Hari tidak ditemukan.');
        }

        return ApiResponse::success($this->formatHari($hari), 'Detail hari berhasil diambil.');
    }

    public function update(int $id, array $data): JsonResponse
    {
        $hari = Hari::find($id);
        if (! $hari) {
            return ApiResponse::notFound('Hari tidak ditemukan.');
        }

        if (isset($data['status_aktif'])) {
            $hari->status_aktif = (bool) $data['status_aktif'];
        }

        $hari->save();

        return ApiResponse::success($this->formatHari($hari), 'Data hari berhasil diperbarui.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $hari = Hari::find($id);
        if (! $hari) {
            return ApiResponse::notFound('Hari tidak ditemukan.');
        }

        $hari->status_aktif = ! $hari->status_aktif;
        $hari->save();

        $statusStr = $hari->status_aktif ? 'diaktifkan' : 'dinonaktifkan';
        return ApiResponse::success($this->formatHari($hari), "Hari {$hari->nama_hari} berhasil {$statusStr}.");
    }
}

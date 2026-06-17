<?php

namespace App\Service;

use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;

class ServiceTahunAkademik
{
    public function getAllTahunAkademik(array $filters = []): JsonResponse
    {
        $query = TahunAkademik::orderByDesc('kode_tahun_akademik');

        if (! empty($filters['tahun_akademik'])) {
            $query->where('tahun_akademik', $filters['tahun_akademik']);
        }

        if (! empty($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => $item->toCode(),
                'tahun_akademik' => $item->tahun_akademik,
                'semester' => $item->semester,
                'tanggal_mulai' => $item->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $item->tanggal_berakhir?->format('Y-m-d'),
                'status' => $item->status,
                'status_kpat' => $item->status_kpat,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'API Tahun Akademik',
            'jumlah' => $paginator->total(),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function getOneTahunAkademik(string $id): JsonResponse
    {
        $data = TahunAkademik::find($id);

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Tahun akademik tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Tahun Akademik',
            'data' => [
                'code' => $data->toCode(),
                'tahun_akademik' => $data->tahun_akademik,
                'semester' => $data->semester,
                'tanggal_mulai' => $data->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $data->tanggal_berakhir?->format('Y-m-d'),
                'status' => $data->status,
                'status_kpat' => $data->status_kpat,
            ],
        ]);
    }

    public function storeTahunAkademik(array $object): JsonResponse
    {
        if (isset($object['status']) && $object['status'] === 'A') {
            TahunAkademik::where('semester', $object['semester'])
                ->where('status', 'A')
                ->update(['status' => 'N']);
        }

        try {
            $tahunAkademik = TahunAkademik::create($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Tahun Akademik',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tahun Akademik berhasil dibuat',
            'data' => [
                'code' => $tahunAkademik->toCode(),
                'tahun_akademik' => $tahunAkademik->tahun_akademik,
                'semester' => $tahunAkademik->semester,
                'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
                'status' => $tahunAkademik->status,
                'status_kpat' => $tahunAkademik->status_kpat,
            ],
        ], 201);
    }

    public function updateTahunAkademik(string $id, array $object): JsonResponse
    {
        $tahunAkademik = TahunAkademik::find($id);

        if (! $tahunAkademik) {
            return response()->json([
                'status' => false,
                'message' => 'Tahun akademik tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if (isset($object['status']) && $object['status'] === 'A') {
            TahunAkademik::where('semester', $object['semester'])
                ->where('status', 'A')
                ->where('kode_tahun_akademik', '!=', $tahunAkademik->kode_tahun_akademik)
                ->update(['status' => 'N']);
        }

        try {
            $tahunAkademik->update($object);
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Tahun Akademik',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tahun Akademik berhasil diperbarui',
            'data' => [
                'code' => $tahunAkademik->toCode(),
                'tahun_akademik' => $tahunAkademik->tahun_akademik,
                'semester' => $tahunAkademik->semester,
                'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
                'status' => $tahunAkademik->status,
                'status_kpat' => $tahunAkademik->status_kpat,
            ],
        ]);
    }

    public function getActiveTA(): JsonResponse
    {
        $data = TahunAkademik::active()->first();

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada tahun akademik aktif',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tahun akademik aktif',
            'data' => [
                'kode_tahun_akademik' => $data->kode_tahun_akademik,
                'tahun_akademik' => $data->tahun_akademik,
                'semester' => $data->semester,
                'tanggal_mulai' => $data->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $data->tanggal_berakhir?->format('Y-m-d'),
                'status' => $data->status,
                'status_kpat' => $data->status_kpat,
            ],
        ]);
    }

    public function deleteTahunAkademik(string $id): JsonResponse
    {
        $tahunAkademik = TahunAkademik::find($id);

        if (! $tahunAkademik) {
            return response()->json([
                'status' => false,
                'message' => 'Tahun akademik tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $tahunAkademik->delete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Tahun Akademik',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tahun Akademik berhasil dihapus',
            'data' => [
                'code' => $tahunAkademik->toCode(),
                'tahun_akademik' => $tahunAkademik->tahun_akademik,
                'semester' => $tahunAkademik->semester,
                'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
                'status' => $tahunAkademik->status,
                'status_kpat' => $tahunAkademik->status_kpat,
            ],
        ]);
    }
}

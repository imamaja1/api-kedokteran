<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Ruangan;
use Illuminate\Http\JsonResponse;

class ServiceRuangan
{
    public function formatRuangan(Ruangan $ruangan): array
    {
        return [
            'id' => $ruangan->id,
            'code' => $ruangan->toCode(),
            'code_ruangan' => $ruangan->toCode(),
            'kode_ruangan' => $ruangan->kode_ruangan,
            'nama_ruangan' => $ruangan->nama_ruangan,
            'kapasitas' => $ruangan->kapasitas,
            'jenis' => $ruangan->jenis,
            'lokasi' => $ruangan->lokasi,
            'status_aktif' => (bool) $ruangan->status_aktif,
            'created_at' => $ruangan->created_at,
            'updated_at' => $ruangan->updated_at,
        ];
    }

    public function index(array $filters = []): JsonResponse
    {
        $query = Ruangan::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('kode_ruangan', 'like', "%{$search}%")
                    ->orWhere('nama_ruangan', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status_aktif']) && $filters['status_aktif'] !== '') {
            $query->where('status_aktif', filter_var($filters['status_aktif'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        $query->orderBy('kode_ruangan');

        if (! empty($filters['per_page'])) {
            $paginator = $query->paginate((int) $filters['per_page']);
            $items = collect($paginator->items())->map(fn ($r) => $this->formatRuangan($r));

            return response()->json([
                'status' => true,
                'message' => 'Daftar ruangan berhasil diambil.',
                'jumlah' => $paginator->total(),
                'data' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        $ruangan = $query->get()->map(fn ($r) => $this->formatRuangan($r));
        return ApiResponse::success($ruangan, 'Daftar ruangan berhasil diambil.');
    }

    public function show(int $id): JsonResponse
    {
        $ruangan = Ruangan::find($id);
        if (! $ruangan) {
            return ApiResponse::notFound('Ruangan tidak ditemukan.');
        }

        return ApiResponse::success($this->formatRuangan($ruangan), 'Detail ruangan berhasil diambil.');
    }

    public function store(array $data): JsonResponse
    {
        $ruangan = Ruangan::create([
            'kode_ruangan' => $data['kode_ruangan'],
            'nama_ruangan' => $data['nama_ruangan'],
            'kapasitas' => (int) ($data['kapasitas'] ?? 40),
            'jenis' => $data['jenis'] ?? 'kuliah',
            'lokasi' => $data['lokasi'] ?? null,
            'status_aktif' => isset($data['status_aktif']) ? (bool) $data['status_aktif'] : true,
        ]);

        return ApiResponse::success($this->formatRuangan($ruangan), 'Ruangan berhasil dibuat.', 201);
    }

    public function update(int $id, array $data): JsonResponse
    {
        $ruangan = Ruangan::find($id);
        if (! $ruangan) {
            return ApiResponse::notFound('Ruangan tidak ditemukan.');
        }

        $ruangan->update(array_filter([
            'kode_ruangan' => $data['kode_ruangan'] ?? $ruangan->kode_ruangan,
            'nama_ruangan' => $data['nama_ruangan'] ?? $ruangan->nama_ruangan,
            'kapasitas' => isset($data['kapasitas']) ? (int) $data['kapasitas'] : $ruangan->kapasitas,
            'jenis' => $data['jenis'] ?? $ruangan->jenis,
            'lokasi' => array_key_exists('lokasi', $data) ? $data['lokasi'] : $ruangan->lokasi,
            'status_aktif' => isset($data['status_aktif']) ? (bool) $data['status_aktif'] : $ruangan->status_aktif,
        ], fn ($val) => ! is_null($val)));

        return ApiResponse::success($this->formatRuangan($ruangan->fresh()), 'Ruangan berhasil diperbarui.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $ruangan = Ruangan::find($id);
        if (! $ruangan) {
            return ApiResponse::notFound('Ruangan tidak ditemukan.');
        }

        $ruangan->status_aktif = ! $ruangan->status_aktif;
        $ruangan->save();

        $statusStr = $ruangan->status_aktif ? 'diaktifkan' : 'dinonaktifkan';
        return ApiResponse::success($this->formatRuangan($ruangan), "Ruangan berhasil {$statusStr}.");
    }

    public function destroy(int $id): JsonResponse
    {
        $ruangan = Ruangan::find($id);
        if (! $ruangan) {
            return ApiResponse::notFound('Ruangan tidak ditemukan.');
        }

        if ($ruangan->jadwalKelas()->exists()) {
            return ApiResponse::error(
                'Ruangan tidak dapat dihapus karena sudah memiliki jadwal kelas terkait. Silakan nonaktifkan ruangan atau hapus jadwal terlebih dahulu.',
                422
            );
        }

        $ruangan->delete();
        return ApiResponse::success(null, 'Ruangan berhasil dihapus.');
    }
}

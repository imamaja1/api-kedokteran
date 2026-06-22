<?php

namespace App\Service;

use App\Models\Grade;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceGradeCRUD
{
    public function index(array $filters = []): LengthAwarePaginator
    {
        $query = Grade::query();

        if ($filters['kode_program_studi'] ?? null) {
            $query->where('kode_program_studi', $filters['kode_program_studi']);
        }

        if ($filters['huruf'] ?? null) {
            $query->where('huruf', $filters['huruf']);
        }

        return $query->orderBy('nilai_min', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function store(array $data): Grade
    {
        return Grade::create($data);
    }

    public function show(int $id): ?Grade
    {
        return Grade::find($id);
    }

    public function update(int $id, array $data): ?Grade
    {
        $grade = Grade::find($id);
        if (! $grade) {
            return null;
        }

        $grade->update($data);

        return $grade;
    }

    public function destroy(int $id): bool
    {
        $grade = Grade::find($id);
        if (! $grade) {
            return false;
        }

        $grade->delete();

        return true;
    }

    /**
     * Cek apakah range grade overlap dengan grade lain di prodi yang sama
     */
    public function isOverlap(?int $kodeProdi, float $nilaiMin, float $nilaiMax, ?int $excludeId = null): bool
    {
        $query = Grade::where('kode_program_studi', $kodeProdi)
            ->where('nilai_min', '<=', $nilaiMax)
            ->where('nilai_max', '>=', $nilaiMin);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

<?php

namespace App\Service;

use App\Models\SksRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceSksRule
{
    public function index(array $filters = []): LengthAwarePaginator
    {
        $query = SksRule::with('programStudi:kode_program_studi,nama_program_studi');

        if ($filters['kode_program_studi'] ?? null) {
            $query->where('kode_program_studi', $filters['kode_program_studi']);
        }

        return $query->orderBy('kode_program_studi')->orderBy('ip_min', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function store(array $data): SksRule
    {
        return SksRule::create($data);
    }

    public function show(int $id): ?SksRule
    {
        return SksRule::with('programStudi:kode_program_studi,nama_program_studi')->find($id);
    }

    public function update(int $id, array $data): ?SksRule
    {
        $rule = SksRule::find($id);
        if (! $rule) {
            return null;
        }

        $rule->update($data);

        return $rule;
    }

    public function destroy(int $id): bool
    {
        $rule = SksRule::find($id);
        if (! $rule) {
            return false;
        }

        $rule->delete();

        return true;
    }

    /**
     * Cek apakah IP range overlap dengan rule lain di prodi yang sama
     */
    public function isOverlap(?int $kodeProdi, float $ipMin, float $ipMax, ?int $excludeId = null): bool
    {
        $query = SksRule::where('kode_program_studi', $kodeProdi)
            ->where('ip_min', '<=', $ipMax)
            ->where('ip_max', '>=', $ipMin);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Dapatkan SKS limit berdasarkan IPK
     */
    public function getSksLimit(int $kodeProdi, float $ip): int
    {
        $rule = SksRule::cariRule($kodeProdi, $ip);

        return $rule?->sks_yang_dapat_diambil ?? 24;
    }
}

<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Pembayaran;
use App\Models\PerwalianKrsValidasi;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceKRS
{
    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build KRS response data (code, sks, detail array).
     */
    private function buildKrsData(Krs $krs, int $limit): array
    {
        $terpakai = 0;
        if ($krs->krsDetail->isNotEmpty()) {
            $terpakai = $krs->krsDetail->sum(function ($detail) {
                return ($detail->matakuliah->sks_teori ?? 0) + ($detail->matakuliah->sks_praktik ?? 0);
            });
        }

        return [
            'code' => $krs->toCode(),
            'semester' => $krs->semester,
            'tahun_akademik' => $krs->tahunAkademik->tahun_akademik,
            'sks_limit' => $limit,
            'sks_terpakai' => $terpakai,
            'sks_tersisa' => $limit - $terpakai,
            'detail' => $krs->krsDetail->map(fn ($detail, $idx) => [
                'id' => $idx + 1,
                'code' => $detail->toCode(),
                'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                'sks_teori' => $detail->matakuliah->sks_teori,
                'sks_praktik' => $detail->matakuliah->sks_praktik,
                'block' => (bool) $detail->matakuliah->block,
            ])->values()->toArray(),
        ];
    }

    /**
     * Get filtered kurikulum for KRS: odd/even semesters + skripsi/kkn.
     */
    private function getKurikulumForKrs(string $nim, string $kode_prodi, int $currentSemester): array
    {
        $angkatan = substr($nim, 0, 2);

        $kurikulumAngkatan = KurikulumAngkatan::select('kurikulum_angkatan.kode_nama_kurikulum')
            ->whereRaw('substr(kurikulum_angkatan.angkatan, 3, 2) = ?', [$angkatan])
            ->join('nama_kurikulum', 'kurikulum_angkatan.kode_nama_kurikulum', '=', 'nama_kurikulum.kode_nama_kurikulum')
            ->where('nama_kurikulum.kode_program_studi', $kode_prodi)
            ->first();

        if (! $kurikulumAngkatan) {
            return [];
        }

        $isGanjil = $currentSemester % 2 === 1;

        $allKurikulum = Kurikulum::where('kode_nama_kurikulum', $kurikulumAngkatan->kode_nama_kurikulum)
            ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block')
            ->select('semester', 'id_matakuliah', 'kode_nama_kurikulum')
            ->orderBy('semester')
            ->get()
            ->groupBy('semester');

        $filtered = $allKurikulum->filter(function ($items, $sem) use ($isGanjil, $currentSemester) {
            // Skripsi/KKN: non-numeric semester values
            if (! is_numeric($sem)) {
                return true;
            }

            $semInt = (int) $sem;

            // Only semesters <= current semester
            if ($semInt > $currentSemester) {
                return false;
            }

            // Match odd/even pattern
            return $isGanjil ? ($semInt % 2 === 1) : ($semInt % 2 === 0);
        });

        return $filtered->map(function ($items, $sem) {
            $totalSks = $items->sum(fn ($item) => ($item->matakuliah->sks_teori ?? 0) + ($item->matakuliah->sks_praktik ?? 0));

            return [
                'semester' => $sem,
                'total_sks' => $totalSks,
                'matakuliah' => $items->map(fn ($item) => [
                    'kode_matakuliah' => $item->matakuliah->kode_matakuliah,
                    'nama_matakuliah' => $item->matakuliah->nama_matakuliah,
                    'sks_teori' => $item->matakuliah->sks_teori,
                    'sks_praktik' => $item->matakuliah->sks_praktik,
                    'block' => (bool) $item->matakuliah->block,
                ])->values(),
            ];
        })->values()->toArray();
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    public function getKRSMhs(string $nim, ?int $semester = null): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode')
            ->with('programStudi:kode_program_studi,nama_program_studi')
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $mhsInfo = [
            'nim' => $mahasiswa->nim,
            'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
            'nama_program_studi' => $mahasiswa->programStudi?->nama_program_studi,
        ];

        $eagerLoad = [
            'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
            'krsDetail' => function ($q) {
                $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah', 'status')
                    ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
            },
        ];

        // ── With semester param ─────────────────────────────────────────────
        if ($semester !== null) {
            $krs = Krs::where('nim', $nim)
                ->where('semester', $semester)
                ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')
                ->with($eagerLoad)
                ->first();

            if (! $krs) {
                return ApiResponse::notFound('Tidak ada KRS untuk semester ini.');
            }

            return $this->buildKrsResponse($mhsInfo, $krs, $nim, $mahasiswa->program_studi_kode);
        }

        // ── Default: TA aktif ────────────────────────────────────────────────
        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA) {
            return ApiResponse::error('Tidak ada tahun akademik aktif.', 404);
        }

        $krs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')
            ->with($eagerLoad)
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS belum tersedia. Pastikan pembayaran sudah dilakukan.');
        }

        return $this->buildKrsResponse($mhsInfo, $krs, $nim, $mahasiswa->program_studi_kode);
    }

    /**
     * Build status-aware KRS response.
     * - Status 'A' (locked) → KRS + detail only
     * - Status 'N' (editable) → KRS + detail + filtered kurikulum
     */
    private function buildKrsResponse(array $mhsInfo, Krs $krs, string $nim, ?string $kode_prodi): JsonResponse
    {
        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $krs->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar?->sks_override ?? 24;

        // Check validation status
        $validasi = PerwalianKrsValidasi::where('nim', $nim)
            ->latest()
            ->first();

        $isLocked = $validasi && $validasi->status_krs === 'A';

        $data = [
            'mahasiswa' => $mhsInfo,
            'krs' => $this->buildKrsData($krs, $limit),
            'status_validasi' => $validasi?->status_krs ?? 'N',
        ];

        // Include kurikulum only when editable
        if (! $isLocked && $kode_prodi) {
            $data['kurikulum'] = $this->getKurikulumForKrs($nim, $kode_prodi, $krs->semester);
        }

        return ApiResponse::success($data, 'KRS Mahasiswa berhasil diambil.');
    }

    /**
     * Get KRS for edit form — returns KRS + kurikulum (must be editable).
     */
    public function getKrsForEdit(string $nim, int $semester): JsonResponse
    {
        $mahasiswa = Mahasiswa::where('nim', $nim)
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode')
            ->with('programStudi:kode_program_studi,nama_program_studi')
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $krs = Krs::where('nim', $nim)
            ->where('semester', $semester)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')
            ->with([
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah', 'status')
                        ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS untuk semester ini tidak ditemukan.');
        }

        // Check if locked
        $isLocked = PerwalianKrsValidasi::where('nim', $nim)
            ->where('status_krs', 'A')
            ->exists();

        if ($isLocked) {
            return ApiResponse::error('KRS sudah divalidasi, tidak dapat diedit.', 422);
        }

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $krs->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar?->sks_override ?? 24;

        $data = [
            'mahasiswa' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'nama_program_studi' => $mahasiswa->programStudi?->nama_program_studi,
            ],
            'krs' => $this->buildKrsData($krs, $limit),
            'kurikulum' => $this->getKurikulumForKrs($nim, $mahasiswa->program_studi_kode, $krs->semester),
        ];

        return ApiResponse::success($data, 'Data KRS untuk edit berhasil diambil.');
    }

    public function getAllKRS(string $nim)
    {
        $krsRecords = Krs::where('nim', $nim)
            ->select('kode_krs', 'nim', 'semester')
            ->orderBy('semester', 'desc')
            ->get();

        if ($krsRecords->isEmpty()) {
            return ApiResponse::notFound('Tidak ada data KRS untuk mahasiswa ini');
        }

        $data = $krsRecords->map(fn ($item, $idx) => [
            'id' => $idx + 1,
            'code_krs' => $item->toCode(),
            'semester' => $item->semester,
        ])->values()->toArray();

        return ApiResponse::success($data, 'KRS Mahasiswa berhasil diambil.');
    }

    public function getKRSDetail(string $kode_krs)
    {
        $krs = Krs::where('kode_krs', $kode_krs)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')
            ->with([
                'mahasiswa:nim,nama_mahasiswa,program_studi_kode',
                'mahasiswa.programStudi:kode_program_studi,nama_program_studi',
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('kode_krs_detail', 'kode_krs', 'id_matakuliah', 'status')
                        ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS tidak ditemukan');
        }

        $data = [
            'mahasiswa' => [
                'nim' => $krs->mahasiswa->nim,
                'nama_mahasiswa' => $krs->mahasiswa->nama_mahasiswa,
                'nama_program_studi' => $krs->mahasiswa->programStudi?->nama_program_studi,
                'semester' => $krs->tahunAkademik->semester,
            ],
            'krs' => $krs->krsDetail->map(fn ($detail, $idx) => [
                'id' => $idx + 1,
                'code' => $detail->toCode(),
                'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                'sks_teori' => $detail->matakuliah->sks_teori,
                'sks_praktik' => $detail->matakuliah->sks_praktik,
                'block' => (bool) $detail->matakuliah->block,
            ])->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Detail KRS berhasil diambil.');
    }

    public function addKrsDetail(string $nim, array $data): JsonResponse
    {
        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA) {
            return ApiResponse::error('Tidak ada tahun akademik aktif.', 404);
        }

        if (! $activeTA->isKrsOpen()) {
            return ApiResponse::error('Periode pengisian KRS belum dibuka atau sudah ditutup.', 422);
        }

        $validasi = PerwalianKrsValidasi::where('nim', $nim)
            ->where('status_krs', 'A')
            ->exists();

        if ($validasi) {
            return ApiResponse::error('KRS sudah divalidasi, tidak bisa diubah.', 422);
        }

        $krs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->first();

        if (! $krs) {
            return ApiResponse::error('KRS belum dibuat untuk tahun akademik aktif.', 422);
        }

        $matakuliahIds = $data['matakuliah'];
        $matakuliahList = Matakuliah::whereIn('id_matakuliah', $matakuliahIds)->get();

        if ($matakuliahList->count() !== count($matakuliahIds)) {
            $foundIds = $matakuliahList->pluck('id_matakuliah')->toArray();
            $notFound = array_diff($matakuliahIds, $foundIds);
            return ApiResponse::error('Matakuliah tidak ditemukan: ' . implode(', ', $notFound), 422);
        }

        $existingDetails = KrsDetail::where('kode_krs', $krs->kode_krs)
            ->whereIn('id_matakuliah', $matakuliahIds)
            ->pluck('id_matakuliah')
            ->toArray();

        if (! empty($existingDetails)) {
            $duplicates = $matakuliahList->whereIn('id_matakuliah', $existingDetails)->pluck('kode_matakuliah')->toArray();
            return ApiResponse::error('Matakuliah sudah ada di KRS: ' . implode(', ', $duplicates), 422);
        }

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $krs->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar?->sks_override ?? 24;

        $currentSks = KrsDetail::where('kode_krs', $krs->kode_krs)
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->sum(DB::raw('matakuliah.sks_teori + matakuliah.sks_praktik'));

        $newSks = $matakuliahList->sum(function ($mk) {
            return ($mk->sks_teori ?? 0) + ($mk->sks_praktik ?? 0);
        });

        $totalSks = $currentSks + $newSks;

        if ($totalSks > $limit) {
            return ApiResponse::error("KRS terlalu banyak. Total SKS: {$totalSks}, Maksimal: {$limit}", 422);
        }

        DB::beginTransaction();

        try {
            $insertData = [];
            foreach ($matakuliahList as $mk) {
                $insertData[] = [
                    'kode_krs' => $krs->kode_krs,
                    'id_matakuliah' => $mk->id_matakuliah,
                    'status' => 'B',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            KrsDetail::insert($insertData);
            DB::commit();

            $insertedMatakuliah = $matakuliahList->map(fn ($mk) => [
                'kode_matakuliah' => $mk->kode_matakuliah,
                'nama_matakuliah' => $mk->nama_matakuliah,
                'sks' => ($mk->sks_teori ?? 0) + ($mk->sks_praktik ?? 0),
            ])->values()->toArray();

            return ApiResponse::success([
                'krs' => [
                    'code' => $krs->toCode(),
                    'semester' => $krs->semester,
                    'tahun_akademik' => $activeTA->tahun_akademik,
                ],
                'matakuliah' => $insertedMatakuliah,
                'sks' => [
                    'limit' => $limit,
                    'terpakai' => $totalSks,
                    'tersisa' => $limit - $totalSks,
                ],
            ], count($insertedMatakuliah) . ' matakuliah berhasil ditambahkan ke KRS.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal menambahkan matakuliah ke KRS.');
        }
    }

    public function removeKrsDetail(string $nim, string $codeKrsDetail): JsonResponse
    {
        $detail = KrsDetail::findByCode($codeKrsDetail);

        if (! $detail || $detail->krs?->nim !== $nim) {
            return ApiResponse::notFound('Detail KRS tidak ditemukan atau bukan milik Anda.');
        }

        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA || ! $activeTA->isKrsOpen()) {
            return ApiResponse::error('Periode pengisian KRS belum dibuka atau sudah ditutup.', 422);
        }

        $validasi = PerwalianKrsValidasi::where('nim', $nim)
            ->where('status_krs', 'A')
            ->exists();

        if ($validasi) {
            return ApiResponse::error('KRS sudah divalidasi, tidak bisa diubah.', 422);
        }

        $khsDetail = \App\Models\KhsDetail::where('kode_krs_detail', $detail->kode_krs_detail)->first();
        if ($khsDetail) {
            return ApiResponse::error('Matakuliah sudah dinilai dan tidak dapat dihapus.', 422);
        }

        $detail->delete();

        return ApiResponse::success(null, 'Matakuliah berhasil dihapus dari KRS.');
    }

    /**
     * Atomic bulk replace: delete all krs_detail, insert new ones.
     */
    public function replaceKrsDetail(string $nim, int $semester, array $matakuliahIds): JsonResponse
    {
        $krs = Krs::where('nim', $nim)
            ->where('semester', $semester)
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS untuk semester ini tidak ditemukan.');
        }

        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA || ! $activeTA->isKrsOpen()) {
            return ApiResponse::error('Periode pengisian KRS belum dibuka atau sudah ditutup.', 422);
        }

        $isLocked = PerwalianKrsValidasi::where('nim', $nim)
            ->where('status_krs', 'A')
            ->exists();

        if ($isLocked) {
            return ApiResponse::error('KRS sudah divalidasi, tidak bisa diubah.', 422);
        }

        $matakuliahList = Matakuliah::whereIn('id_matakuliah', $matakuliahIds)->get();

        if ($matakuliahList->count() !== count($matakuliahIds)) {
            $foundIds = $matakuliahList->pluck('id_matakuliah')->toArray();
            $notFound = array_diff($matakuliahIds, $foundIds);
            return ApiResponse::error('Matakuliah tidak ditemukan: ' . implode(', ', $notFound), 422);
        }

        if (count($matakuliahIds) !== count(array_unique($matakuliahIds))) {
            return ApiResponse::error('Terdapat matakuliah duplikat dalam permintaan.', 422);
        }

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $krs->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar?->sks_override ?? 24;

        $totalSks = $matakuliahList->sum(function ($mk) {
            return ($mk->sks_teori ?? 0) + ($mk->sks_praktik ?? 0);
        });

        if ($totalSks > $limit) {
            return ApiResponse::error("KRS terlalu banyak. Total SKS: {$totalSks}, Maksimal: {$limit}", 422);
        }

        DB::beginTransaction();

        try {
            KrsDetail::where('kode_krs', $krs->kode_krs)->delete();

            if (! empty($matakuliahIds)) {
                $insertData = [];
                foreach ($matakuliahList as $mk) {
                    $insertData[] = [
                        'kode_krs' => $krs->kode_krs,
                        'id_matakuliah' => $mk->id_matakuliah,
                        'status' => 'B',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                KrsDetail::insert($insertData);
            }

            DB::commit();

            $detailList = $matakuliahList->map(fn ($mk) => [
                'kode_matakuliah' => $mk->kode_matakuliah,
                'nama_matakuliah' => $mk->nama_matakuliah,
                'sks' => ($mk->sks_teori ?? 0) + ($mk->sks_praktik ?? 0),
            ])->values()->toArray();

            return ApiResponse::success([
                'krs' => [
                    'code' => $krs->toCode(),
                    'semester' => $krs->semester,
                    'tahun_akademik' => $krs->tahunAkademik->tahun_akademik ?? $activeTA?->tahun_akademik,
                ],
                'matakuliah' => $detailList,
                'sks' => [
                    'limit' => $limit,
                    'terpakai' => $totalSks,
                    'tersisa' => $limit - $totalSks,
                ],
            ], 'KRS berhasil diperbarui.', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::serverError('Gagal memperbarui KRS.');
        }
    }

    public function getSksInfo(string $nim): JsonResponse
    {
        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA) {
            return ApiResponse::error('Tidak ada tahun akademik aktif.', 404);
        }

        $krs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->first();

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar->sks_override ?? 24;
        $terpakai = 0;

        if ($krs) {
            $terpakai = KrsDetail::where('kode_krs', $krs->kode_krs)
                ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
                ->sum(DB::raw('matakuliah.sks_teori + matakuliah.sks_praktik'));
        }

        return ApiResponse::success([
            'nim' => $nim,
            'kode_tahun_akademik' => $activeTA->kode_tahun_akademik,
            'tahun_akademik' => $activeTA->tahun_akademik,
            'semester' => $activeTA->semester,
            'sks_limit' => $limit,
            'sks_terpakai' => $terpakai,
            'sks_tersisa' => $limit - $terpakai,
            'sks_override' => $bayar && $bayar->sks_override ? true : false,
            'sks_override_reason' => $bayar?->sks_override_reason,
            'sks_override_by' => $bayar?->overrideBy?->name,
            'pembayaran_lunas' => $bayar ? true : false,
        ], 'Info SKS berhasil diambil.');
    }
}

<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Pembayaran;
use App\Models\Perwalian;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ServiceKRS
{
    /**
     * Get KRS untuk mahasiswa dengan eager loading (optimized)
     * Queries: 1 untuk mahasiswa + 1 untuk KRS dengan all relations = 2 total
     * Sebelumnya: 10-15 queries (N+1 problem)
     */
    public function getKRSMhs(string $nim, ?int $semester = null)
    {
        // Get mahasiswa data
        $mahasiswa = Mahasiswa::where('nim', $nim)
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode', 'alamat', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'telepon_orangtua')
            ->with('programStudi:kode_program_studi,nama_program_studi')
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        // Determine semester
        if ($semester === null) {
            $latestKrs = Krs::where('nim', $nim)->orderBy('semester', 'desc')->first();
            if (! $latestKrs) {
                return ApiResponse::success([
                    'mahasiswa' => [],
                    'krs' => [],
                ], 'Belum ada data KRS.');
            }
            $semester = $latestKrs->semester;
        }

        // Get KRS dengan eager loading untuk semua relations
        // FIX: Include all necessary keys dan columns untuk eager loading
        $krs = Krs::where('nim', $nim)
            ->where('semester', $semester)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')  // Include primary key explicitly
            ->with([
                'tahunAkademik:kode_tahun_akademik,tahun_akademik,semester',
                'krsDetail' => function ($q) {
                    $q->select('krs_detail.kode_krs_detail', 'krs_detail.kode_krs', 'krs_detail.id_matakuliah', 'status')
                        ->with('matakuliah:id_matakuliah,kode_matakuliah,nama_matakuliah,sks_teori,sks_praktik,block');
                },
            ])
            ->first();

        if (! $krs) {
            return ApiResponse::success([
                'mahasiswa' => [],
                'krs' => [],
            ], 'Belum ada data KRS untuk semester ini.');
        }
        // Format data
        $data = [
            'mahasiswa' => [
                [
                    'nim' => $mahasiswa->nim,
                    'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $mahasiswa->programStudi->nama_program_studi,
                    'alamat' => $mahasiswa->alamat,
                    'tempat_lahir' => $mahasiswa->tempat_lahir,
                    'tanggal_lahir' => $mahasiswa->tanggal_lahir,
                    'telepon' => $mahasiswa->telepon,
                    'telepon_orangtua' => $mahasiswa->telepon_orangtua,
                    'semester' => $semester,
                ],
            ],
            'krs' => $krs->krsDetail->map(function ($detail, $idx) {
                return [
                    'id' => $idx + 1,
                    'code' => $detail->toCode(),
                    'kode_matakuliah' => $detail->matakuliah->kode_matakuliah,
                    'nama_matakuliah' => $detail->matakuliah->nama_matakuliah,
                    'sks_teori' => $detail->matakuliah->sks_teori,
                    'sks_praktik' => $detail->matakuliah->sks_praktik,
                    'block' => (bool) $detail->matakuliah->block,
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'KRS Mahasiswa berhasil diambil.');
    }

    /**
     * Get all KRS records untuk mahasiswa
     */
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

    /**
     * Get KRS detail dengan eager loading
     */
    public function getKRSDetail(string $kode_krs)
    {
        $krs = Krs::where('kode_krs', $kode_krs)
            ->select('kode_krs', 'nim', 'semester', 'kode_tahun_akademik')  // Include primary key
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
                [
                    'nim' => $krs->mahasiswa->nim,
                    'nama_mahasiswa' => $krs->mahasiswa->nama_mahasiswa,
                    'nama_program_studi' => $krs->mahasiswa->programStudi->nama_program_studi,
                    'semester' => $krs->tahunAkademik->semester,
                ],
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

    public function createKRS(string $nim): JsonResponse
    {
        $activeTA = TahunAkademik::active()->first();
        if (! $activeTA) {
            return ApiResponse::error('Tidak ada tahun akademik aktif.', 404);
        }

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        if (! $bayar) {
            return ApiResponse::error('Pembayaran belum lunas. Silakan lunasi pembayaran terlebih dahulu.', 422);
        }

        $perwalian = Perwalian::where('nim', $nim)->first();
        if (! $perwalian) {
            return ApiResponse::error('Anda belum memiliki dosen wali. Silakan hubungi bagian akademik.', 422);
        }

        $existingKrs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
            ->first();

        if ($existingKrs) {
            return ApiResponse::error('KRS untuk semester ini sudah ada.', 422);
        }

        $krs = Krs::create([
            'nim' => $nim,
            'kode_tahun_akademik' => $activeTA->kode_tahun_akademik,
            'semester' => $activeTA->semester,
        ]);

        return ApiResponse::success([
            'kode_krs' => $krs->kode_krs,
            'nim' => $krs->nim,
            'kode_tahun_akademik' => $krs->kode_tahun_akademik,
            'semester' => $krs->semester,
        ], 'KRS berhasil dibuat.', 201);
    }

    public function addKrsDetail(string $nim, array $data): JsonResponse
    {
        $krs = Krs::where('kode_krs', $data['kode_krs'])
            ->where('nim', $nim)
            ->first();

        if (! $krs) {
            return ApiResponse::notFound('KRS tidak ditemukan atau bukan milik Anda.');
        }

        $matakuliah = Matakuliah::find($data['id_matakuliah']);
        if (! $matakuliah) {
            return ApiResponse::notFound('Matakuliah tidak ditemukan.');
        }

        $existingDetail = KrsDetail::where('kode_krs', $krs->kode_krs)
            ->where('id_matakuliah', $data['id_matakuliah'])
            ->first();

        if ($existingDetail) {
            return ApiResponse::error('Matakuliah sudah ada di KRS.', 422);
        }

        $bayar = Pembayaran::where('nim', $nim)
            ->where('kode_tahun_akademik', $krs->kode_tahun_akademik)
            ->where('status', 'lunas')
            ->first();

        $limit = $bayar->sks_override ?? 24;

        $currentSks = KrsDetail::where('kode_krs', $krs->kode_krs)
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->sum(DB::raw('matakuliah.sks_teori + matakuliah.sks_praktik'));

        $newSks = ($matakuliah->sks_teori ?? 0) + ($matakuliah->sks_praktik ?? 0);

        if (($currentSks + $newSks) > $limit) {
            $reason = $bayar && $bayar->sks_override
                ? " (Override SKS: {$limit}, alasan: {$bayar->sks_override_reason})"
                : '';
            return ApiResponse::error("Melebihi batas SKS ({$limit} SKS). Terpakai: {$currentSks}, Minta: {$newSks}{$reason}", 422);
        }

        $detail = KrsDetail::create([
            'kode_krs' => $krs->kode_krs,
            'id_matakuliah' => $data['id_matakuliah'],
            'status' => 'A',
        ]);

        return ApiResponse::success([
            'kode_krs_detail' => $detail->kode_krs_detail,
            'kode_krs' => $detail->kode_krs,
            'id_matakuliah' => $detail->id_matakuliah,
            'kode_matakuliah' => $matakuliah->kode_matakuliah,
            'nama_matakuliah' => $matakuliah->nama_matakuliah,
            'sks' => $newSks,
        ], 'Matakuliah berhasil ditambahkan ke KRS.', 201);
    }

    public function removeKrsDetail(string $nim, string $kodeKrsDetail): JsonResponse
    {
        $detail = KrsDetail::where('kode_krs_detail', $kodeKrsDetail)
            ->whereHas('krs', function ($q) use ($nim) {
                $q->where('nim', $nim);
            })
            ->first();

        if (! $detail) {
            return ApiResponse::notFound('Detail KRS tidak ditemukan atau bukan milik Anda.');
        }

        $khsDetail = \App\Models\KhsDetail::where('kode_krs_detail', $detail->kode_krs_detail)->first();
        if ($khsDetail) {
            return ApiResponse::error('Matakuliah sudah dinilai dan tidak dapat dihapus.', 422);
        }

        $detail->delete();

        return ApiResponse::success(null, 'Matakuliah berhasil dihapus dari KRS.');
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

<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Dosen;
use App\Models\KhsDetail;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Perwalian;
use App\Models\PerwalianKrsValidasi;
use App\Models\TahunAkademik;
use Illuminate\Http\JsonResponse;

class ServicePerwalian
{
    private function queryPerwalianByDosenSemester(int $kode_dosen, int $semester): \Illuminate\Support\Collection
    {
        return Perwalian::with(['mahasiswa', 'dosen', 'dosenPerwakilan'])
            ->where(function ($q) use ($kode_dosen) {
                $q->where('kode_dosen', $kode_dosen)
                    ->orWhere('kode_dosen_perwakilan', $kode_dosen);
            })
            ->whereHas('mahasiswa.krs', function ($q) use ($semester) {
                $q->where('semester', $semester);
            })
            ->get();
    }

    private function mapPerwalianCollection(\Illuminate\Support\Collection $records): array
    {
        return $records->map(function ($record, $idx) {
            return [
                'id' => $idx + 1,
                'code' => $record->toCode(),
                'nim' => $record->nim,
                'nama_mahasiswa' => optional($record->mahasiswa)->nama_mahasiswa,
                'code_dosen' => $record->dosen?->toCode(),
                'nama_dosen' => optional($record->dosen)->nama_dosen,
                'code_dosen_perwakilan' => $record->dosenPerwakilan?->toCode(),
                'nama_dosen_perwakilan' => optional($record->dosenPerwakilan)->nama_dosen,
            ];
        })->values()->toArray();
    }

    public function getPerwalianByMahasiswa(string $nim)
    {
        $mahasiswa = Mahasiswa::with('programStudi')
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode')
            ->where('nim', $nim)
            ->first();

        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $perwalianRecords = Perwalian::with(['dosen', 'dosenPerwakilan'])
            ->where('nim', $nim)
            ->get();

        $validasiRecords = PerwalianKrsValidasi::with('dosenValidator')
            ->where('nim', $nim)
            ->get();

        $data = [
            'mahasiswa' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'nama_program_studi' => optional($mahasiswa->programStudi)->nama_program_studi,
            ],
            'perwalian' => $perwalianRecords->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code' => $record->toCode(),
                    'nim' => $record->nim,
                    'nama_mahasiswa' => optional($record->mahasiswa)->nama_mahasiswa,
                    'code_dosen' => $record->dosen?->toCode(),
                    'nama_dosen' => optional($record->dosen)->nama_dosen,
                    'code_dosen_perwakilan' => $record->dosenPerwakilan?->toCode(),
                    'nama_dosen_perwakilan' => optional($record->dosenPerwakilan)->nama_dosen,
                ];
            })->values()->toArray(),
            'krs_validasi' => $validasiRecords->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code_dosen_validator' => $record->dosenValidator?->toCode(),
                    'nama_dosen_validator' => optional($record->dosenValidator)->nama_dosen,
                    'status_krs' => $record->status_krs,
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Perwalian mahasiswa retrieved successfully.');
    }

    public function getPerwalianByDosen(int $kode_dosen)
    {
        $dosen = Dosen::find($kode_dosen);

        if (! $dosen) {
            return ApiResponse::notFound('Dosen tidak ditemukan');
        }

        $records = Perwalian::with(['mahasiswa', 'dosen', 'dosenPerwakilan'])
            ->where(function ($q) use ($kode_dosen) {
                $q->where('kode_dosen', $kode_dosen)
                    ->orWhere('kode_dosen_perwakilan', $kode_dosen);
            })
            ->get();

        if ($records->isEmpty()) {
            return ApiResponse::notFound('Tidak ada data perwalian untuk dosen ini');
        }

        $data = [
            'dosen' => [
                'code' => $dosen->toCode(),
                'nama_dosen' => $dosen->nama_dosen,
            ],
            'perwalian' => $records->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code' => $record->toCode(),
                    'nim' => $record->nim,
                    'nama_mahasiswa' => optional($record->mahasiswa)->nama_mahasiswa,
                    'code_dosen' => $record->dosen?->toCode(),
                    'code_dosen_perwakilan' => $record->dosenPerwakilan?->toCode(),
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Perwalian dosen retrieved successfully.');
    }

    public function storePerwalian(array $payload)
    {
        $mahasiswa = Mahasiswa::find($payload['nim']);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $dosen = Dosen::find($payload['kode_dosen']);
        if (! $dosen) {
            return ApiResponse::notFound('Dosen validator tidak ditemukan');
        }

        $dosenPerwakilan = null;
        if (isset($payload['kode_dosen_perwakilan'])) {
            $dosenPerwakilan = Dosen::find($payload['kode_dosen_perwakilan']);
            if (! $dosenPerwakilan) {
                return ApiResponse::notFound('Dosen perwakilan tidak ditemukan');
            }
        }

        $perwalian = Perwalian::create($payload);

        return ApiResponse::success([
            'code' => $perwalian->toCode(),
            'nim' => $perwalian->nim,
            'code_dosen' => $dosen->toCode(),
            'code_dosen_perwakilan' => $dosenPerwakilan?->toCode(),
        ], 'Perwalian berhasil ditambahkan.');
    }

    public function updatePerwalian(int $kode_perwalian, array $payload)
    {
        $perwalian = Perwalian::find($kode_perwalian);
        if (! $perwalian) {
            return ApiResponse::notFound('Perwalian tidak ditemukan');
        }

        if (isset($payload['nim'])) {
            $mahasiswa = Mahasiswa::find($payload['nim']);
            if (! $mahasiswa) {
                return ApiResponse::notFound('Mahasiswa tidak ditemukan');
            }
        }

        $dosen = null;
        if (isset($payload['kode_dosen'])) {
            $dosen = Dosen::find($payload['kode_dosen']);
            if (! $dosen) {
                return ApiResponse::notFound('Dosen tidak ditemukan');
            }
        }

        $dosenPerwakilan = null;
        if (isset($payload['kode_dosen_perwakilan'])) {
            $dosenPerwakilan = Dosen::find($payload['kode_dosen_perwakilan']);
            if (! $dosenPerwakilan) {
                return ApiResponse::notFound('Dosen perwakilan tidak ditemukan');
            }
        }

        $perwalian->update($payload);

        return ApiResponse::success([
            'code' => $perwalian->toCode(),
            'nim' => $perwalian->nim,
            'code_dosen' => $dosen?->toCode() ?? $perwalian->dosen?->toCode(),
            'code_dosen_perwakilan' => $dosenPerwakilan?->toCode() ?? $perwalian->dosenPerwakilan?->toCode(),
        ], 'Perwalian berhasil diperbarui.');
    }

    public function getAllPerwalian(?string $nim = null, ?int $kode_dosen = null, int $perPage = 20)
    {
        $query = Perwalian::with(['mahasiswa', 'dosen', 'dosenPerwakilan'])
            ->when($nim, function ($q, $nim) {
                return $q->where('nim', $nim);
            })
            ->when($kode_dosen, function ($q, $kode) {
                return $q->where(function ($sub) use ($kode) {
                    $sub->where('kode_dosen', $kode)
                        ->orWhere('kode_dosen_perwakilan', $kode);
                });
            });

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => $item->toCode(),
                'nim' => $item->nim,
                'nama_mahasiswa' => optional($item->mahasiswa)->nama_mahasiswa,
                'nama_program_studi' => optional(optional($item->mahasiswa)->programStudi)->nama_program_studi,
                'code_dosen' => $item->dosen?->toCode(),
                'nama_dosen' => optional($item->dosen)->nama_dosen,
                'code_dosen_perwakilan' => $item->dosenPerwakilan?->toCode(),
                'nama_dosen_perwakilan' => optional($item->dosenPerwakilan)->nama_dosen,
            ];
        });

        return ApiResponse::paginated($paginator, 'Daftar perwalian');
    }

    public function getPerwalianByDosenName(string $nama_dosen)
    {
        $dosenList = Dosen::where('nama_dosen', 'LIKE', "%{$nama_dosen}%")
            ->select('kode_dosen', 'nama_dosen')
            ->get();

        if ($dosenList->isEmpty()) {
            return ApiResponse::notFound('Dosen tidak ditemukan');
        }

        $kodeDosen = $dosenList->pluck('kode_dosen')->toArray();

        $records = Perwalian::with(['mahasiswa', 'dosen', 'dosenPerwakilan'])
            ->where(function ($q) use ($kodeDosen) {
                $q->whereIn('kode_dosen', $kodeDosen)
                    ->orWhereIn('kode_dosen_perwakilan', $kodeDosen);
            })
            ->get();

        if ($records->isEmpty()) {
            return ApiResponse::notFound('Tidak ada data perwalian untuk dosen dengan nama ini');
        }

        $data = [
            'dosen_found' => $dosenList->map(function ($dosen) {
                return [
                    'code' => $dosen->toCode(),
                    'nama_dosen' => $dosen->nama_dosen,
                ];
            })->values()->toArray(),
            'perwalian' => $records->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code' => $record->toCode(),
                    'nim' => $record->nim,
                    'nama_mahasiswa' => optional($record->mahasiswa)->nama_mahasiswa,
                    'code_dosen' => $record->dosen?->toCode(),
                    'code_dosen_perwakilan' => $record->dosenPerwakilan?->toCode(),
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Perwalian dosen by nama retrieved successfully.');
    }

    public function getPerwalianByMahasiswaName(string $nama_mahasiswa)
    {
        $mahasiswaList = Mahasiswa::with('programStudi')
            ->where('nama_mahasiswa', 'LIKE', "%{$nama_mahasiswa}%")
            ->select('nim', 'nama_mahasiswa', 'program_studi_kode')
            ->get();

        if ($mahasiswaList->isEmpty()) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $nimList = $mahasiswaList->pluck('nim')->toArray();

        $perwalianRecords = Perwalian::with(['dosen', 'dosenPerwakilan'])
            ->whereIn('nim', $nimList)
            ->get();

        $validasiRecords = PerwalianKrsValidasi::with('dosenValidator')
            ->whereIn('nim', $nimList)
            ->get();

        $data = [
            'mahasiswa_found' => $mahasiswaList->map(function ($mhs) {
                return [
                    'nim' => $mhs->nim,
                    'nama_mahasiswa' => $mhs->nama_mahasiswa,
                    'nama_program_studi' => optional($mhs->programStudi)->nama_program_studi,
                ];
            })->values()->toArray(),
            'perwalian' => $perwalianRecords->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code' => $record->toCode(),
                    'nim' => $record->nim,
                    'nama_mahasiswa' => optional($record->mahasiswa)->nama_mahasiswa,
                    'code_dosen' => $record->dosen?->toCode(),
                    'nama_dosen' => optional($record->dosen)->nama_dosen,
                    'code_dosen_perwakilan' => $record->dosenPerwakilan?->toCode(),
                    'nama_dosen_perwakilan' => optional($record->dosenPerwakilan)->nama_dosen,
                ];
            })->values()->toArray(),
            'krs_validasi' => $validasiRecords->map(function ($record, $idx) {
                return [
                    'id' => $idx + 1,
                    'code_dosen_validator' => $record->dosenValidator?->toCode(),
                    'nama_dosen_validator' => optional($record->dosenValidator)->nama_dosen,
                    'status_krs' => $record->status_krs,
                ];
            })->values()->toArray(),
        ];

        return ApiResponse::success($data, 'Perwalian mahasiswa by nama retrieved successfully.');
    }

    public function getJumlahPerwalian(int $kode_dosen, int $semester): JsonResponse
    {
        $perwalian = $this->queryPerwalianByDosenSemester($kode_dosen, $semester);

        $data = [
            'code_dosen' => optional(Dosen::find($kode_dosen))->toCode(),
            'semester' => $semester,
            'jumlah' => $perwalian->count(),
            'perwalian' => $this->mapPerwalianCollection($perwalian),
        ];

        return ApiResponse::success($data, 'Jumlah perwalian retrieved successfully.');
    }

    public function getDaftarPerwalian(int $kode_dosen, int $semester): JsonResponse
    {
        $perwalian = $this->queryPerwalianByDosenSemester($kode_dosen, $semester);

        $data = [
            'code_dosen' => optional(Dosen::find($kode_dosen))->toCode(),
            'semester' => $semester,
            'perwalian' => $this->mapPerwalianCollection($perwalian),
        ];

        return ApiResponse::success($data, 'Daftar perwalian retrieved successfully.');
    }

    public function storeValidasiKrs(array $payload): JsonResponse
    {
        $mahasiswa = Mahasiswa::find($payload['nim']);
        if (! $mahasiswa) {
            return ApiResponse::notFound('Mahasiswa tidak ditemukan');
        }

        $isPembimbing = Perwalian::where('nim', $payload['nim'])
            ->where(function ($q) use ($payload) {
                $q->where('kode_dosen', $payload['kode_dosen_validator'])
                    ->orWhere('kode_dosen_perwakilan', $payload['kode_dosen_validator']);
            })
            ->exists();

        if (! $isPembimbing) {
            return ApiResponse::error('Anda bukan pembimbing mahasiswa ini.', 403);
        }

        $existing = PerwalianKrsValidasi::where('nim', $payload['nim'])
            ->where('kode_dosen_validator', $payload['kode_dosen_validator'])
            ->first();

        if ($existing) {
            return ApiResponse::error('Dosen ini sudah melakukan validasi KRS untuk mahasiswa ini.', 409);
        }

        $catatan = $payload['catatan'] ?? null;

        $validasi = PerwalianKrsValidasi::create([
            'nim' => $payload['nim'],
            'kode_dosen_validator' => $payload['kode_dosen_validator'],
            'status_krs' => $payload['status_krs'],
            'catatan' => $catatan,
        ]);

        // Jika status A, buat KhsDetail untuk setiap krs_detail
        if ($payload['status_krs'] === 'A') {
            $activeTA = TahunAkademik::active()->first();

            if ($activeTA) {
                $krs = Krs::where('nim', $payload['nim'])
                    ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
                    ->first();

                if ($krs) {
                    $krsDetails = KrsDetail::where('kode_krs', $krs->kode_krs)->get();

                    $insertData = [];
                    foreach ($krsDetails as $detail) {
                        $insertData[] = [
                            'kode_krs_detail' => $detail->kode_krs_detail,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (! empty($insertData)) {
                        KhsDetail::insert($insertData);
                    }
                }
            }
        }

        return ApiResponse::success([
            'code_perwalian_krs_validasi' => $validasi->toCode(),
            'nim' => $validasi->nim,
            'code_dosen_validator' => $validasi->dosenValidator?->toCode(),
            'status_krs' => $validasi->status_krs,
            'catatan' => $validasi->catatan,
        ], 'Validasi KRS berhasil disimpan.', 201);
    }

    public function revisiValidasiKrs(array $payload): JsonResponse
    {
        $validasi = PerwalianKrsValidasi::find($payload['kode_perwalian_krs_validasi']);
        if (! $validasi) {
            return ApiResponse::notFound('Validasi KRS tidak ditemukan');
        }

        if ($validasi->status_krs !== 'A') {
            return ApiResponse::error('Validasi KRS bukan status A, tidak bisa direvisi.', 422);
        }

        // Cek apakah sudah ada nilai
        $activeTA = TahunAkademik::active()->first();
        if ($activeTA) {
            $krs = Krs::where('nim', $validasi->nim)
                ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
                ->first();

            if ($krs) {
                $krsDetails = KrsDetail::where('kode_krs', $krs->kode_krs)->get();
                foreach ($krsDetails as $detail) {
                    $khsDetail = KhsDetail::where('kode_krs_detail', $detail->kode_krs_detail)
                        ->whereNotNull('nilai_akhir')
                        ->first();

                    if ($khsDetail) {
                        return ApiResponse::error('KRS sudah memiliki nilai, tidak bisa direvisi.', 422);
                    }
                }
            }
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Hapus KhsDetail terkait
            $activeTA = TahunAkademik::active()->first();
            if ($activeTA) {
                $krs = Krs::where('nim', $validasi->nim)
                    ->where('kode_tahun_akademik', $activeTA->kode_tahun_akademik)
                    ->first();

                if ($krs) {
                    $krsDetails = KrsDetail::where('kode_krs', $krs->kode_krs)->get();
                    foreach ($krsDetails as $detail) {
                        KhsDetail::where('kode_krs_detail', $detail->kode_krs_detail)->delete();
                    }
                }
            }

            // Update status validasi
            $validasi->update([
                'status_krs' => 'N',
                'catatan' => $payload['catatan'] ?? $validasi->catatan,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return ApiResponse::success([
                'code_perwalian_krs_validasi' => $validasi->toCode(),
                'nim' => $validasi->nim,
                'status_krs' => 'N',
                'catatan' => $validasi->catatan,
            ], 'Revisi KRS berhasil. Mahasiswa dapat mengubah KRS kembali.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return ApiResponse::serverError('Gagal merevisi KRS.');
        }
    }

    public function batalPerwalian(int $kode_perwalian, int $kode_dosen): JsonResponse
    {
        $perwalian = Perwalian::find($kode_perwalian);

        if (! $perwalian) {
            return ApiResponse::notFound('Perwalian tidak ditemukan');
        }

        if ($perwalian->kode_dosen != $kode_dosen && $perwalian->kode_dosen_perwakilan != $kode_dosen) {
            return ApiResponse::error('Anda tidak memiliki akses untuk membatalkan perwalian ini.', 403);
        }

        $perwalian->delete();

        return ApiResponse::success([
            'code_perwalian' => $perwalian->toCode(),
            'nim' => $perwalian->nim,
            'status' => 'dibatalkan',
        ], 'Perwalian berhasil dibatalkan.');
    }
}

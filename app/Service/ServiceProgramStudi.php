<?php

namespace App\Service;

use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;

class ServiceProgramStudi
{
    public function getAllProgramStudi(): JsonResponse
    {
        $paginator = ProgramStudi::with('kaprodi')->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => $item->toCode(),
                'nama_program_studi' => $item->nama_program_studi,
                'singkatan_program_studi' => $item->singkatan_program_studi,
                'kompetensi' => $item->kompetensi,
                'code_dosen_kaprodi' => $item->kaprodi?->toCode(),
                'nama_kaprodi' => $item->kaprodi?->nama_dosen,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'API Program Studi',
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

    public function getOneProgramStudi(string $id): JsonResponse
    {
        $data = ProgramStudi::with('kaprodi')->find($id);

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Program Studi tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Program Studi',
            'data' => [
                'code' => $data->toCode(),
                'nama_program_studi' => $data->nama_program_studi,
                'singkatan_program_studi' => $data->singkatan_program_studi,
                'kompetensi' => $data->kompetensi,
                'code_dosen_kaprodi' => $data->kaprodi?->toCode(),
                'nama_kaprodi' => $data->kaprodi?->nama_dosen,
            ],
        ]);
    }

    public function storeProgramStudi(array $object): JsonResponse
    {
        try {
            $programStudi = ProgramStudi::create($object);
            $programStudi->load('kaprodi');
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Program Studi',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Program Studi berhasil dibuat',
            'data' => [
                'code' => $programStudi->toCode(),
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
                'code_dosen_kaprodi' => $programStudi->kaprodi?->toCode(),
                'nama_kaprodi' => $programStudi->kaprodi?->nama_dosen,
            ],
        ], 201);
    }

    public function updateProgramStudi(string $id, array $object): JsonResponse
    {
        $programStudi = ProgramStudi::find($id);

        if (! $programStudi) {
            return response()->json([
                'status' => false,
                'message' => 'Program Studi tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $programStudi->update($object);
            $programStudi->load('kaprodi');
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Program Studi',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Program Studi berhasil diperbarui',
            'data' => [
                'code' => $programStudi->toCode(),
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
                'code_dosen_kaprodi' => $programStudi->kaprodi?->toCode(),
                'nama_kaprodi' => $programStudi->kaprodi?->nama_dosen,
            ],
        ]);
    }

    public function getKaprodi(string $id): JsonResponse
    {
        $programStudi = ProgramStudi::with('kaprodi')->find($id);

        if (! $programStudi) {
            return response()->json([
                'status' => false,
                'message' => 'Program Studi tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if (! $programStudi->kaprodi) {
            return response()->json([
                'status' => true,
                'message' => 'Program Studi belum memiliki Kaprodi',
                'data' => [
                    'nama_program_studi' => $programStudi->nama_program_studi,
                    'code_program_studi' => $programStudi->toCode(),
                    'kaprodi' => null,
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Kaprodi Program Studi',
            'data' => [
                'nama_program_studi' => $programStudi->nama_program_studi,
                'code_program_studi' => $programStudi->toCode(),
                'kaprodi' => [
                    'code' => $programStudi->kaprodi->toCode(),
                    'nama_dosen' => $programStudi->kaprodi->nama_dosen,
                    'nik' => $programStudi->kaprodi->nik,
                    'alamat_email' => $programStudi->kaprodi->alamat_email,
                    'no_telp' => $programStudi->kaprodi->no_telp,
                    'field_studi' => $programStudi->kaprodi->field_studi,
                    'alumni' => $programStudi->kaprodi->alumni,
                    'homebase' => $programStudi->nama_program_studi,
                    'aktif' => $programStudi->kaprodi->aktif,
                ],
            ],
        ]);
    }

    public function setKaprodi(string $id, ?int $kodeDosen): JsonResponse
    {
        $programStudi = ProgramStudi::find($id);

        if (! $programStudi) {
            return response()->json([
                'status' => false,
                'message' => 'Program Studi tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if ($kodeDosen) {
            $dosen = \App\Models\Dosen::find($kodeDosen);
            if (! $dosen) {
                return response()->json([
                    'status' => false,
                    'message' => 'Dosen yang dipilih tidak ditemukan',
                    'data' => null,
                ], 404);
            }
        }

        try {
            $programStudi->update([
                'kode_dosen_kaprodi' => $kodeDosen,
            ]);
            $programStudi->load('kaprodi');
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengatur Kaprodi: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => $kodeDosen ? 'Kaprodi berhasil ditugaskan' : 'Jabatan Kaprodi berhasil dikosongkan',
            'data' => [
                'code' => $programStudi->toCode(),
                'nama_program_studi' => $programStudi->nama_program_studi,
                'code_dosen_kaprodi' => $programStudi->kaprodi?->toCode(),
                'nama_kaprodi' => $programStudi->kaprodi?->nama_dosen,
            ],
        ]);
    }

    public function deleteProgramStudi(string $id): JsonResponse
    {
        $programStudi = ProgramStudi::find($id);

        if (! $programStudi) {
            return response()->json([
                'status' => false,
                'message' => 'Program Studi tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $programStudi->delete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Program Studi',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Program Studi berhasil dihapus',
            'data' => [
                'code' => $programStudi->toCode(),
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
            ],
        ]);
    }
}

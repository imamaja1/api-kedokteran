<?php

namespace App\Service;

use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;

class ServiceProgramStudi
{
    public function getAllProgramStudi(): JsonResponse
    {
        $paginator = ProgramStudi::paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return [
                'id' => $index + 1,
                'code' => $item->toCode(),
                'nama_program_studi' => $item->nama_program_studi,
                'singkatan_program_studi' => $item->singkatan_program_studi,
                'kompetensi' => $item->kompetensi,
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
        $data = ProgramStudi::find($id);

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
            ],
        ]);
    }

    public function storeProgramStudi(array $object): JsonResponse
    {
        try {
            $programStudi = ProgramStudi::create($object);
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

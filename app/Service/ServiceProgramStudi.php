<?php

namespace App\Service;

use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Crypt;

class ServiceProgramStudi
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllProgramStudi()
    {
        $data = ProgramStudi::all()
            ->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
                    'code' => Crypt::encryptString($item->kode_program_studi),
                    'nama_program_studi' => $item->nama_program_studi,
                    'singkatan_program_studi' => $item->singkatan_program_studi,
                    'kompetensi' => $item->kompetensi,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'API Program Studi',
            'data' => $data,
        ]);
    }

    public function getOneProgramStudi($id)
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
                'code' => Crypt::encryptString($data->kode_program_studi),
                'nama_program_studi' => $data->nama_program_studi,
                'singkatan_program_studi' => $data->singkatan_program_studi,
                'kompetensi' => $data->kompetensi,
            ],
        ]);
    }

    public function storeProgramStudi($object)
    {
        try {
            $programStudi = ProgramStudi::create($object);
        } catch (\Throwable $th) {
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
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
            ],
        ], 201);
    }

    public function updateProgramStudi($id, $object)
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
        } catch (\Throwable $th) {
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
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
            ],
        ]);
    }

    public function deleteProgramStudi($id)
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
        } catch (\Throwable $th) {
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
                'nama_program_studi' => $programStudi->nama_program_studi,
                'singkatan_program_studi' => $programStudi->singkatan_program_studi,
                'kompetensi' => $programStudi->kompetensi,
            ],
        ]);
    }
}

<?php

namespace App\Service;
use App\Models\ProgramStudi;
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
        $data = ProgramStudi::select(
            'kode_program_studi',
            'nama_program_studi',
            'singkatan_program_studi'
        )->get();
        return response()->json([
            'status' => true,
            'message' => 'API Program Studi',
            'data' => $data
        ]);
    }
}

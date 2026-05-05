<?php

namespace App\Service;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
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
            'kode_program_studi as id',
            'nama_program_studi',
            'singkatan_program_studi'
        )->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'code' =>  Crypt::encryptString($item->id),
                'nama_program_studi' => $item->nama_program_studi,
                'singkatan_program_studi' => $item->singkatan_program_studi,
            ];
        });
        return response()->json([
            'status' => true,
            'message' => 'API Program Studi',
            'data' => $data
        ]);
    }
}

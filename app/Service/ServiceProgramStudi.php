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
        $data = ProgramStudi::all()
        ->map(function ($item,$nomor) {
            return [
                'id' => $nomor+1,
                'code' =>  Crypt::encryptString($item->kode_program_studi),
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

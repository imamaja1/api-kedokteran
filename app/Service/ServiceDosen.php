<?php

namespace App\Service;
use App\Models\Dosen;

class ServiceDosen
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getAllDosen($kode_program_studi = null, $nama_dosen = null, $alamat_email = null)
    {
        $data = Dosen::select('*')
                    ->with('programStudi')
                    ->when($kode_program_studi, function ($query, $kode_program_studi) {
                        return $query->where('kode_program_studi', $kode_program_studi);
                    })
                    ->when($nama_dosen, function ($query, $nama_dosen) {
                        return $query->where('nama_dosen', 'like', "%{$nama_dosen}");
                    })
                    ->when($alamat_email, function ($query, $alamat_email) {
                        return $query->where('alamat_email', 'like', "%{$alamat_email}");
                    })
                    ->get();
        return response()->json([
            'status' => true,
            'message' => 'API Dosen',
            'data' => $data
        ]);
    }
}

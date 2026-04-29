<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceMatakuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MasterDataController extends Controller
{
    //
    public function __construct()
    {
        //
    }

    public function GetMatakuliah()
    {
        return (new ServiceMatakuliah())->getAllMatakuliah();
    }
    Public function GetOneMatakuliah(Request $request)
    {
        $code = $request->query('code');
        $id =  Crypt::decryptString($code);
        return (new ServiceMatakuliah())->getOneMatakuliah($id);
    }
    public function StoreMatakuliah(Request $request)
    {
        $validasi = $request->validate([
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num', 'unique:matakuliah,kode_matakuliah'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'boolean'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'boolean'],
            'kode_program_studi' => ['required', 'string', 'max:20', 'alpha_num', 'exists:program_studi,kode_program_studi'],
        ]);
        return (new ServiceMatakuliah())->storeMatakuliah($validasi);
    }
    public function UpdateMatakuliah(Request $request)
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'boolean'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'boolean'],
            'kode_program_studi' => ['required', 'string', 'max:20', 'alpha_num', 'exists:program_studi,kode_program_studi'],
        ]);
        $id = Crypt::decryptString($validasi['code']);
        return (new ServiceMatakuliah())->updateMatakuliah($id, $validasi);
    }
    Public function DeleteMatakuliah($code)
    {
        $id = Crypt::decryptString($code);
        return (new ServiceMatakuliah())->deleteMatakuliah($id);
    }




    public function GetDosen()
    {
        
    }
    public function GetProgramStudi()
    {
        
    }
    public function GetTahunAngkatan()
    {
        
    }
}

<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceDosen;
use App\Service\ServiceMatakuliah;
use App\Service\ServiceProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MasterDataController extends Controller
{
    //
    public function __construct()
    {
        //
    }

    public function GetProgramStudi()
    {
        return (new ServiceProgramStudi)->getAllProgramStudi();
    }

    public function GetMatakuliah(Request $request)
    {
        $request->validate([
            'code_program_studi' => ['nullable', 'string'],
        ]);
        $kode_program_studi = $request->query('code_program_studi') ? Crypt::decryptString($request->query('code_program_studi')) : null;

        return (new ServiceMatakuliah)->getAllMatakuliah($kode_program_studi);
    }

    public function GetOneMatakuliah(Request $request)
    {
        $code = $request->query('code');
        $id = Crypt::decryptString($code);

        return (new ServiceMatakuliah)->getOneMatakuliah($id);
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

        return (new ServiceMatakuliah)->storeMatakuliah($validasi);
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

        return (new ServiceMatakuliah)->updateMatakuliah($id, $validasi);
    }

    public function DeleteMatakuliah($code)
    {
        $id = Crypt::decryptString($code);

        return (new ServiceMatakuliah)->deleteMatakuliah($id);
    }

    public function GetDosen(Request $request)
    {
        $validasi = $request->validate([
            'kode_program_studi' => ['nullable', 'string'],
            'nama_dosen' => ['nullable', 'string', 'max:255'],
            'alamat_email' => ['nullable', 'string', 'max:255', 'email'],
        ]);
        $kode_program_studi = $request->query('kode_program_studi') ? Crypt::decryptString($request->query('kode_program_studi')) : null;
        $nama_dosen = Crypt::decryptString($request->query('nama_dosen')) ?? $request->query('nama_dosen');
        $alamat_email = Crypt::decryptString($request->query('alamat_email')) ?? $request->query('alamat_email');

        return (new ServiceDosen)->getAllDosen($kode_program_studi, $nama_dosen, $alamat_email);
    }

    public function GetTahunAngkatan() {}
}

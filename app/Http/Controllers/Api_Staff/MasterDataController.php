<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceDosen;
use App\Service\ServiceKurikulum;
use App\Service\ServiceMatakuliah;
use App\Service\ServiceProgramStudi;
use App\Service\ServiceTahunAkademik;
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

    public function GetOneProgramStudi(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return (new ServiceProgramStudi)->getOneProgramStudi($id);
    }

    public function StoreProgramStudi(Request $request)
    {
        $validasi = $request->validate([
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
        ]);

        return (new ServiceProgramStudi)->storeProgramStudi($validasi);
    }

    public function UpdateProgramStudi(Request $request)
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_program_studi' => ['required', 'string', 'max:80'],
            'singkatan_program_studi' => ['required', 'string', 'max:20'],
            'kompetensi' => ['nullable', 'in:Y,N'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return (new ServiceProgramStudi)->updateProgramStudi($id, $validasi);
    }

    public function DeleteProgramStudi($code)
    {
        $id = Crypt::decryptString($code);

        return (new ServiceProgramStudi)->deleteProgramStudi($id);
    }

    public function GetNamaKurikulum()
    {
        return (new ServiceKurikulum)->nama_kurikulum();
    }

    public function GetOneNamaKurikulum(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return (new ServiceKurikulum)->getOneNamaKurikulum($id);
    }

    public function StoreNamaKurikulum(Request $request)
    {
        $validasi = $request->validate([
            'nama_kurikulum' => ['required', 'string', 'max:20'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
            'angkatan1' => ['nullable', 'string', 'max:255'],
            'ekstensi1' => ['nullable', 'in:Y,N'],
            'paket1' => ['nullable', 'in:Y,N'],
        ]);

        return (new ServiceKurikulum)->storeNamaKurikulum($validasi);
    }

    public function UpdateNamaKurikulum(Request $request)
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_kurikulum' => ['required', 'string', 'max:20'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
            'angkatan1' => ['nullable', 'string', 'max:255'],
            'ekstensi1' => ['nullable', 'in:Y,N'],
            'paket1' => ['nullable', 'in:Y,N'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return (new ServiceKurikulum)->updateNamaKurikulum($id, $validasi);
    }

    public function DeleteNamaKurikulum($code)
    {
        $id = Crypt::decryptString($code);

        return (new ServiceKurikulum)->deleteNamaKurikulum($id);
    }

    public function GetTahunAkademik(Request $request)
    {
        $validasi = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['nullable', 'in:1,2'],
            'status' => ['nullable', 'in:A,N'],
        ]);

        return (new ServiceTahunAkademik)->getAllTahunAkademik($validasi);
    }

    public function GetOneTahunAkademik(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return (new ServiceTahunAkademik)->getOneTahunAkademik($id);
    }

    public function StoreTahunAkademik(Request $request)
    {
        $validasi = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['required', 'in:A,N'],
            'status_kpat' => ['nullable', 'in:A,N'],
        ]);

        return (new ServiceTahunAkademik)->storeTahunAkademik($validasi);
    }

    public function UpdateTahunAkademik(Request $request)
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'tahun_akademik' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after:tanggal_mulai'],
            'status' => ['required', 'in:A,N'],
            'status_kpat' => ['nullable', 'in:A,N'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return (new ServiceTahunAkademik)->updateTahunAkademik($id, $validasi);
    }

    public function DeleteTahunAkademik($code)
    {
        $id = Crypt::decryptString($code);

        return (new ServiceTahunAkademik)->deleteTahunAkademik($id);
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
        $nama_dosen = $request->query('nama_dosen') ? Crypt::decryptString($request->query('nama_dosen')) : null;
        $alamat_email = $request->query('alamat_email') ? Crypt::decryptString($request->query('alamat_email')) : null;

        return (new ServiceDosen)->getAllDosen($kode_program_studi, $nama_dosen, $alamat_email);
    }

    public function GetOneDosen(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return (new ServiceDosen)->getOneDosen($id);
    }

    public function StoreDosen(Request $request)
    {
        $validasi = $request->validate([
            'nama_dosen' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'alamat_email' => ['nullable', 'string', 'max:100', 'email'],
            'field_studi' => ['nullable', 'string', 'max:255'],
            'alumni' => ['nullable', 'string', 'max:255'],
            'homebase' => ['nullable', 'integer', 'exists:program_studi,kode_program_studi'],
            'status_dosen' => ['required', 'in:T,L'],
            'aktif' => ['required', 'in:A,N'],
            'chatid' => ['nullable', 'string', 'max:20'],
            'sandi_pengguna' => ['nullable', 'string', 'min:6'],
        ]);

        return (new ServiceDosen)->storeDosen($validasi);
    }

    public function UpdateDosen(Request $request)
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'alamat_email' => ['nullable', 'string', 'max:100', 'email'],
            'field_studi' => ['nullable', 'string', 'max:255'],
            'alumni' => ['nullable', 'string', 'max:255'],
            'homebase' => ['nullable', 'integer', 'exists:program_studi,kode_program_studi'],
            'status_dosen' => ['required', 'in:T,L'],
            'aktif' => ['required', 'in:A,N'],
            'chatid' => ['nullable', 'string', 'max:20'],
            'sandi_pengguna' => ['nullable', 'string', 'min:6'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return (new ServiceDosen)->updateDosen($id, $validasi);
    }

    public function DeleteDosen($code)
    {
        $id = Crypt::decryptString($code);

        return (new ServiceDosen)->deleteDosen($id);
    }

    public function GetTahunAngkatan()
    {
    }
}

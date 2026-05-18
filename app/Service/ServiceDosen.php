<?php

namespace App\Service;

use App\Models\Dosen;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

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
            ->when($kode_program_studi, function ($query, $kode_program_studi) {
                return $query->where('kode_program_studi', $kode_program_studi);
            })
            ->when($nama_dosen, function ($query, $nama_dosen) {
                return $query->where('nama_dosen', 'like', "%{$nama_dosen}%");
            })
            ->when($alamat_email, function ($query, $alamat_email) {
                return $query->where('alamat_email', 'like', "%{$alamat_email}%");
            })
            ->get()->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
                    'code' => Crypt::encryptString($item->kode_dosen),
                    'nama_dosen' => $item->nama_dosen,
                    'nik' => $item->nik,
                    'no_telp' => $item->no_telp,
                    'alamat_email' => $item->alamat_email,
                    'field_studi' => $item->field_studi,
                    'alumni' => $item->alumni,
                    'homebase' => $item->programStudi->nama_program_studi,
                    'status_dosen' => $item->status_dosen,
                    'aktif' => $item->aktif,
                    'status_login' => $item->status_login,
                    'signature' => $item->signature,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'API Dosen',
            'data' => $data,
        ]);
    }

    public function getOneDosen($id)
    {
        $data = Dosen::find($id)->get()
            ->map(function ($item) {
                return [
                    'code' => Crypt::encryptString($item->kode_dosen),
                    'nama_dosen' => $item->nama_dosen,
                    'nik' => $item->nik,
                    'no_telp' => $item->no_telp,
                    'alamat_email' => $item->alamat_email,
                    'field_studi' => $item->field_studi,
                    'alumni' => $item->alumni,
                    'homebase' => $item->programStudi->nama_program_studi,
                    'status_dosen' => $item->status_dosen,
                    'aktif' => $item->aktif,
                    'status_login' => $item->status_login,
                    'signature' => $item->signature,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->first();

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Dosen',
            'data' => $data,
        ]);
    }

    public function storeDosen($object)
    {
        if (! empty($object['sandi_pengguna'])) {
            $object['sandi_pengguna'] = Hash::make($object['sandi_pengguna']);
        } else {
            unset($object['sandi_pengguna']);
        }

        $object['chatid'] = $object['chatid'] ?? '';
        $object['status_login'] = 'N';

        try {
            $dosen = Dosen::create($object);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat Dosen',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dosen berhasil dibuat',
            'data' => $dosen,
        ], 201);
    }

    public function updateDosen($id, $object)
    {
        $dosen = Dosen::find($id);

        if (! $dosen) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if (! empty($object['sandi_pengguna'])) {
            $object['sandi_pengguna'] = Hash::make($object['sandi_pengguna']);
        } else {
            unset($object['sandi_pengguna']);
        }

        try {
            $dosen->update($object);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui Dosen',
                'data' => $dosen,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dosen berhasil diperbarui',
            'data' => $dosen,
        ]);
    }

    public function deleteDosen($id)
    {
        $dosen = Dosen::find($id);

        if (! $dosen) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan',
                'data' => null,
            ], 404);
        }

        try {
            $dosen->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus Dosen',
                'data' => $dosen,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dosen berhasil dihapus',
            'data' => $dosen,
        ]);
    }
}

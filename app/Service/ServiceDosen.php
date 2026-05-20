<?php

namespace App\Service;

use App\Models\Dosen;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ServiceDosen
{
    public function getAllDosen(?string $kode_program_studi = null, ?string $nama_dosen = null, ?string $alamat_email = null)
    {
        $data = Dosen::select('*')
            ->with('programStudi')
            ->when($kode_program_studi, function ($query, $kode) {
                return $query->where('kode_program_studi', $kode);
            })
            ->when($nama_dosen, function ($query, $nama) {
                return $query->where('nama_dosen', 'like', "%{$nama}%");
            })
            ->when($alamat_email, function ($query, $email) {
                return $query->where('alamat_email', 'like', "%{$email}%");
            })
            ->get()
            ->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
                    'code' => Crypt::encryptString($item->kode_dosen),
                    'nama_dosen' => $item->nama_dosen,
                    'nik' => $item->nik,
                    'no_telp' => $item->no_telp,
                    'alamat_email' => $item->alamat_email,
                    'field_studi' => $item->field_studi,
                    'alumni' => $item->alumni,
                    'homebase' => $item->programStudi?->nama_program_studi,
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

    public function getOneDosen(string $id)
    {
        $dosen = Dosen::with('programStudi')->where('kode_dosen', $id)->first();

        if (! $dosen) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan',
                'data' => null,
            ], 404);
        }

        $data = [
            'code' => Crypt::encryptString($dosen->kode_dosen),
            'nama_dosen' => $dosen->nama_dosen,
            'nik' => $dosen->nik,
            'no_telp' => $dosen->no_telp,
            'alamat_email' => $dosen->alamat_email,
            'field_studi' => $dosen->field_studi,
            'alumni' => $dosen->alumni,
            'homebase' => $dosen->programStudi?->nama_program_studi,
            'status_dosen' => $dosen->status_dosen,
            'aktif' => $dosen->aktif,
            'status_login' => $dosen->status_login,
            'signature' => $dosen->signature,
            'created_at' => $dosen->created_at,
            'updated_at' => $dosen->updated_at,
        ];

        return response()->json([
            'status' => true,
            'message' => 'API Dosen',
            'data' => $data,
        ]);
    }

    public function storeDosen(array $object)
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
        } catch (\Throwable) {
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

    public function updateDosen(string $id, array $object)
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
        } catch (\Throwable) {
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

    public function deleteDosen(string $id)
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
        } catch (\Throwable) {
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

    public function getDosenTrash(?string $kode_program_studi = null, ?string $nama_dosen = null, ?string $alamat_email = null)
    {
        $data = Dosen::onlyTrashed()
            ->with('programStudi')
            ->when($kode_program_studi, function ($query, $kode) {
                return $query->where('kode_program_studi', $kode);
            })
            ->when($nama_dosen, function ($query, $nama) {
                return $query->where('nama_dosen', 'like', "%{$nama}%");
            })
            ->when($alamat_email, function ($query, $email) {
                return $query->where('alamat_email', 'like', "%{$email}%");
            })
            ->get()
            ->map(function ($item, $nomor) {
                return [
                    'id' => $nomor + 1,
                    'code' => Crypt::encryptString($item->kode_dosen),
                    'nama_dosen' => $item->nama_dosen,
                    'nik' => $item->nik,
                    'no_telp' => $item->no_telp,
                    'alamat_email' => $item->alamat_email,
                    'field_studi' => $item->field_studi,
                    'alumni' => $item->alumni,
                    'homebase' => $item->programStudi?->nama_program_studi,
                    'status_dosen' => $item->status_dosen,
                    'aktif' => $item->aktif,
                    'status_login' => $item->status_login,
                    'signature' => $item->signature,
                    'deleted_at' => $item->deleted_at,
                ];
            });

        if ($data->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada Dosen yang dihapus',
                'jumlah' => 0,
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'API Dosen (Trash)',
            'jumlah' => $data->count(),
            'data' => $data,
        ]);
    }

    public function restoreDosen(string $id)
    {
        $dosen = Dosen::onlyTrashed()->where('kode_dosen', $id)->first();

        if (! $dosen) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan di trash',
                'data' => null,
            ], 404);
        }

        try {
            $dosen->restore();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memulihkan Dosen',
                'data' => $dosen,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dosen berhasil dipulihkan',
            'data' => $dosen,
        ]);
    }

    public function forceDeleteDosen(string $id)
    {
        $dosen = Dosen::onlyTrashed()->where('kode_dosen', $id)->first();

        if (! $dosen) {
            return response()->json([
                'status' => false,
                'message' => 'Dosen tidak ditemukan di trash',
                'data' => null,
            ], 404);
        }

        try {
            $dosen->forceDelete();
        } catch (\Throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus permanen Dosen',
                'data' => $dosen,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dosen berhasil dihapus permanen',
            'data' => $dosen,
        ]);
    }
}

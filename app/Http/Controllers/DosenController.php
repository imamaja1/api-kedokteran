<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DosenController extends Controller
{
    public function index(): JsonResponse
    {
        $dosen = Dosen::select([
            'kode_dosen', 'nama_dosen', 'field_studi', 'nik',
            'no_telp', 'alamat_email', 'homebase', 'status_dosen', 'aktif',
        ])->paginate(20);

        return response()->json(['status' => true, 'data' => $dosen]);
    }

    public function show(Request $request): JsonResponse
    {
        $kode = $request->query('kode_dosen');
        abort_if(! $kode, 422, 'Parameter kode_dosen wajib diisi.');

        $dosen = Dosen::findOrFail($kode);

        return response()->json(['status' => true, 'data' => $dosen]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['kode_dosen' => 'required|string|exists:dosen,kode_dosen']);
        $dosen = Dosen::findOrFail($request->input('kode_dosen'));

        $validated = $request->validate([
            'nama_dosen'   => 'sometimes|string|max:125',
            'field_studi'  => 'sometimes|string|max:100',
            'no_telp'      => 'sometimes|string|max:20',
            'alamat_email' => 'sometimes|email|max:75',
            'alamat'       => 'sometimes|string|max:255',
            'sandi_pengguna' => 'sometimes|string|min:6',
        ]);

        if (isset($validated['sandi_pengguna'])) {
            $validated['sandi_pengguna'] = Hash::make($validated['sandi_pengguna']);
        }

        $dosen->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Data dosen berhasil diupdate.',
            'data'    => $dosen->fresh(),
        ]);
    }
}

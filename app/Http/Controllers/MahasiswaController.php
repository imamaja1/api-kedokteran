<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MahasiswaController extends Controller
{
    public function index(): JsonResponse
    {
        $mahasiswa = Mahasiswa::select([
            'nim', 'nama_mahasiswa', 'email', 'program_studi_kode',
            'status', 'status_pendaftaran', 'created_at',
        ])->get();

        return response()->json(['status' => true, 'data' => $mahasiswa]);
    }

    public function show(Request $request): JsonResponse
    {
        $nim = $request->query('nim');
        abort_if(! $nim, 422, 'Parameter nim wajib diisi.');

        $mahasiswa = Mahasiswa::findOrFail($nim);

        return response()->json(['status' => true, 'data' => $mahasiswa]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => 'required|string|size:11|unique:mahasiswa,nim',
            'nik' => 'required|string|max:20',
            'npm' => 'required|string|max:23',
            'nama_mahasiswa' => 'required|string|max:125',
            'email' => 'nullable|email|max:75',
            'sandi' => 'required|string|min:6',
        ]);

        $validated['sandi'] = Hash::make($validated['sandi']);

        $mahasiswa = Mahasiswa::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil ditambahkan.',
            'data' => $mahasiswa,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['nim' => 'required|string|exists:mahasiswa,nim']);
        $mahasiswa = Mahasiswa::findOrFail($request->input('nim'));

        $validated = $request->validate([
            'nama_mahasiswa' => 'sometimes|string|max:125',
            'email' => 'sometimes|email|max:75',
            'telepon' => 'sometimes|string|max:20',
            'alamat' => 'sometimes|string|max:75',
            'sandi' => 'sometimes|string|min:6',
        ]);

        if (isset($validated['sandi'])) {
            $validated['sandi'] = Hash::make($validated['sandi']);
        }

        $mahasiswa->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil diupdate.',
            'data' => $mahasiswa,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $nim = $request->query('nim');
        abort_if(! $nim, 422, 'Parameter nim wajib diisi.');

        $mahasiswa = Mahasiswa::findOrFail($nim);
        $mahasiswa->delete(); // soft delete

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dihapus.',
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $request->validate(['nim' => 'required|string']);
        $mahasiswa = Mahasiswa::withTrashed()->where('nim', $request->input('nim'))->firstOrFail();
        $mahasiswa->restore();

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dipulihkan.',
            'data' => $mahasiswa,
        ]);
    }

    public function forceDelete(Request $request): JsonResponse
    {
        $nim = $request->query('nim');
        abort_if(! $nim, 422, 'Parameter nim wajib diisi.');

        $mahasiswa = Mahasiswa::withTrashed()->where('nim', $nim)->firstOrFail();
        $mahasiswa->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'Mahasiswa berhasil dihapus permanen.',
        ]);
    }
}

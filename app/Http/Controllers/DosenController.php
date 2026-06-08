<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DosenController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = Auth::guard('dosen_web')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. User not found.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profil dosen retrieved successfully.',
            'data' => [
                'code' => $user->toCode(),
                'nik' => $user->nik,
                'no_telp' => $user->no_telp,
                'nama_dosen' => $user->nama_dosen,
                'alamat_email' => $user->alamat_email,
                'status_dosen' => $user->status_dosen,
                'homebase' => $user->homebase,
                'nama_program_studi' => optional($user->programStudi)->nama_program_studi,
            ],
        ]);
    }

    public function profileUpdate(Request $request): JsonResponse
    {
        $user = Auth::guard('dosen_web')->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'nik'          => 'sometimes|string|max:30',
            'no_telp'      => 'sometimes|string|max:20',
            'alamat_email' => 'sometimes|email|max:75',
            'sandi'        => 'sometimes|string|min:6',
        ]);

        if (isset($validated['sandi'])) {
            $validated['sandi_pengguna'] = Hash::make($validated['sandi']);
            unset($validated['sandi']);
        }

        $user->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'code' => $user->toCode(),
                'nik' => $user->nik,
                'no_telp' => $user->no_telp,
                'nama_dosen' => $user->nama_dosen,
                'alamat_email' => $user->alamat_email,
                'status_dosen' => $user->status_dosen,
                'homebase' => $user->homebase,
                'nama_program_studi' => optional($user->programStudi)->nama_program_studi,
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $dosen = Dosen::select([
            'kode_dosen', 'nama_dosen', 'field_studi', 'nik',
            'no_telp', 'alamat_email', 'homebase', 'status_dosen', 'aktif',
        ])->paginate(20);

        $dosen->getCollection()->transform(function ($item) {
            return [
                'code' => $item->toCode(),
                'nama_dosen' => $item->nama_dosen,
                'field_studi' => $item->field_studi,
                'nik' => $item->nik,
                'no_telp' => $item->no_telp,
                'alamat_email' => $item->alamat_email,
                'homebase' => $item->homebase,
                'status_dosen' => $item->status_dosen,
                'aktif' => $item->aktif,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Data dosen retrieved successfully.',
            'data' => $dosen->items(),
            'pagination' => [
                'current_page' => $dosen->currentPage(),
                'per_page' => $dosen->perPage(),
                'total' => $dosen->total(),
                'last_page' => $dosen->lastPage(),
                'from' => $dosen->firstItem(),
                'to' => $dosen->lastItem(),
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $code = $request->query('code');
        abort_if(! $code, 422, 'Parameter code wajib diisi.');

        $dosen = Dosen::findByCode($code);

        if (! $dosen) {
            return response()->json(['status' => false, 'message' => 'Dosen tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data dosen retrieved successfully.',
            'data' => [
                'code' => $dosen->toCode(),
                'nama_dosen' => $dosen->nama_dosen,
                'field_studi' => $dosen->field_studi,
                'nik' => $dosen->nik,
                'no_telp' => $dosen->no_telp,
                'alamat_email' => $dosen->alamat_email,
                'homebase' => $dosen->homebase,
                'status_dosen' => $dosen->status_dosen,
                'aktif' => $dosen->aktif,
                'nama_program_studi' => optional($dosen->programStudi)->nama_program_studi,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);
        $dosen = Dosen::findByCode($request->input('code'));

        if (! $dosen) {
            return response()->json(['status' => false, 'message' => 'Dosen tidak ditemukan.'], 404);
        }

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
            'data'    => [
                'code' => $dosen->fresh()->toCode(),
                'nama_dosen' => $dosen->fresh()->nama_dosen,
                'field_studi' => $dosen->fresh()->field_studi,
                'nik' => $dosen->fresh()->nik,
                'no_telp' => $dosen->fresh()->no_telp,
                'alamat_email' => $dosen->fresh()->alamat_email,
                'homebase' => $dosen->fresh()->homebase,
                'status_dosen' => $dosen->fresh()->status_dosen,
                'aktif' => $dosen->fresh()->aktif,
                'nama_program_studi' => optional($dosen->fresh()->programStudi)->nama_program_studi,
            ],
        ]);
    }
}

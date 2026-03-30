<?php

namespace App\Http\Controllers;

use App\Models\KhsDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KhsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $khs = KhsDetail::with(['krsDetail.matakuliah', 'krsDetail.krs.mahasiswa:nim,nama_mahasiswa'])
            ->paginate(20);

        return response()->json(['status' => true, 'data' => $khs]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_krs_detail' => 'required|integer|exists:krs_detail,kode_krs_detail',
            'nilai_harian' => 'nullable|numeric|min:0|max:100',
            'nilai_uts' => 'nullable|numeric|min:0|max:100',
            'nilai_uas' => 'nullable|numeric|min:0|max:100',
            'nilai_akhir' => 'nullable|numeric|min:0|max:100',
            'tidak_berhak' => 'nullable|in:A,N',
        ]);

        $khs = KhsDetail::updateOrCreate(
            ['kode_krs_detail' => $validated['kode_krs_detail']],
            $validated
        );

        return response()->json([
            'status' => true,
            'message' => 'Nilai KHS berhasil disimpan.',
            'data' => $khs,
        ], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $id = $request->query('id');
        abort_if(! $id, 422, 'Parameter id wajib diisi.');

        $khs = KhsDetail::with(['krsDetail.matakuliah'])->findOrFail($id);

        return response()->json(['status' => true, 'data' => $khs]);
    }
}

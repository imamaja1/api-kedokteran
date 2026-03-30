<?php

namespace App\Http\Controllers;

use App\Models\Krs;
use App\Models\KrsDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KrsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $krs = Krs::with(['tahunAkademik', 'mahasiswa:nim,nama_mahasiswa'])
            ->when($request->nim, fn ($q) => $q->where('nim', $request->nim))
            ->paginate(20);

        return response()->json(['status' => true, 'data' => $krs]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_tahun_akademik' => 'required|integer|exists:tahun_akademik,kode_tahun_akademik',
            'nim' => 'required|string|exists:mahasiswa,nim',
            'semester' => 'required|string|max:2',
        ]);

        $krs = Krs::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'KRS berhasil dibuat.',
            'data' => $krs,
        ], 201);
    }

    public function showDetail(Request $request): JsonResponse
    {
        $id = $request->query('id');
        abort_if(! $id, 422, 'Parameter id wajib diisi.');

        $krs = Krs::with(['detail.matakuliah', 'tahunAkademik', 'mahasiswa:nim,nama_mahasiswa'])
            ->findOrFail($id);

        return response()->json(['status' => true, 'data' => $krs]);
    }

    public function storeDetail(Request $request): JsonResponse
    {
        $request->validate(['kode_krs' => 'required|integer|exists:krs,kode_krs']);
        $krs = Krs::findOrFail($request->input('kode_krs'));

        $validated = $request->validate([
            'id_matakuliah' => 'required|integer|exists:matakuliah,id_matakuliah',
            'kode_matakuliah' => 'required|string|max:10',
            'status' => 'nullable|in:B,U,K',
        ]);

        $validated['kode_krs'] = $krs->kode_krs;

        $detail = KrsDetail::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil ditambahkan ke KRS.',
            'data' => $detail->load('matakuliah'),
        ], 201);
    }

    public function destroyDetail(Request $request): JsonResponse
    {
        $kodeKrs = $request->query('kode_krs');
        $kodeKrsDetail = $request->query('kode_krs_detail');
        abort_if(! $kodeKrs || ! $kodeKrsDetail, 422, 'Parameter kode_krs dan kode_krs_detail wajib diisi.');

        $detail = KrsDetail::where('kode_krs', $kodeKrs)
            ->where('kode_krs_detail', $kodeKrsDetail)
            ->firstOrFail();

        $detail->delete();

        return response()->json([
            'status' => true,
            'message' => 'Matakuliah berhasil dihapus dari KRS.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatakuliahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $matakuliah = Matakuliah::query()
            ->when($request->search, fn ($q) => $q->where('nama_matakuliah', 'like', "%{$request->search}%"))
            ->when($request->kode_program_studi, fn ($q) => $q->where('kode_program_studi', $request->kode_program_studi))
            ->paginate(20);

        return response()->json(['status' => true, 'data' => $matakuliah]);
    }

    public function show(Request $request): JsonResponse
    {
        $id = $request->query('id');
        abort_if(! $id, 422, 'Parameter id wajib diisi.');

        $matakuliah = Matakuliah::findOrFail($id);

        return response()->json(['status' => true, 'data' => $matakuliah]);
    }
}

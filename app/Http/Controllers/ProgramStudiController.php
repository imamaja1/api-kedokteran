<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramStudiController extends Controller
{
    public function index(): JsonResponse
    {
        $prodi = ProgramStudi::all();

        return response()->json(['status' => true, 'data' => $prodi]);
    }

    public function show(Request $request): JsonResponse
    {
        $id = $request->query('id');
        abort_if(! $id, 422, 'Parameter id wajib diisi.');

        $prodi = ProgramStudi::findOrFail($id);

        return response()->json(['status' => true, 'data' => $prodi]);
    }
}

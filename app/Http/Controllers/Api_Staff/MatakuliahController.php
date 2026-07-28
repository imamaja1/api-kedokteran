<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceMatakuliah;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MatakuliahController extends Controller
{
    public function __construct(
        private readonly ServiceMatakuliah $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_program_studi' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $kode_program_studi = isset($validated['code_program_studi'])
            ? Crypt::decryptString($validated['code_program_studi'])
            : null;

        return $this->service->getAllMatakuliah(
            $kode_program_studi,
            (int) ($validated['per_page'] ?? 20)
        );
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $id = Crypt::decryptString($request->query('code'));

        return $this->service->getOneMatakuliah($id);
    }

    public function store(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['nullable', 'boolean'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'in:0,1'],
            'code_program_studi' => ['required', 'string'],
        ]);

        try {
            $validasi['kode_program_studi'] = (int) Crypt::decryptString($validasi['code_program_studi']);
            unset($validasi['code_program_studi']);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code_program_studi tidak valid',
            ], 422);
        }

        return $this->service->storeMatakuliah($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:0,1'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'in:0,1'],
            'code_program_studi' => ['required', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($validasi['code']);
            $validasi['kode_program_studi'] = (int) Crypt::decryptString($validasi['code_program_studi']);
            unset($validasi['code_program_studi']);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->updateMatakuliah($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $id = Crypt::decryptString($code);

        return $this->service->deleteMatakuliah($id);
    }

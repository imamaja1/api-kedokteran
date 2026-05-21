<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceMatakuliah;
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
        $request->validate([
            'code_program_studi' => ['nullable', 'string'],
        ]);

        $kode_program_studi = $request->query('code_program_studi')
            ? Crypt::decryptString($request->query('code_program_studi'))
            : null;

        return $this->service->getAllMatakuliah($kode_program_studi);
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
        $decrypted = $this->decryptField($request, 'kode_program_studi');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num', 'unique:matakuliah,kode_matakuliah'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'boolean'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'boolean'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
        ]);

        return $this->service->storeMatakuliah($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $decrypted = $this->decryptField($request, 'kode_program_studi');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'kode_matakuliah' => ['required', 'string', 'max:20', 'alpha_num'],
            'nama_matakuliah' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'boolean'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'block' => ['required', 'boolean'],
            'kode_program_studi' => ['required', 'integer', 'exists:program_studi,kode_program_studi'],
        ]);

        $id = Crypt::decryptString($validasi['code']);

        return $this->service->updateMatakuliah($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        $id = Crypt::decryptString($code);

        return $this->service->deleteMatakuliah($id);
    }

    private function decryptField(Request $request, string $field): ?JsonResponse
    {
        if (! $request->has($field)) {
            return null;
        }

        try {
            $request->merge([
                $field => Crypt::decryptString($request->input($field)),
            ]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => "Format {$field} tidak valid",
            ], 422);
        }

        return null;
    }
}

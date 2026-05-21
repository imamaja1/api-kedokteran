<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceDosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DosenController extends Controller
{
    public function __construct(
        private readonly ServiceDosen $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'kode_program_studi' => ['nullable', 'string'],
            'nama_dosen' => ['nullable', 'string', 'max:255'],
            'alamat_email' => ['nullable', 'string', 'max:255', 'email'],
        ]);

        return $this->service->getAllDosen(
            $this->decryptQuery($request, 'kode_program_studi'),
            $this->decryptQuery($request, 'nama_dosen'),
            $this->decryptQuery($request, 'alamat_email'),
        );
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $id = Crypt::decryptString($request->query('code'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }

        return $this->service->getOneDosen($id);
    }

    public function store(Request $request): JsonResponse
    {
        $decrypted = $this->decryptField($request, 'homebase');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'nama_dosen' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat_email' => ['required', 'string', 'max:100', 'email'],
            'field_studi' => ['required', 'string', 'max:255'],
            'alumni' => ['required', 'string', 'max:255'],
            'homebase' => ['required', 'string', 'exists:program_studi,kode_program_studi'],
            'status_dosen' => ['required', 'in:T,L'],
            'aktif' => ['required', 'in:A,N'],
            'chatid' => ['nullable', 'string', 'max:20'],
            'sandi_pengguna' => ['required', 'string', 'min:8'],
        ]);

        return $this->service->storeDosen($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $decrypted = $this->decryptField($request, 'homebase');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat_email' => ['required', 'string', 'max:100', 'email'],
            'field_studi' => ['required', 'string', 'max:255'],
            'alumni' => ['required', 'string', 'max:255'],
            'homebase' => ['required', 'string', 'exists:program_studi,kode_program_studi'],
            'status_dosen' => ['required', 'in:T,L'],
            'aktif' => ['required', 'in:A,N'],
            'chatid' => ['nullable', 'string', 'max:20'],
            'sandi_pengguna' => ['nullable', 'string', 'min:8'],
        ]);

        try {
            $id = Crypt::decryptString($validasi['code']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }

        return $this->service->updateDosen($id, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        try {
            $id = Crypt::decryptString($code);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }

        return $this->service->deleteDosen($id);
    }

    public function trash(Request $request): JsonResponse
    {
        $request->validate([
            'kode_program_studi' => ['nullable', 'string'],
            'nama_dosen' => ['nullable', 'string'],
            'alamat_email' => ['nullable', 'email'],
        ]);

        return $this->service->getDosenTrash(
            $this->decryptQuery($request, 'kode_program_studi'),
            $this->decryptQuery($request, 'nama_dosen'),
            $this->decryptQuery($request, 'alamat_email'),
        );
    }

    public function restore(string $code): JsonResponse
    {
        try {
            $id = Crypt::decryptString($code);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }

        return $this->service->restoreDosen($id);
    }

    public function forceDelete(string $code): JsonResponse
    {
        try {
            $id = Crypt::decryptString($code);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format kode tidak valid',
                'errors' => 'Invalid encryption format',
            ], 422);
        }

        return $this->service->forceDeleteDosen($id);
    }

    private function decryptQuery(Request $request, string $key): ?string
    {
        $value = $request->query($key);
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Return null to trigger validation failure via when() callback
            // Or throw exception to be caught at route level
            return null;
        }
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

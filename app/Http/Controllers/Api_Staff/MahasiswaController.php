<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Service\ServiceMahasiswa;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MahasiswaController extends Controller
{
    public function __construct(
        private readonly ServiceMahasiswa $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'code' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'angkatan' => ['nullable', 'digits:4'],
        ]);

        return $this->service->getAllMahasiswa(
            $validated['nim'] ?? null,
            isset($validated['code']) ? Crypt::decryptString($validated['code']) : null,
            isset($validated['angkatan']) ? substr($validated['angkatan'], 2, 2) : null,
        );
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $q = $validated['nama'];

        $items = Mahasiswa::where('nim', 'like', "%{$q}%")
            ->orWhere('nama_mahasiswa', 'like', "%{$q}%")
            ->select('nim', 'nama_mahasiswa')
            ->limit(5)
            ->get()->map(function ($item) {
                return [
                    'code' => Crypt::encryptString($item->nim),
                    'nim' => $item->nim,
                    'nama_mahasiswa' => $item->nama_mahasiswa,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Pencarian mahasiswa',
            'data' => $items,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $nim = Crypt::decryptString($request->query('code'));
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->getOneMahasiswa($nim);
    }

    public function store(Request $request): JsonResponse
    {
        $decrypted = $this->decryptField($request, 'program_studi_kode');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'nim' => ['required', 'string', 'max:20', 'unique:mahasiswa,nim'],
            'nik' => ['required', 'string', 'max:20'],
            'npm' => ['required', 'string', 'max:20'],
            'nomor_pendaftaran' => ['required', 'string', 'max:20'],
            'nomor_pendaftaran_ulang' => ['required', 'string', 'max:20'],
            'program_studi_kode' => ['required', 'exists:program_studi,kode_program_studi'],
            'nama_mahasiswa' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['required', 'string', 'max:50'],
            'tanggal_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:50'],
            'propinsi' => ['required', 'string', 'max:50'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'agama' => ['required', 'string', 'max:20'],
            'golongan_darah' => ['required', 'in:A,B,AB,O'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'nama_ayah' => ['required', 'string', 'max:100'],
            'agama_ayah' => ['required', 'string', 'max:20'],
            'pekerjaan_ayah' => ['required', 'string', 'max:100'],
            'nama_ibu' => ['required', 'string', 'max:100'],
            'agama_ibu' => ['required', 'string', 'max:20'],
            'pekerjaan_ibu' => ['required', 'string', 'max:100'],
            'alamat_orangtua' => ['required', 'string', 'max:255'],
            'kota_orangtua' => ['required', 'string', 'max:50'],
            'propinsi_orangtua' => ['required', 'string', 'max:50'],
            'telepon_orangtua' => ['required', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', 'in:A,N'],
            'status_pendaftaran' => ['required', 'in:B,L,T'],
        ]);

        // Handle foto upload
        if ($request->hasFile('foto')) {
            $validasi['foto'] = $request->file('foto')->store('mahasiswa');
        }

        return $this->service->storeMahasiswa($validasi);
    }

    public function update(Request $request): JsonResponse
    {
        $decrypted = $this->decryptField($request, 'program_studi_kode');
        if ($decrypted !== null) {
            return $decrypted;
        }

        $validasi = $request->validate([
            'code' => ['required', 'string'],
            'nim' => ['nullable', 'string', 'max:20'],
            'nik' => ['nullable', 'string', 'max:20'],
            'npm' => ['nullable', 'string', 'max:20'],
            'nomor_pendaftaran' => ['nullable', 'string', 'max:20'],
            'nomor_pendaftaran_ulang' => ['nullable', 'string', 'max:20'],
            'program_studi_kode' => ['nullable', 'exists:program_studi,kode_program_studi'],
            'nama_mahasiswa' => ['nullable', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:50'],
            'propinsi' => ['nullable', 'string', 'max:50'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'agama' => ['nullable', 'string', 'max:20'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'agama_ayah' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'agama_ibu' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'alamat_orangtua' => ['nullable', 'string', 'max:255'],
            'kota_orangtua' => ['nullable', 'string', 'max:50'],
            'propinsi_orangtua' => ['nullable', 'string', 'max:50'],
            'telepon_orangtua' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:A,N'],
            'status_pendaftaran' => ['nullable', 'in:B,L,T'],
        ]);

        try {
            $nim = Crypt::decryptString($validasi['code']);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        // Handle foto upload
        if ($request->hasFile('foto')) {
            $validasi['foto'] = $request->file('foto')->store('mahasiswa');
        }

        return $this->service->updateMahasiswa($nim, $validasi);
    }

    public function destroy(string $code): JsonResponse
    {
        try {
            $nim = Crypt::decryptString($code);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->deleteMahasiswa($nim);
    }

    public function trash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'code' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'angkatan' => ['nullable', 'digits:4'],
        ]);

        return $this->service->getMahasiswaTrash(
            $validated['nim'] ?? null,
            isset($validated['code']) ? Crypt::decryptString($validated['code']) : null,
            isset($validated['angkatan']) ? substr($validated['angkatan'], 2, 2) : null,
        );
    }

    public function restore(string $code): JsonResponse
    {
        try {
            $nim = Crypt::decryptString($code);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->restoreMahasiswa($nim);
    }

    public function forceDelete(string $code): JsonResponse
    {
        try {
            $nim = Crypt::decryptString($code);
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => 'Format code tidak valid',
            ], 422);
        }

        return $this->service->forceDeleteMahasiswa($nim);
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
        } catch (DecryptException) {
            return response()->json([
                'status' => false,
                'message' => "Format {$field} tidak valid",
            ], 422);
        }

        return null;
    }
}

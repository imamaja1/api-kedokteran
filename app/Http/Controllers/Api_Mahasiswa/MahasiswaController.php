<?php

namespace App\Http\Controllers\Api_Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Service\ServiceKRS;
use App\Models\TahunAkademik;
use App\Models\KRS;

class MahasiswaController extends Controller
{
    const PROVINCES = [
        'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Jambi',
        'Sumatera Selatan', 'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung',
        'Kepulauan Riau', 'Daerah Khusus Ibukota Jakarta', 'Jawa Barat',
        'Jawa Tengah', 'Daerah Istimewa Yogyakarta', 'Jawa Timur', 'Banten',
        'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
        'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur',
        'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara',
        'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua Barat', 'Papua',
    ];

    public function me(): JsonResponse
    {
        $user = Auth::guard('mahasiswa_web')->user();
        return response()->json([
            'status' => true,
            'data' => $user,
        ]);
    }

    public function profil(): JsonResponse
    {
        $user = Auth::guard('mahasiswa_web')->user();
        return response()->json(
            [
                'status' => true, 
                'data' => $user,
                'provinces' => self::PROVINCES
            ]
        );
    }

    public function semester(): JsonResponse
    {
        $nim = Auth::guard('mahasiswa_web')->user()->nim;
        $semester = KRS::select("semester")->where('nim', $nim)->get();
        return response()->json([
            'status' => true,
            'data' => [
                'semester' => $semester,
            ],
        ]);
    }

    public function profil_update(Request $request): JsonResponse
    {
        $user = Auth::guard('mahasiswa_web')->user();

        $provinces = implode(',', self::PROVINCES);

        $validated = $request->validate([
            'nisn'                   => 'sometimes|nullable|string|max:20',
            'nama_mahasiswa'         => 'sometimes|string|max:125',
            'tempat_lahir'           => 'sometimes|nullable|string|max:50',
            'tanggal_lahir'          => 'sometimes|nullable|date',
            'alamat'                 => 'sometimes|nullable|string|max:75',
            'kota'                   => 'sometimes|nullable|string|max:50',
            'propinsi'               => 'sometimes|nullable|string|in:'.$provinces,
            'telepon'                => 'sometimes|nullable|string|max:20',
            'jenis_kelamin'          => 'sometimes|nullable|in:L,P',
            'agama'                  => 'sometimes|nullable|in:Islam,Hindu,Kristen,Katolik,Budha,Konghucu',
            'golongan_darah'         => 'sometimes|nullable|in:O,A,AB,B,-',
            'kewarganegaraan'        => 'sometimes|nullable|in:WNI,WNA',
            'nama_instansi'          => 'sometimes|nullable|string|max:75',
            'email'                  => 'sometimes|nullable|email|max:75',
            'nama_ayah'              => 'sometimes|nullable|string|max:50',
            'agama_ayah'             => 'sometimes|nullable|in:Islam,Hindu,Kristen,Katolik,Budha,Konghucu',
            'pekerjaan_ayah'         => 'sometimes|nullable|in:Pegawai Negeri Sipil,Pegawai Swasta,Wiraswasta,TNI/Polri,Dosen,Guru,Petani,Rumah Tangga,Lainnya',
            'nama_ibu'               => 'sometimes|nullable|string|max:50',
            'agama_ibu'              => 'sometimes|nullable|in:Islam,Hindu,Kristen,Katolik,Budha,Konghucu',
            'pekerjaan_ibu'          => 'sometimes|nullable|in:Pegawai Negeri Sipil,Pegawai Swasta,Wiraswasta,TNI/Polri,Dosen,Guru,Petani,Rumah Tangga,Lainnya',
            'alamat_orangtua'        => 'sometimes|nullable|string|max:75',
            'kota_orangtua'          => 'sometimes|nullable|string|max:50',
            'propinsi_orangtua'      => 'sometimes|nullable|string|in:'.$provinces,
            'telepon_orangtua'       => 'sometimes|nullable|string|max:20',
            'sandi'                  => 'sometimes|string|min:6',
        ]);

        if (isset($validated['sandi'])) {
            $validated['sandi'] = Hash::make($validated['sandi']);
        }

        $user->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Data mahasiswa berhasil diupdate.',
        ]);
    }
    
}

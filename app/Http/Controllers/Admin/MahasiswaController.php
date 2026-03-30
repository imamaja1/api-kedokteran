<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class MahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Mahasiswa::withTrashed()->with('krs');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama_mahasiswa', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $mahasiswas = $query->orderBy('nim')->paginate(20)->withQueryString();

        return view('admin.mahasiswa.index', compact('mahasiswas'));
    }

    public function create()
    {
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.mahasiswa.create', compact('programStudis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nim' => 'required|string|max:11|unique:mahasiswa,nim',
            'nama_mahasiswa' => 'required|string|max:125',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:75',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:75',
            'kota' => 'nullable|string|max:50',
            'program_studi_kode' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'status' => 'required|in:A,N',
            'sandi' => 'nullable|string|min:6',
            'nama_ayah' => 'nullable|string|max:50',
            'nama_ibu' => 'nullable|string|max:50',
            'telepon_orangtua' => 'nullable|string|max:20',
            'nik' => 'required|string|max:20',
            'npm' => 'required|string|max:23',
            'nomor_pendaftaran' => 'required|string|max:13',
            'nomor_pendaftaran_ulang' => 'required|string|max:13',
        ]);

        if (! empty($data['sandi'])) {
            $data['sandi'] = Hash::make($data['sandi']);
        } else {
            unset($data['sandi']);
        }

        Mahasiswa::create($data);

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function edit($nim)
    {
        $mahasiswa = Mahasiswa::withTrashed()->findOrFail($nim);
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.mahasiswa.edit', compact('mahasiswa', 'programStudis'));
    }

    public function update(Request $request, $nim)
    {
        $mahasiswa = Mahasiswa::withTrashed()->findOrFail($nim);

        $data = $request->validate([
            'nama_mahasiswa' => 'required|string|max:125',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:75',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:75',
            'kota' => 'nullable|string|max:50',
            'program_studi_kode' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'status' => 'required|in:A,N',
            'sandi' => 'nullable|string|min:6',
            'nama_ayah' => 'nullable|string|max:50',
            'nama_ibu' => 'nullable|string|max:50',
            'telepon_orangtua' => 'nullable|string|max:20',
            'nik' => 'required|string|max:20',
            'npm' => 'required|string|max:23',
            'nomor_pendaftaran' => 'required|string|max:13',
            'nomor_pendaftaran_ulang' => 'required|string|max:13',
        ]);

        if (! empty($data['sandi'])) {
            $data['sandi'] = Hash::make($data['sandi']);
        } else {
            unset($data['sandi']);
        }

        $mahasiswa->update($data);

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy($nim)
    {
        $mahasiswa = Mahasiswa::findOrFail($nim);
        $mahasiswa->delete(); // soft delete

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil dinonaktifkan (soft delete).');
    }

    public function restore($nim)
    {
        $mahasiswa = Mahasiswa::withTrashed()->findOrFail($nim);
        $mahasiswa->restore();

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil dipulihkan.');
    }

    public function forceDelete($nim)
    {
        $mahasiswa = Mahasiswa::withTrashed()->findOrFail($nim);
        $mahasiswa->forceDelete();

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil dihapus permanen.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Mhs Kedokteran API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            $missing = collect([
                'CSRF Cookie' => $csrfConn,
                'Credential Api Siska' => $credConn,
                'Get Mhs Kedokteran API-SISKA' => $dataConn,
            ])->filter(fn ($v) => $v === null)->keys()->implode(', ');

            return back()->with('error', "Koneksi SISKA tidak ditemukan: {$missing}. Tambahkan di menu Api Connections.");
        }

        // 2. Login ulang jika cookie credential belum ada / expired
        if (! $credConn->cookie || ! $credConn->isCookieValid()) {

            // 2a. Ambil CSRF token (Sanctum SPA)
            try {
                $cookieJar = new CookieJar;

                $csrfResp = Http::withHeaders($csrfConn->extra_headers ?? [])
                    ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => true])
                    ->get($csrfConn->base_url);

                if ($csrfResp->failed()) {
                    return back()->with('error', 'Gagal mengambil CSRF cookie dari SISKA. HTTP '.$csrfResp->status());
                }

                // Baca XSRF-TOKEN dari cookie jar
                $xsrfToken = null;
                foreach ($cookieJar as $cookie) {
                    if ($cookie->getName() === 'XSRF-TOKEN') {
                        $xsrfToken = urldecode($cookie->getValue());
                        break;
                    }
                }

                if (! $xsrfToken) {
                    return back()->with('error', 'XSRF-TOKEN tidak ditemukan. Pastikan endpoint /sanctum/csrf-cookie benar.');
                }

            } catch (\Exception $e) {
                return back()->with('error', 'Koneksi ke endpoint CSRF gagal: '.$e->getMessage());
            }

            // 2b. Login dengan credentials SISKA
            try {
                $loginResp = Http::withHeaders(array_merge($credConn->extra_headers ?? [], [
                    'X-XSRF-TOKEN' => $xsrfToken,
                    'Accept' => 'application/json',
                    'Referer' => rtrim($credConn->base_url, '/'),
                ]))
                    ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => false])
                    ->asForm()
                    ->post($credConn->base_url, [
                        'username' => $credConn->username,
                        'password' => $credConn->password,
                    ]);

                // Sanctum login sukses biasanya 200 atau 204
                if ($loginResp->clientError() && ! in_array($loginResp->status(), [200, 204, 302])) {
                    return back()->with('error', 'Login SISKA gagal. HTTP '.$loginResp->status().' — periksa username/password di koneksi "Credential Api Siska".');
                }

                // Kumpulkan semua cookie dari jar menjadi header Cookie
                $cookieHeader = implode('; ', array_map(
                    fn ($c) => $c->getName().'='.$c->getValue(),
                    iterator_to_array($cookieJar)
                ));

                // Simpan cookie ke koneksi credential (expires 8 jam)
                $credConn->updateQuietly([
                    'cookie' => $cookieHeader,
                    'cookie_expires_at' => now()->addHours(8),
                ]);

            } catch (\Exception $e) {
                return back()->with('error', 'Proses login ke SISKA gagal: '.$e->getMessage());
            }
        }

        // 3. Ambil data mahasiswa dari SISKA menggunakan session cookie
        try {
            // base_url pada dataConn adalah URL endpoint lengkap
            $response = Http::withHeaders(array_merge($dataConn->extra_headers ?? [], [
                'Cookie' => $credConn->cookie,
                'Accept' => 'application/json',
                'Referer' => rtrim($credConn->base_url, '/'),
            ]))
                ->withOptions(['allow_redirects' => false])
                ->get($dataConn->base_url);

            // Session expired di sisi SISKA
            if (in_array($response->status(), [401, 419])) {
                $credConn->updateQuietly(['cookie' => null, 'cookie_expires_at' => null]);

                return back()->with('error', 'Session SISKA telah habis. Klik Sinkronisasi lagi untuk login ulang.');
            }

            if ($response->failed()) {
                return back()->with('error', 'Gagal mengambil data mahasiswa dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $mahasiswas = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($mahasiswas)) {
                return back()->with('error', 'Data mahasiswa dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data mahasiswa: '.$e->getMessage());
        }

        // 4. Update atau create mahasiswa di database lokal (upsert per NIM)
        $created = 0;
        $updated = 0;

        foreach ($mahasiswas as $m) {
            $nim = $m['nim'] ?? null;
            if (! $nim) {
                continue;
            }

            $fields = [
                'nama_mahasiswa' => $m['nama_mahasiswa'] ?? null,
                'nik' => $m['nik'] ?? '',
                'npm' => $m['npm'] ?? '',
                'nomor_pendaftaran' => $m['nomor_pendaftaran'] ?? '',
                'nomor_pendaftaran_ulang' => $m['nomor_pendaftaran_ulang'] ?? '',
                'program_studi_kode' => $m['program_studi_kode'] ?? null,
                'tempat_lahir' => $m['tempat_lahir'] ?? null,
                'tanggal_lahir' => $m['tanggal_lahir'] ?? null,
                'alamat' => $m['alamat'] ?? null,
                'kota' => $m['kota'] ?? null,
                'telepon' => $m['telepon'] ?? null,
                'email' => $m['email'] ?? null,
                'jenis_kelamin' => $m['jenis_kelamin'] ?? null,
                'agama' => $m['agama'] ?? null,
                'nama_ayah' => $m['nama_ayah'] ?? null,
                'nama_ibu' => $m['nama_ibu'] ?? null,
                'telepon_orangtua' => $m['telepon_orangtua'] ?? null,
                'status' => $m['status'] ?? 'N',
                'status_pendaftaran' => $m['status_pendaftaran'] ?? null,
                'ta_lulus' => $m['ta_lulus'] ?? null,
                'sandi' => Hash::make('password'), // Set default password, bisa diubah manual setelahnya
            ];

            $existing = Mahasiswa::withTrashed()->where('nim', $nim)->first();

            if ($existing) {
                $existing->update($fields);
                $updated++;
            } else {
                Mahasiswa::create(array_merge(['nim' => $nim], $fields));
                $created++;
            }
        }

        return redirect()->route('admin.mahasiswa.index')
            ->with('success', "Sinkronisasi SISKA selesai: {$created} mahasiswa ditambahkan, {$updated} diperbarui.");
    }
}

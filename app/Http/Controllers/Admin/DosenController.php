<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class DosenController extends Controller
{
    public function index(Request $request)
    {
        $query = Dosen::with('programStudi');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('alamat_email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('aktif')) {
            $query->where('aktif', $status);
        }

        $dosens = $query->paginate(20)->withQueryString();

        return view('admin.dosen.index', compact('dosens'));
    }

    public function create()
    {
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.dosen.create', compact('programStudis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_dosen' => 'required|string|max:255',
            'nik' => 'nullable|string|max:255',
            'no_telp' => 'nullable|string|max:20',
            'alamat_email' => 'nullable|email|max:100',
            'field_studi' => 'nullable|string|max:255',
            'alumni' => 'nullable|string|max:255',
            'homebase' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'status_dosen' => 'required|in:T,L',
            'aktif' => 'required|in:A,N',
            'chatid' => 'nullable|string|max:20',
            'sandi_pengguna' => 'nullable|string|min:6',
        ]);

        if (! empty($data['sandi_pengguna'])) {
            $data['sandi_pengguna'] = Hash::make($data['sandi_pengguna']);
        } else {
            unset($data['sandi_pengguna']);
        }

        $data['chatid'] = $data['chatid'] ?? '';
        $data['status_login'] = 'N';

        Dosen::create($data);

        return redirect()->route('admin.dosen.index')
            ->with('success', 'Dosen berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen)
    {
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.dosen.edit', compact('dosen', 'programStudis'));
    }

    public function update(Request $request, Dosen $dosen)
    {
        $data = $request->validate([
            'nama_dosen' => 'required|string|max:255',
            'nik' => 'nullable|string|max:255',
            'no_telp' => 'nullable|string|max:20',
            'alamat_email' => 'nullable|email|max:100',
            'field_studi' => 'nullable|string|max:255',
            'alumni' => 'nullable|string|max:255',
            'homebase' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'status_dosen' => 'required|in:T,L',
            'aktif' => 'required|in:A,N',
            'chatid' => 'nullable|string|max:20',
            'sandi_pengguna' => 'nullable|string|min:6',
        ]);

        if (! empty($data['sandi_pengguna'])) {
            $data['sandi_pengguna'] = Hash::make($data['sandi_pengguna']);
        } else {
            unset($data['sandi_pengguna']);
        }

        $dosen->update($data);

        return redirect()->route('admin.dosen.index')
            ->with('success', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen)
    {
        $dosen->delete();

        return redirect()->route('admin.dosen.index')
            ->with('success', 'Dosen berhasil dihapus.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Dosen Kedokteran API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            return redirect()->route('admin.dosen.index')
                ->with('error', 'Koneksi API SISKA belum lengkap. Pastikan koneksi "CSRF Cookie", "Credential Api Siska", dan "Get Dosen Kedokteran API-SISKA" sudah dibuat dan diaktifkan.');
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
                return back()->with('error', 'Gagal mengambil data dosen dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $dosens = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($dosens)) {
                return back()->with('error', 'Data dosen dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data dosen: '.$e->getMessage());
        }

        // 4. Update atau create dosen di database lokal (upsert per NIK)
        $created = 0;
        $updated = 0;

        foreach ($dosens as $d) {
            $nik = $d['nik'] ?? null;
            if (! $nik) {
                continue;
            }

            $fields = [
                'nama_dosen' => $d['nama_dosen'] ?? null,
                'nik' => $nik,
                'no_telp' => $d['no_telp'] ?? null,
                'alamat_email' => $d['alamat_email'] ?? null,
                'field_studi' => $d['field_studi'] ?? null,
                'alumni' => $d['alumni'] ?? null,
                'homebase' => $d['homebase'] ?? null,
                'status_dosen' => $d['status_dosen'] ?? 'T',
                'aktif' => $d['aktif'] ?? 'A',
                'chatid' => $d['chatid'] ?? '',
                'sandi_pengguna' => Hash::make('password'), // Set default password, bisa diubah manual setelahnya
            ];

            $existing = Dosen::where('nik', $nik)->first();

            if ($existing) {
                $existing->update($fields);
                $updated++;
            } else {
                Dosen::create($fields);
                $created++;
            }
        }

        return redirect()->route('admin.dosen.index')
            ->with('success', "Sinkronisasi SISKA selesai: {$created} dosen ditambahkan, {$updated} diperbarui.");

    }
}

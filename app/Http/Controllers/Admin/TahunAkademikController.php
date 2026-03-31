<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\TahunAkademik;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TahunAkademikController extends Controller
{
    public function index(Request $request)
    {
        $query = TahunAkademik::orderByDesc('kode_tahun_akademik');

        if ($search = $request->query('search')) {
            $query->where('tahun_akademik', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        $tahunAkademiks = $query->paginate(15)->withQueryString();

        return view('admin.tahun-akademik.index', compact('tahunAkademiks'));
    }

    public function create()
    {
        return view('admin.tahun-akademik.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tahun_akademik'   => 'required|string|max:9|regex:/^\d{4}\/\d{4}$/',
            'semester'         => 'required|in:1,2',
            'tanggal_mulai'    => 'required|date',
            'tanggal_berakhir' => 'required|date|after:tanggal_mulai',
            'status'           => 'required|in:A,N',
            'status_kpat'      => 'nullable|in:A,N',
        ]);

        // Jika status baru = Aktif, nonaktifkan yang lain pada semester yang sama
        if ($data['status'] === 'A') {
            TahunAkademik::where('semester', $data['semester'])
                ->where('status', 'A')
                ->update(['status' => 'N']);
        }

        TahunAkademik::create($data);

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil ditambahkan.');
    }

    public function edit(TahunAkademik $tahunAkademik)
    {
        return view('admin.tahun-akademik.edit', compact('tahunAkademik'));
    }

    public function update(Request $request, TahunAkademik $tahunAkademik)
    {
        $data = $request->validate([
            'tahun_akademik'   => 'required|string|max:9|regex:/^\d{4}\/\d{4}$/',
            'semester'         => 'required|in:1,2',
            'tanggal_mulai'    => 'required|date',
            'tanggal_berakhir' => 'required|date|after:tanggal_mulai',
            'status'           => 'required|in:A,N',
            'status_kpat'      => 'nullable|in:A,N',
        ]);

        // Jika di-set Aktif, nonaktifkan yang lain (selain record ini)
        if ($data['status'] === 'A') {
            TahunAkademik::where('semester', $data['semester'])
                ->where('status', 'A')
                ->where('kode_tahun_akademik', '!=', $tahunAkademik->kode_tahun_akademik)
                ->update(['status' => 'N']);
        }

        $tahunAkademik->update($data);

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil diperbarui.');
    }

    public function destroy(TahunAkademik $tahunAkademik)
    {
        $tahunAkademik->delete();

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil dihapus.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Tahun Akademik API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            return redirect()->route('admin.tahun-akademik.index')
                ->with('error', 'Koneksi API SISKA belum lengkap. Pastikan koneksi "CSRF Cookie", "Credential Api Siska", dan "Get Tahun Akademik API-SISKA" sudah dibuat dan diaktifkan.');
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
                    'Accept'       => 'application/json',
                    'Referer'      => rtrim($credConn->base_url, '/'),
                ]))
                    ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => false])
                    ->asForm()
                    ->post($credConn->base_url, [
                        'username' => $credConn->username,
                        'password' => $credConn->password,
                    ]);

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
                    'cookie'            => $cookieHeader,
                    'cookie_expires_at' => now()->addHours(8),
                ]);

            } catch (\Exception $e) {
                return back()->with('error', 'Proses login ke SISKA gagal: '.$e->getMessage());
            }
        }

        // 3. Ambil data tahun akademik dari SISKA menggunakan session cookie
        try {
            $response = Http::withHeaders(array_merge($dataConn->extra_headers ?? [], [
                'Cookie'  => $credConn->cookie,
                'Accept'  => 'application/json',
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
                return back()->with('error', 'Gagal mengambil data tahun akademik dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $items = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($items)) {
                return back()->with('error', 'Data tahun akademik dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data tahun akademik: '.$e->getMessage());
        }

        // 4. Update atau create tahun akademik di database lokal
        $created = 0;
        $updated = 0;
        // dd($items);
        foreach ($items as $ta) {
            $kode = $ta['kode_tahun_akademik'] ?? null;
            if (! $kode) {
                continue;
            }

            $fields = [
                'kode_tahun_akademik' => $ta['kode_tahun_akademik'] ?? null,
                'tahun_akademik'   => $ta['tahun_akademik'] ?? null,
                'semester'         => in_array($ta['semester'] ?? '', ['1', '2']) ? $ta['semester'] : '2',
                'tanggal_mulai'    => ($ts = strtotime($ta['tanggal_mulai'] ?? '')) !== false && $ts > 0 ? date('Y-m-d', $ts) : date('Y-m-d'),
                'tanggal_berakhir' => ($ts = strtotime($ta['tanggal_berakhir'] ?? '')) !== false && $ts > 0 ? date('Y-m-d', $ts) : date('Y-m-d'),
                'status'           => in_array($ta['status'] ?? '', ['A', 'N']) ? $ta['status'] : 'N',
                'status_kpat'      => in_array($ta['status_kpat'] ?? '', ['A', 'N']) ? $ta['status_kpat'] : null,
            ];

            $existing = TahunAkademik::find($kode);

            if ($existing) {
                $existing->update($fields);
                $updated++;
            } else {
                TahunAkademik::create(array_merge($fields, ['kode_tahun_akademik' => $kode]));
                $created++;
            }
        }

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', "Sinkronisasi SISKA selesai: {$created} tahun akademik ditambahkan, {$updated} diperbarui.");
    }
}

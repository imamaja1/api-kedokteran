<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Matakuliah;
use App\Models\ProgramStudi;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MatakuliahController extends Controller
{
    public function index(Request $request)
    {
        $query = Matakuliah::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_matakuliah', 'like', "%{$search}%")
                    ->orWhere('kode_matakuliah', 'like', "%{$search}%");
            });
        }

        if ($block = $request->query('block')) {
            $query->where('block', $block);
        }

        $matakuliahs = $query->orderBy('nama_matakuliah')->paginate(20)->withQueryString();

        return view('admin.matakuliah.index', compact('matakuliahs'));
    }

    public function create()
    {
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.matakuliah.create', compact('programStudis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_matakuliah'    => 'required|string|max:10',
            'nama_matakuliah'    => 'required|string|max:75',
            'jenis'              => 'nullable|integer',
            'sks_teori'          => 'required|integer|min:0|max:255',
            'sks_praktik'        => 'required|integer|min:0|max:255',
            'kode_kompetensi'    => 'nullable|integer',
            'kode_program_studi' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'block'              => 'required|in:0,1',
        ]);

        Matakuliah::create($data);

        return redirect()->route('admin.matakuliah.index')
            ->with('success', 'Matakuliah berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $matakuliah = Matakuliah::findOrFail($id);
        $programStudis = ProgramStudi::orderBy('nama_program_studi')->get();

        return view('admin.matakuliah.edit', compact('matakuliah', 'programStudis'));
    }

    public function update(Request $request, $id)
    {
        $matakuliah = Matakuliah::findOrFail($id);

        $data = $request->validate([
            'kode_matakuliah'    => 'required|string|max:10',
            'nama_matakuliah'    => 'required|string|max:75',
            'jenis'              => 'nullable|integer',
            'sks_teori'          => 'required|integer|min:0|max:255',
            'sks_praktik'        => 'required|integer|min:0|max:255',
            'kode_kompetensi'    => 'nullable|integer',
            'kode_program_studi' => 'nullable|integer|exists:program_studi,kode_program_studi',
            'block'              => 'required|in:0,1',
        ]);

        $matakuliah->update($data);

        return redirect()->route('admin.matakuliah.index')
            ->with('success', 'Matakuliah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Matakuliah::findOrFail($id)->delete();

        return redirect()->route('admin.matakuliah.index')
            ->with('success', 'Matakuliah berhasil dihapus.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Matakuliah API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            $missing = collect([
                'CSRF Cookie'             => $csrfConn,
                'Credential Api Siska'    => $credConn,
                'Get Matakuliah API-SISKA'=> $dataConn,
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

                $cookieHeader = implode('; ', array_map(
                    fn ($c) => $c->getName().'='.$c->getValue(),
                    iterator_to_array($cookieJar)
                ));

                $credConn->updateQuietly([
                    'cookie'            => $cookieHeader,
                    'cookie_expires_at' => now()->addHours(8),
                ]);

            } catch (\Exception $e) {
                return back()->with('error', 'Proses login ke SISKA gagal: '.$e->getMessage());
            }
        }

        // 3. Ambil data matakuliah dari SISKA
        try {
            $response = Http::withHeaders(array_merge($dataConn->extra_headers ?? [], [
                'Cookie'  => $credConn->cookie,
                'Accept'  => 'application/json',
                'Referer' => rtrim($credConn->base_url, '/'),
            ]))
                ->withOptions(['allow_redirects' => false])
                ->get($dataConn->base_url);

            if (in_array($response->status(), [401, 419])) {
                $credConn->updateQuietly(['cookie' => null, 'cookie_expires_at' => null]);

                return back()->with('error', 'Session SISKA telah habis. Klik Sinkronisasi lagi untuk login ulang.');
            }

            if ($response->failed()) {
                return back()->with('error', 'Gagal mengambil data matakuliah dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $items = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($items)) {
                return back()->with('error', 'Data matakuliah dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data matakuliah: '.$e->getMessage());
        }

        // 4. Upsert matakuliah — gunakan id_matakuliah SISKA jika tersedia agar FK krs_detail cocok
        $created = 0;
        $updated = 0;
        foreach ($items as $mk) {
            $kodeMk    = $mk['kode_matakuliah'] ?? null;
            $siskaId   = $mk['id'] ?? null;  // SISKA internal ID (mungkin ada)
            if (! $kodeMk) {
                continue;
            }

            $fields = [
                'kode_matakuliah'    => $kodeMk,
                'nama_matakuliah'    => $mk['nama_matakuliah'] ?? null,
                'jenis'              => $mk['jenis'] ?? null,
                'sks_teori'          => $mk['sks_teori'] ?? 0,
                'sks_praktik'        => ($mk['sks_praktek'] ?? 0) + ($mk['sks_praktikum'] ?? 0), // beberapa versi SISKA typo "praktek"
                'kode_kompetensi'    => $mk['kode_kompetensi'] ?? null,
                'kode_program_studi' => $mk['kode_program_studi'] ?? null,
                'block'              => in_array($mk['block'] ?? '', ['0', '1']) ? $mk['block'] : '0',
            ];

            // Cari existing: duluan cek by SISKA id, fallback ke kode_matakuliah
            $existing = $siskaId
                ? (Matakuliah::find($siskaId) ?? Matakuliah::where('kode_matakuliah', $kodeMk)->first())
                : Matakuliah::where('kode_matakuliah', $kodeMk)->first();

            if ($existing) {
                $existing->update($fields);
                $updated++;
            } else {
                // Simpan dengan id_matakuliah SISKA agar FK dari krs_detail cocok
                $new = new Matakuliah();
                if ($siskaId) {
                    $new->id_matakuliah = $siskaId;
                }
                $new->fill($fields);
                $new->save();
                $created++;
            }
        }

        return redirect()->route('admin.matakuliah.index')
            ->with('success', "Sinkronisasi SISKA selesai: {$created} matakuliah ditambahkan, {$updated} diperbarui.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Kurikulum;
use App\Models\KurikulumAngkatan;
use App\Models\NamaKurikulum;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KurikulumController extends Controller
{
    public function index(Request $request)
    {
        $query = NamaKurikulum::withCount(['kurikulum', 'kurikulumAngkatan']);

        if ($search = $request->query('search')) {
            $query->where('nama_kurikulum', 'like', "%{$search}%");
        }

        $namaKurikulumList = $query->orderByDesc('kode_nama_kurikulum')->paginate(15)->withQueryString();

        return view('admin.kurikulum.index', compact('namaKurikulumList'));
    }

    public function show(Request $request, int $kode)
    {
        $namaKurikulum = NamaKurikulum::findOrFail($kode);

        $kurikulumQuery = Kurikulum::with('matakuliah')
            ->where('kode_nama_kurikulum', $kode);

        if ($semester = $request->query('semester')) {
            $kurikulumQuery->where('semester', $semester);
        }

        $kurikulumList = $kurikulumQuery->orderBy('semester')->paginate(25)->withQueryString();
        $angkatanList  = KurikulumAngkatan::where('kode_nama_kurikulum', $kode)->get();

        // Hitung total SKS per semester (semua semester, tanpa filter/paginate)
        $sksBySemester = Kurikulum::with('matakuliah')
            ->where('kode_nama_kurikulum', $kode)
            ->get()
            ->groupBy('semester')
            ->map(function ($items) {
                return [
                    'jumlah_mk'    => $items->count(),
                    'sks_teori'    => $items->sum(fn ($k) => $k->matakuliah->sks_teori ?? 0),
                    'sks_praktik'  => $items->sum(fn ($k) => $k->matakuliah->sks_praktik ?? 0),
                    'total_sks'    => $items->sum(fn ($k) => ($k->matakuliah->sks_teori ?? 0) + ($k->matakuliah->sks_praktik ?? 0)),
                ];
            })
            ->sortKeys();

        return view('admin.kurikulum.show', compact('namaKurikulum', 'kurikulumList', 'angkatanList', 'sksBySemester'));
    }

    public function syncWithSiska()
    {
        // 1. Ambil koneksi
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Kurikulum')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            $missing = collect([
                'CSRF Cookie'          => $csrfConn,
                'Credential Api Siska' => $credConn,
                'Get Kurikulum'        => $dataConn,
            ])->filter(fn ($v) => $v === null)->keys()->implode(', ');

            return back()->with('error', "Koneksi SISKA tidak ditemukan: {$missing}. Tambahkan di menu Api Connections.");
        }
        
        // 2. Login ulang jika cookie tidak valid
        if (! $credConn->cookie || ! $credConn->isCookieValid()) {

            // 2a. CSRF token
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

            // 2b. Login
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

        // 3. Ambil data kurikulum dari SISKA
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
                return back()->with('error', 'Gagal mengambil data kurikulum dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $data    = $payload['data'] ?? [];

            if (empty($data)) {
                return back()->with('error', 'Data kurikulum dari SISKA kosong atau format response tidak dikenali.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data kurikulum: '.$e->getMessage());
        }

        // 4. Upsert
        $namaCreated     = 0;
        $namaUpdated     = 0;
        $kurCreated      = 0;
        $kurUpdated      = 0;
        $angkatanCreated = 0;
        $angkatanUpdated = 0;

        // 4a. nama_kurikulum
        foreach ($data['nama_kurikulum'] ?? [] as $item) {
            $kode = $item['kode_nama_kurikulum'] ?? null;
            if (! $kode) {
                continue;
            }

            $existing = NamaKurikulum::find($kode);

            $fields = [
                'nama_kurikulum'   => $item['nama_kurikulum'] ?? null,
                'kode_program_studi' => $item['kode_program_studi'] ?? null,
                'angkatan1'        => $item['angkatan1'] ?? null,
                'ekstensi1'        => $item['ekstensi1'] ?? null,
                'paket1'           => $item['paket1'] ?? null,
            ];

            if (! $existing) {
                $model = new NamaKurikulum();
                $model->kode_nama_kurikulum = $kode;
                $model->fill($fields);
                $model->save();
                $namaCreated++;
            } else {
                $existing->fill($fields)->save();
                $namaUpdated++;
            }
        }

        // 4b. kurikulum
        foreach ($data['kurikulum'] ?? [] as $item) {
            $kode = $item['kode_kurikulum'] ?? null;
            if (! $kode) {
                continue;
            }

            $fields = [
                'kode_nama_kurikulum' => $item['kode_nama_kurikulum'] ?? null,
                'kode_matakuliah'     => $item['kode_matakuliah'] ?? '',
                'semester'            => $item['semester'] ?? null,
                'id_matakuliah'       => $item['id_matakuliah'] ?? null,
            ];

            $existing = Kurikulum::find($kode);

            if (! $existing) {
                $model = new Kurikulum();
                $model->kode_kurikulum = $kode;
                $model->fill($fields);
                $model->save();
                $kurCreated++;
            } else {
                $existing->fill($fields)->save();
                $kurUpdated++;
            }
        }

        // 4c. kurikulum_angkatan
        foreach ($data['kurikulum_angkatan'] ?? [] as $item) {
            $kode = $item['kode_kurikulum_angkatan'] ?? null;
            if (! $kode) {
                continue;
            }

            $fields = [
                'angkatan'           => $item['angkatan'] ?? null,
                'ekstensi'           => $item['ekstensi'] ?? 'N',
                'paket'              => $item['paket'] ?? 'N',
                'semester_stup_grade' => $item['semester_stup_grade'] ?? null,
                'kode_nama_kurikulum' => $item['kode_nama_kurikulum'] ?? null,
            ];

            $existing = KurikulumAngkatan::find($kode);

            if (! $existing) {
                $model = new KurikulumAngkatan();
                $model->kode_kurikulum_angkatan = $kode;
                $model->fill($fields);
                $model->save();
                $angkatanCreated++;
            } else {
                $existing->fill($fields)->save();
                $angkatanUpdated++;
            }
        }

        $msg = "Sinkronisasi selesai. "
            . "Nama Kurikulum: +{$namaCreated} baru, {$namaUpdated} diperbarui. "
            . "Kurikulum: +{$kurCreated} baru, {$kurUpdated} diperbarui. "
            . "Angkatan: +{$angkatanCreated} baru, {$angkatanUpdated} diperbarui.";

        return back()->with('success', $msg);
    }
}

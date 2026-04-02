<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\KhsDetail;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\TahunAkademik;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KrsKhsController extends Controller
{
    public function index(Request $request)
    {
        $tahunAkademiks = TahunAkademik::orderByDesc('kode_tahun_akademik')->get();

        $query = Krs::with(['mahasiswa:nim,nama_mahasiswa', 'tahunAkademik', 'detail.matakuliah', 'detail.khsDetail'])
            ->orderByDesc('kode_krs');

        if ($nim = $request->query('nim')) {
            $query->where('nim', 'like', "%{$nim}%");
        }

        if ($ta = $request->query('kode_tahun_akademik')) {
            $query->where('kode_tahun_akademik', $ta);
        }

        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        $krsList = $query->paginate(15)->withQueryString();

        return view('admin.krs-khs.index', compact('krsList', 'tahunAkademiks'));
    }

    public function show($kode_krs)
    {
        $krs = Krs::with(['mahasiswa', 'tahunAkademik', 'detail'])
            ->findOrFail($kode_krs);

        return view('admin.krs-khs.show', compact('krs'));
    }

    public function storeDetail(Request $request, $kode_krs)
    {
        $krs = Krs::findOrFail($kode_krs);

        $validated = $request->validate([
            'id_matakuliah'  => 'required|integer|exists:matakuliah,id_matakuliah',
            'kode_matakuliah'=> 'required|string|max:10',
            'status'         => 'nullable|in:B,U,K',
        ]);

        $validated['kode_krs'] = $krs->kode_krs;

        KrsDetail::create($validated);

        return redirect()->route('admin.krs-khs.show', $kode_krs)
            ->with('success', 'Matakuliah berhasil ditambahkan ke KRS.');
    }

    public function destroyDetail(Request $request, $kode_krs, $kode_krs_detail)
    {
        KrsDetail::where('kode_krs', $kode_krs)
            ->where('kode_krs_detail', $kode_krs_detail)
            ->firstOrFail()
            ->delete();

        return redirect()->route('admin.krs-khs.show', $kode_krs)
            ->with('success', 'Matakuliah berhasil dihapus dari KRS.');
    }

    public function updateNilai(Request $request, $kode_krs_detail)
    {
        $validated = $request->validate([
            'nilai_harian' => 'nullable|numeric|min:0|max:100',
            'nilai_uts'    => 'nullable|numeric|min:0|max:100',
            'nilai_uas'    => 'nullable|numeric|min:0|max:100',
            'nilai_akhir'  => 'nullable|numeric|min:0|max:100',
            'tidak_berhak' => 'nullable|in:A,N',
        ]);

        KhsDetail::updateOrCreate(
            ['kode_krs_detail' => $kode_krs_detail],
            $validated
        );

        $kodeKrs = KrsDetail::findOrFail($kode_krs_detail)->kode_krs;

        return redirect()->route('admin.krs-khs.show', $kodeKrs)
            ->with('success', 'Nilai KHS berhasil disimpan.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get KRS API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            $missing = collect([
                'CSRF Cookie'          => $csrfConn,
                'Credential Api Siska' => $credConn,
                'Get KRS API-SISKA'    => $dataConn,
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

        // 3. Ambil data KRS dari SISKA
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
                return back()->with('error', 'Gagal mengambil data KRS dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $items = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($items)) {
                return back()->with('error', 'Data KRS dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data KRS: '.$e->getMessage());
        }

        // 4. Upsert KRS, KRS Detail, dan KHS Detail
        $krsCreated   = 0;
        $krsUpdated   = 0;
        $detailCreated = 0;
        $detailUpdated = 0;
        $detailSkipped = 0;

        // Pre-scan semua id_matakuliah yang dibutuhkan krs_detail,
        // buat placeholder matakuliah jika belum ada lokal (FK constraint)
        $neededIds = collect($items)
            ->flatMap(fn ($item) => collect($item['krs_detail'] ?? [])->pluck('id_matakuliah')->filter())
            ->unique();

        $existingMkIds = Matakuliah::whereIn('id_matakuliah', $neededIds)
            ->pluck('id_matakuliah')
            ->flip()
            ->all();

        foreach ($neededIds as $mkId) {
            if (! isset($existingMkIds[$mkId])) {
                $ph = new Matakuliah();
                $ph->id_matakuliah  = $mkId;
                $ph->kode_matakuliah = substr('MK'.$mkId, 0, 10);
                $ph->nama_matakuliah = 'Matakuliah #'.$mkId.' (belum disync)';
                $ph->sks_teori      = 0;
                $ph->sks_praktik    = 0;
                $ph->block          = '0';
                $ph->save();
            }
        }
        // dd($items); // --- IGNORE ---
        foreach ($items as $item) {
            $nim = $item['nim'] ?? null;
            if (! $nim) {
                continue;
            }

            // Upsert KRS — setiap item bisa punya array 'krs'
            foreach ($item['krs'] ?? [] as $krsData) {
                $kodeKrs = $krsData['kode_krs'] ?? null;
                if (! $kodeKrs) {
                    continue;
                }

                $krsFields = [
                    'nim'                 => $krsData['nim'] ?? $nim,
                    'kode_tahun_akademik' => $krsData['kode_tahun_akademik'] ?? null,
                    'semester'            => $krsData['semester'] ?? null,
                ];

                $existingKrs = Krs::find($kodeKrs);
                if ($existingKrs) {
                    $existingKrs->update($krsFields);
                    $krsUpdated++;
                } else {
                    // kode_krs bukan di $fillable, set langsung
                    $newKrs = new Krs();
                    $newKrs->kode_krs = $kodeKrs;
                    $newKrs->fill($krsFields);
                    $newKrs->save();
                    $krsCreated++;
                }
            }

            // Upsert KRS Detail — flat array, match pakai kode_krs + id_matakuliah
            foreach ($item['krs_detail'] ?? [] as $detail) {
                $kodeKrs      = $detail['kode_krs'] ?? null;
                $idMatakuliah = $detail['id_matakuliah'] ?? null;
                $kode_krs_detail = $detail['k_krs_detail'] ?? null;
                if (! $kodeKrs || ! $idMatakuliah) {
                    $detailSkipped++;
                    continue;
                }

                $detailFields = [
                    'kode_krs_detail'   => $kode_krs_detail, // dari SISKA, bukan auto-increment lokal
                    'kode_krs'        => $kodeKrs,
                    'id_matakuliah'   => $idMatakuliah,
                    'status'          => in_array($detail['status'] ?? '', ['B', 'U', 'K'])
                                            ? $detail['status']
                                            : null,
                ];

                try {
                    $existingDetail = KrsDetail::where('kode_krs', $kodeKrs)
                        ->where('id_matakuliah', $idMatakuliah)
                        ->first();

                    if ($existingDetail) {
                        $existingDetail->update($detailFields);
                        $kodeKrsDetail = $existingDetail->kode_krs_detail;
                        $detailUpdated++;
                    } else {
                        $newDetail     = KrsDetail::create($detailFields);
                        $kodeKrsDetail = $newDetail->kode_krs_detail;
                        $detailCreated++;
                    }

                    // Upsert KHS Detail — kode_khs_detail dari SISKA (bukan fillable, set langsung)
                    $kodeKhsDetail = $detail['kode_khs_detail'] ?? null;
                    if ($kodeKhsDetail) {
                        $existingKhs = KhsDetail::find($kodeKhsDetail);
                        if ($existingKhs) {
                            $existingKhs->kode_krs_detail = $kodeKrsDetail;
                            $existingKhs->nilai_akhir     = $detail['nilai_akhir'] ?? null;
                            $existingKhs->save();
                        } else {
                            $newKhs = new KhsDetail();
                            $newKhs->kode_khs_detail = $kodeKhsDetail;
                            $newKhs->kode_krs_detail = $kodeKrsDetail;
                            $newKhs->nilai_akhir     = $detail['nilai_akhir'] ?? null;
                            $newKhs->save();
                        }
                    }
                } catch (\Exception $e) {
                    $detailSkipped++;
                }
            }
        }

        $msg = "Sinkronisasi SISKA selesai: {$krsCreated} KRS ditambahkan, {$krsUpdated} diperbarui, {$detailCreated} detail ditambahkan, {$detailUpdated} detail diperbarui.";
        if ($detailSkipped > 0) {
            $msg .= " {$detailSkipped} detail dilewati (matakuliah belum tersinkronisasi — sync Matakuliah terlebih dahulu).";
        }

        return redirect()->route('admin.krs-khs.index')->with('success', $msg);
    }
}

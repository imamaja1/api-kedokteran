<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KrsDetail;
use App\Models\Mengajar;
use App\Models\NamaKelas;
use App\Models\TahunAkademik;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::with(['namaKelas', 'matakuliah']);

        if ($namaKelasId = $request->query('nama_kelas_id')) {
            $query->where('nama_kelas_id', $namaKelasId);
        }

        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        if ($ta = $request->query('kode_tahun_akademik')) {
            $query->where('kode_tahun_akademik', $ta);
        }

        if ($search = $request->query('search')) {
            $query->whereHas('matakuliah', function ($q) use ($search) {
                $q->where('nama_matakuliah', 'like', "%{$search}%")
                  ->orWhere('kode_matakuliah', 'like', "%{$search}%");
            });
        }

        $kelasList    = $query->orderBy('nama_kelas_id')->orderBy('semester')->paginate(20)->withQueryString();
        $namaKelasList = NamaKelas::orderBy('nama_kelas')->get();
        $tahunAkademiks = TahunAkademik::orderByDesc('kode_tahun_akademik')->get();

        return view('admin.kelas.index', compact('kelasList', 'namaKelasList', 'tahunAkademiks'));
    }

    public function show(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        // ── Mahasiswa table (paginated + searchable) ──────────────────────
        $mhsQuery = KelasMahasiswa::with('krsDetail.krs.mahasiswa')
            ->where('kelas_id', $kelas->kelas_id);

        if ($mhsSearch = $request->query('mhs_search')) {
            $mhsQuery->whereHas('krsDetail.krs.mahasiswa', function ($q) use ($mhsSearch) {
                $q->where('nim', 'like', "%{$mhsSearch}%")
                  ->orWhere('nama_mahasiswa', 'like', "%{$mhsSearch}%");
            });
        }

        $kelasMahasiswas = $mhsQuery->paginate(15, ['*'], 'mhs_page')->withQueryString();

        // ── Dosen table (paginated + searchable) ───────────────────────────
        $dosenQuery = Mengajar::with('dosen')
            ->where('kelas_id', $kelas->kelas_id);

        if ($dosenSearch = $request->query('dosen_search')) {
            $dosenQuery->whereHas('dosen', function ($q) use ($dosenSearch) {
                $q->where('nama_dosen', 'like', "%{$dosenSearch}%")
                  ->orWhere('alamat_email', 'like', "%{$dosenSearch}%");
            });
        }

        $mengajars = $dosenQuery->paginate(15, ['*'], 'dosen_page')->withQueryString();

        // ── Dropdowns for modals ──────────────────────────────────────────
        $assignedKrsDetailIds = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
            ->pluck('kode_krs_detail')->toArray();

        $availableKrsDetails = KrsDetail::with('krs.mahasiswa')
            ->where('id_matakuliah', $kelas->id_matakuliah)
            ->whereNotIn('kode_krs_detail', $assignedKrsDetailIds)
            ->get();

        $assignedDosenIds = Mengajar::where('kelas_id', $kelas->kelas_id)
            ->pluck('kode_dosen')->toArray();

        $availableDosens = Dosen::where('aktif', 'A')
            ->whereNotIn('kode_dosen', $assignedDosenIds)
            ->orderBy('nama_dosen')
            ->get();

        return view('admin.kelas.show', compact(
            'kelas', 'kelasMahasiswas', 'mengajars',
            'availableKrsDetails', 'availableDosens'
        ));
    }

    public function storeMahasiswa(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'kode_krs_detail' => 'required|integer|exists:krs_detail,kode_krs_detail',
        ]);

        $kode = $request->integer('kode_krs_detail');

        // Prevent duplicate
        $exists = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
            ->where('kode_krs_detail', $kode)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Mahasiswa tersebut sudah ada di kelas ini.');
        }

        KelasMahasiswa::create([
            'kelas_id'        => $kelas->kelas_id,
            'kode_krs_detail' => $kode,
        ]);

        return back()->with('success', 'Mahasiswa berhasil ditambahkan ke kelas.');
    }

    public function destroyMahasiswa(int $id, int $kmId)
    {
        $km = KelasMahasiswa::where('kelas_id', $id)->where('kelas_mahasiswa_id', $kmId)->firstOrFail();
        $km->delete();

        return back()->with('success', 'Mahasiswa berhasil dihapus dari kelas.');
    }

    public function storeDosen(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'kode_dosen' => 'required|integer|exists:dosen,kode_dosen',
        ]);

        $kodeDosen = $request->integer('kode_dosen');

        // Prevent duplicate
        $exists = Mengajar::where('kelas_id', $kelas->kelas_id)
            ->where('kode_dosen', $kodeDosen)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Dosen tersebut sudah mengajar di kelas ini.');
        }

        Mengajar::create([
            'kelas_id'   => $kelas->kelas_id,
            'kode_dosen' => $kodeDosen,
        ]);

        return back()->with('success', 'Dosen berhasil ditambahkan ke kelas.');
    }

    public function destroyDosen(int $id, int $mengajarId)
    {
        $m = Mengajar::where('kelas_id', $id)->where('mengajar_id', $mengajarId)->firstOrFail();
        $m->delete();

        return back()->with('success', 'Dosen berhasil dihapus dari kelas.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Kelas API-SISKA')->first();

        if (! $csrfConn || ! $credConn || ! $dataConn) {
            $missing = collect([
                'CSRF Cookie'          => $csrfConn,
                'Credential Api Siska' => $credConn,
                'Get Kelas API-SISKA'  => $dataConn,
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

        // 3. Ambil data kelas dari SISKA
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
                return back()->with('error', 'Gagal mengambil data kelas dari SISKA. HTTP '.$response->status());
            }

            $payload = $response->json();
            $items = isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (empty($items)) {
                return back()->with('error', 'Data kelas dari SISKA kosong atau format response tidak dikenali.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error saat mengambil data kelas: '.$e->getMessage());
        }

        // 4. Upsert nama_kelas, kelas, mengajar, kelas_mahasiswa
        $namaKelasCreated  = 0;
        $kelasCreated      = 0;
        $mengajarCreated   = 0;
        $mhsCreated        = 0;
        $skipped           = 0;
    
        foreach ($items as $item) {
            // try {
                // 4a. Upsert nama_kelas — nested object { "nama_kelas": "A" }, cari/buat by nama
                $namaKelasStr = $item['nama_kelas_kedokteran']['nama_kelas'] ?? null;
                $idnamakelas = $item['nama_kelas_kedokteran']['id'] ?? null; // prefer ID dari top-level jika ada
                $namaKelasId  = null;
                if ($namaKelasStr) {
                    $namaKelasModel = NamaKelas::firstOrCreate([
                        'nama_kelas' => $namaKelasStr,
                        'nama_kelas_id' => $idnamakelas, // optional, hanya untuk memastikan konsistensi jika ID tersedia
                        ]);
                    $namaKelasId    = $namaKelasModel->nama_kelas_id;
                    if ($namaKelasModel->wasRecentlyCreated) {
                        $namaKelasCreated++;
                    }
                }

                // 4b. Upsert kelas
                // kelas_id dari top-level jika ada, fallback ke mahasiswa_kedokteran[0].k_id
                $mahasiswaList = $item['mahasiswa_kedokteran'] ?? [];
                $kelasId = $item['kelas_id'] ?? ($mahasiswaList[0]['k_id'] ?? null);
                if (! $kelasId) {
                    $skipped++;
                    continue;
                }

                $idMk = $item['kode_matakuliah'] ?? null;

                $kelasFields = [
                    'nama_kelas_id'       => $namaKelasId,
                    'semester'            => $item['semester'] ?? null,
                    'kode_tahun_akademik' => $item['kode_tahun_akademik'] ?? null,
                    'kode_program_studi'  => $item['kode_program_studi'] ?? null,
                    'id_matakuliah'       => $idMk,
                ];

                if (! Kelas::find($kelasId)) {
                    $newKelas = new Kelas();
                    $newKelas->kelas_id = $kelasId;
                    $newKelas->fill($kelasFields);
                    $newKelas->save();
                    $kelasCreated++;
                }
                // 4c. Upsert mengajar — key "dosen_kedokteran" di response
                foreach ($item['dosen_kedokteran'] ?? [] as $dosenData) {
                    $kodeDosen = $dosenData['kode_dosen'] ?? null;
                    $idmengjar = $dosenData['mengajar_id'] ?? null; // prefer ID dari top-level jika ada
                    if (! $kodeDosen) {
                        continue;
                    }
                    $alreadyMengajar = Mengajar::where('kelas_id', $kelasId)
                        ->where('kode_dosen', $kodeDosen)
                        ->exists();

                    if (! $alreadyMengajar) {
                        Mengajar::create([
                            'mengajar_id' => $idmengjar, // optional, hanya untuk memastikan konsistensi jika ID tersedia
                            'kelas_id'   => $kelasId,
                            'kode_dosen' => $kodeDosen,
                        ]);
                        $mengajarCreated++;
                    }
                }
                // 4d. Upsert kelas_mahasiswa
                // k_mahasiswa_id = kelas_mahasiswa_id, k_id = kelas_id, k_krs_detail = kode_krs_detail
            
                foreach ($mahasiswaList as $kmData) {
                    $kmPk          = $kmData['k_mahasiswa_id'] ?? null; // kelas_mahasiswa_id
                    $kmKelasId     = $kmData['k_id'] ?? $kelasId;      // kelas_id
                    $kodeKrsDetail = $kmData['k_krs_detail'] ?? null;   // kode_krs_detail

                    if (! $kodeKrsDetail) {
                        continue;
                    }

                    // Skip jika kode_krs_detail belum ada di lokal (FK constraint)
                    if (! KrsDetail::where('kode_krs_detail', $kodeKrsDetail)->exists()) {
                        continue;
                    }
                    
                    $alreadyInKelas = KelasMahasiswa::where('kelas_id', $kmKelasId)
                        ->where('kode_krs_detail', $kodeKrsDetail)
                        ->exists();

                    if (! $alreadyInKelas) {
                        KelasMahasiswa::create([
                            'kelas_mahasiswa_id' => $kmPk,
                            'kelas_id'           => $kmKelasId,
                            'kode_krs_detail'    => $kodeKrsDetail,
                        ]);
                        $mhsCreated++;
                    }
                }

            // } catch (\Exception $e) {
            //     $skipped++;
            // }
        }

        $msg = "Sinkronisasi selesai. "
            . "Kelas: +{$kelasCreated} baru. "
            . "Mengajar: +{$mengajarCreated}. "
            . "Mahasiswa kelas: +{$mhsCreated}.";

        if ($skipped > 0) {
            $msg .= " {$skipped} item dilewati karena error.";
        }

        return back()->with('success', $msg);
    }
}

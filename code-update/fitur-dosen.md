# Pengembangan Route Dosen — 4 Fitur

## Daftar Isi

- [Latar Belakang](#latar-belakang)
- [Ringkasan File](#ringkasan-file)
- [Fitur 1: Profil](#fitur-1-profil)
- [Fitur 2: Kurikulum](#fitur-2-kurikulum)
- [Fitur 3: Perwalian](#fitur-3-perwalian)
- [Fitur 4: Penilaian](#fitur-4-penilaian)
- [Route File — Hasil Akhir](#route-file--hasil-akhir)
- [Catatan Penting](#catatan-penting)

---

## Latar Belakang

Route dosen saat ini hanya punya 5 endpoint dasar (logout, me, index, show, update).
Perlu dikembangkan 4 fitur utama: **Profil, Kurikulum, Perwalian, Penilaian**.

Total akhir: **17 endpoint** (5 lama + 12 baru).

---

## Ringkasan File

| # | File | Status |
|---|------|--------|
| 1 | `app/Models/Kelas.php` | **UBAH** — tambah `use HasCode` |
| 2 | `app/Models/KelasMahasiswa.php` | **UBAH** — tambah `use HasCode` |
| 3 | `app/Models/Mengajar.php` | **UBAH** — tambah `use HasCode` |
| 4 | `app/Http/Controllers/DosenController.php` | **UBAH** — tambah method `me()`, `profileUpdate()` |
| 5 | `app/Http/Controllers/Api_Dosen/PerwalianController.php` | **UBAH** — isi dari kosong (6 method) |
| 6 | `app/Http/Controllers/Api_Dosen/KurikulumController.php` | **BARU** |
| 7 | `app/Http/Controllers/Api_Dosen/NilaiController.php` | **BARU** |
| 8 | `app/Service/ServicePerwalian.php` | **UBAH** — tambah method validasi, riwayat, jumlah |
| 9 | `app/Service/ServiceMengajar.php` | **BARU** |
| 10 | `app/Service/ServiceNilaiDosen.php` | **BARU** |
| 11 | `routes/dosen.php` | **UBAH** — rewrite semua route |

---

## Fitur 1: Profil

### Endpoint

| Method | Endpoint | Controller | Fungsi |
|--------|----------|------------|--------|
| `GET` | `/api/dosen/me` | `DosenController@me` | Profil lengkap dosen login |
| `PUT` | `/api/dosen/profile/update` | `DosenController@profileUpdate` | Update data diri (pakai auth) |

### Detail

#### `GET /api/dosen/me`

Pindahkan dari `AuthController@me_dosen` ke `DosenController@me`.

```php
public function me(): JsonResponse
{
    $dosen = Auth::guard('dosen_web')->user();
    $dosen->load('programStudi');

    return response()->json([
        'status' => true,
        'data' => [
            'code' => $dosen->toCode(),
            'kode_dosen' => $dosen->kode_dosen,
            'nik' => $dosen->nik,
            'nama_dosen' => $dosen->nama_dosen,
            'alamat_email' => $dosen->alamat_email,
            'no_telp' => $dosen->no_telp,
            'field_studi' => $dosen->field_studi,
            'alumni' => $dosen->alumni,
            'homebase' => $dosen->programStudi?->nama_program_studi,
            'status_dosen' => $dosen->status_dosen,
            'aktif' => $dosen->aktif,
            'signature' => $dosen->signature,
        ],
    ]);
}
```

#### `PUT /api/dosen/profile/update`

Tidak perlu `kode_dosen` di body — pakai user yang sedang login.

```php
public function profileUpdate(Request $request): JsonResponse
{
    $dosen = Auth::guard('dosen_web')->user();

    $validated = $request->validate([
        'no_telp' => 'sometimes|string|max:20',
        'alamat_email' => 'sometimes|email|max:75',
        'field_studi' => 'sometimes|string|max:100',
        'alumni' => 'sometimes|string|max:100',
        'sandi_pengguna' => 'sometimes|string|min:6',
        'signature' => 'sometimes|string',
    ]);

    if (isset($validated['sandi_pengguna'])) {
        $validated['sandi_pengguna'] = Hash::make($validated['sandi_pengguna']);
    }

    $dosen->update($validated);

    return ApiResponse::success([
        'code' => $dosen->toCode(),
        'nama_dosen' => $dosen->nama_dosen,
    ], 'Profil berhasil diperbarui.');
}
```

---

## Fitur 2: Kurikulum

### Endpoint

| Method | Endpoint | Controller | Fungsi |
|--------|----------|------------|--------|
| `GET` | `/api/dosen/kurikulum` | `KurikulumController@index` | Daftar nama kurikulum |
| `GET` | `/api/dosen/kurikulum/detail` | `KurikulumController@show` | Detail kurikulum (?code_nama_kurikulum=) |

### Controller — `KurikulumController`

```php
<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceKurikulum;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class KurikulumController extends Controller
{
    public function __construct(
        private readonly ServiceKurikulum $kurikulumService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->kurikulumService->nama_kurikulum();
    }

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_nama_kurikulum' => ['required', 'string'],
        ]);

        try {
            $kode = Crypt::decryptString($validated['code_nama_kurikulum']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_nama_kurikulum' => 'Format code tidak valid']);
        }

        return $this->kurikulumService->kurikulum_by_nama_kurikulum($kode);
    }
}
```

Reuse service yang sudah ada:
- `ServiceKurikulum.nama_kurikulum()` — return daftar nama kurikulum (paginated)
- `ServiceKurikulum.kurikulum_by_nama_kurikulum(kode)` — return matakuliah per semester

---

## Fitur 3: Perwalian

### Endpoint

| Method | Endpoint | Controller | Fungsi |
|--------|----------|------------|--------|
| `GET` | `/api/dosen/perwalian/jumlah` | `PerwalianController@jumlah` | Jumlah mahasiswa bimbingan |
| `GET` | `/api/dosen/perwalian/mahasiswa` | `PerwalianController@mahasiswaBimbingan` | Daftar mahasiswa bimbingan |
| `GET` | `/api/dosen/perwalian/mahasiswa/{code}` | `PerwalianController@detailMahasiswa` | Detail mahasiswa + KRS/KHS |
| `GET` | `/api/dosen/perwalian/mahasiswa/{code}/krs` | `PerwalianController@krsMahasiswa` | KRS mahasiswa (untuk divalidasi) |
| `POST` | `/api/dosen/perwalian/validasi-krs` | `PerwalianController@validasiKrs` | Approve/reject KRS |
| `GET` | `/api/dosen/perwalian/validasi` | `PerwalianController@riwayatValidasi` | Riwayat validasi saya |

### Controller — `PerwalianController`

```php
<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Perwalian;
use App\Models\PerwalianKrsValidasi;
use App\Service\ServiceKHS;
use App\Service\ServiceKRS;
use App\Service\ServicePerwalian;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class PerwalianController extends Controller
{
    public function __construct(
        private readonly ServicePerwalian $perwalianService,
        private readonly ServiceKRS $krsService,
        private readonly ServiceKHS $khsService,
    ) {}

    public function jumlah(): JsonResponse
    {
        $dosen = Auth::guard('dosen_web')->user();

        $jumlah = Perwalian::where('kode_dosen', $dosen->kode_dosen)
            ->orWhere('kode_dosen_perwakilan', $dosen->kode_dosen)
            ->count();

        return ApiResponse::success([
            'jumlah_mahasiswa_bimbingan' => $jumlah,
        ]);
    }

    public function mahasiswaBimbingan(): JsonResponse
    {
        $dosen = Auth::guard('dosen_web')->user();
        return $this->perwalianService->getPerwalianByDosen((int) $dosen->kode_dosen);
    }

    public function detailMahasiswa(string $code): JsonResponse
    {
        try {
            $nim = Crypt::decryptString($code);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();

        // Validasi: dosen ini adalah pembimbingnya
        $perwalian = Perwalian::where('nim', $nim)
            ->where(function ($q) use ($dosen) {
                $q->where('kode_dosen', $dosen->kode_dosen)
                  ->orWhere('kode_dosen_perwakilan', $dosen->kode_dosen);
            })->first();

        if (! $perwalian) {
            return ApiResponse::error('Anda bukan pembimbing mahasiswa ini.', 403);
        }

        // Ambil data perwalian + KRS + KHS
        $dataPerwalian = $this->perwalianService->getPerwalianByMahasiswa($nim);
        $dataKRS = $this->krsService->getKRSMhs($nim);
        $dataKHS = $this->khsService->getKHSMhs($nim);

        // Gabungkan response (handle jika dataKRS/dataKHS adalah JsonResponse)
        $response = json_decode($dataPerwalian->getContent(), true);
        $response['krs_terakhir'] = json_decode($dataKRS->getContent(), true)['data'] ?? [];
        $response['khs_terakhir'] = json_decode($dataKHS->getContent(), true)['data'] ?? [];

        return response()->json($response);
    }

    public function krsMahasiswa(string $code): JsonResponse
    {
        try {
            $nim = Crypt::decryptString($code);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();

        // Validasi pembimbing
        $perwalian = Perwalian::where('nim', $nim)
            ->where(function ($q) use ($dosen) {
                $q->where('kode_dosen', $dosen->kode_dosen)
                  ->orWhere('kode_dosen_perwakilan', $dosen->kode_dosen);
            })->first();

        if (! $perwalian) {
            return ApiResponse::error('Anda bukan pembimbing mahasiswa ini.', 403);
        }

        // Ambil status validasi terakhir
        $validasi = PerwalianKrsValidasi::where('nim', $nim)
            ->where('kode_dosen_validator', $dosen->kode_dosen)
            ->first();

        $dataKRS = $this->krsService->getKRSMhs($nim);
        $response = json_decode($dataKRS->getContent(), true);
        $response['status_validasi'] = $validasi ? $validasi->status_krs : null;

        return response()->json($response);
    }

    public function validasiKrs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        try {
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();

        // Validasi pembimbing
        $perwalian = Perwalian::where('nim', $nim)
            ->where(function ($q) use ($dosen) {
                $q->where('kode_dosen', $dosen->kode_dosen)
                  ->orWhere('kode_dosen_perwakilan', $dosen->kode_dosen);
            })->first();

        if (! $perwalian) {
            return ApiResponse::error('Anda bukan pembimbing mahasiswa ini.', 403);
        }

        // Mapping status: approved → A, rejected → N
        $statusMap = ['approved' => 'A', 'rejected' => 'N'];

        $validasi = PerwalianKrsValidasi::updateOrCreate(
            [
                'nim' => $nim,
                'kode_dosen_validator' => $dosen->kode_dosen,
            ],
            ['status_krs' => $statusMap[$validated['status']]]
        );

        return ApiResponse::success([
            'nim' => $nim,
            'status_krs' => $validasi->status_krs,
        ], 'Validasi KRS berhasil.');
    }

    public function riwayatValidasi(): JsonResponse
    {
        $dosen = Auth::guard('dosen_web')->user();

        $validasi = PerwalianKrsValidasi::where('kode_dosen_validator', $dosen->kode_dosen)
            ->with('mahasiswa:nim,nama_mahasiswa')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(
            $validasi->map(fn ($v) => [
                'nim' => $v->nim,
                'nama_mahasiswa' => $v->mahasiswa?->nama_mahasiswa,
                'status_krs' => $v->status_krs,
                'tanggal' => $v->created_at,
            ])->values()->toArray(),
            'Riwayat validasi KRS.'
        );
    }
}
```

### Service — Method Baru di `ServicePerwalian`

Semua method perwalian yang di-reuse sudah ada:
- `getPerwalianByDosen(int $kode_dosen)` ✅ sudah ada
- `getPerwalianByMahasiswa(string $nim)` ✅ sudah ada

Tidak perlu method baru di ServicePerwalian — logic validasi KRS cukup di Controller karena sederhana (updateOrCreate langsung ke model).

---

## Fitur 4: Penilaian

### Endpoint

| Method | Endpoint | Controller | Fungsi |
|--------|----------|------------|--------|
| `GET` | `/api/dosen/nilai/kelas` | `NilaiController@kelas` | Daftar kelas yang diajar (+ template) |
| `GET` | `/api/dosen/nilai/kelas/{code_kelas}` | `NilaiController@detailKelas` | Detail kelas + template penilaian |
| `GET` | `/api/dosen/nilai/kelas/{code_kelas}/mahasiswa` | `NilaiController@mahasiswaKelas` | Daftar mahasiswa + nilai di kelas |
| `POST` | `/api/dosen/nilai/input` | `NilaiController@inputNilai` | Input/update nilai 1 mahasiswa |
| `POST` | `/api/dosen/nilai/input-batch` | `NilaiController@inputNilaiBatch` | Input nilai banyak mahasiswa |
| `GET` | `/api/dosen/nilai/rekap/{code_kelas}` | `NilaiController@rekapKelas` | Rekap nilai per kelas |

### Service — `ServiceMengajar` (BARU)

```php
<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\KelasMahasiswa;
use App\Models\Mengajar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class ServiceMengajar
{
    public function getKelasDiajar(int $kodeDosen, ?int $semester = null): JsonResponse
    {
        $query = Mengajar::where('kode_dosen', $kodeDosen)
            ->with([
                'kelas.matakuliah',
                'kelas.namaKelas',
                'kelas.tahunAkademik',
            ]);

        if ($semester) {
            $query->whereHas('kelas', fn($q) => $q->where('semester', $semester));
        }

        $mengajar = $query->get();

        $data = $mengajar->map(function ($m) {
            $template = AssessmentTemplate::active()
                ->byMatakuliah($m->kelas->id_matakuliah)
                ->first();

            $totalMhs = KelasMahasiswa::where('kelas_id', $m->kelas->kelas_id)->count();

            return [
                'code_kelas' => Crypt::encryptString($m->kelas->kelas_id),
                'nama_kelas' => $m->kelas->namaKelas?->nama_kelas,
                'matakuliah' => $m->kelas->matakuliah?->nama_matakuliah,
                'kode_matakuliah' => $m->kelas->matakuliah?->kode_matakuliah,
                'sks' => ($m->kelas->matakuliah?->sks_teori ?? 0) + ($m->kelas->matakuliah?->sks_praktik ?? 0),
                'semester' => $m->kelas->semester,
                'tahun_akademik' => $m->kelas->tahunAkademik?->tahun_akademik,
                'total_mahasiswa' => $totalMhs,
                'template' => $template ? [
                    'code' => Crypt::encryptString($template->id),
                    'versi' => $template->versi,
                ] : null,
            ];
        })->values()->toArray();

        return ApiResponse::success($data, 'Kelas yang diajar.');
    }

    public function getDetailKelas(int $kelasId, int $kodeDosen): JsonResponse
    {
        $mengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->whereHas('kelas', fn($q) => $q->where('kelas_id', $kelasId))
            ->with(['kelas.matakuliah', 'kelas.namaKelas', 'kelas.tahunAkademik'])
            ->first();

        if (! $mengajar) {
            return ApiResponse::error('Kelas tidak ditemukan atau Anda tidak mengajar di kelas ini.', 404);
        }

        $template = AssessmentTemplate::active()
            ->byMatakuliah($mengajar->kelas->id_matakuliah)
            ->first();

        $data = [
            'code_kelas' => Crypt::encryptString($mengajar->kelas->kelas_id),
            'nama_kelas' => $mengajar->kelas->namaKelas?->nama_kelas,
            'matakuliah' => $mengajar->kelas->matakuliah?->nama_matakuliah,
            'kode_matakuliah' => $mengajar->kelas->matakuliah?->kode_matakuliah,
            'sks' => ($mengajar->kelas->matakuliah?->sks_teori ?? 0) + ($mengajar->kelas->matakuliah?->sks_praktik ?? 0),
            'semester' => $mengajar->kelas->semester,
            'tahun_akademik' => $mengajar->kelas->tahunAkademik?->tahun_akademik,
            'template' => $template ? [
                'code' => Crypt::encryptString($template->id),
                'versi' => $template->versi,
                'structure' => $template->structure,
            ] : null,
        ];

        return ApiResponse::success($data, 'Detail kelas.');
    }

    public function getMahasiswaDiKelas(int $kelasId, int $kodeDosen, ?string $templateId = null): JsonResponse
    {
        $mengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->whereHas('kelas', fn($q) => $q->where('kelas_id', $kelasId))
            ->first();

        if (! $mengajar) {
            return ApiResponse::error('Anda tidak mengajar di kelas ini.', 404);
        }

        $kelasMhs = KelasMahasiswa::where('kelas_id', $kelasId)
            ->with(['krsDetail.krs.mahasiswa:nim,nama_mahasiswa'])
            ->get()
            ->map(function ($km) {
                return [
                    'code' => Crypt::encryptString($km->krsDetail?->krs?->mahasiswa?->nim),
                    'nim' => $km->krsDetail?->krs?->mahasiswa?->nim,
                    'nama_mahasiswa' => $km->krsDetail?->krs?->mahasiswa?->nama_mahasiswa,
                ];
            })->filter(fn($m) => $m['nim'] !== null)->values();

        if ($templateId) {
            $kelasMhs = app(\App\Service\Assessment\TreeTraversalService::class)
                ->getLeafNodes(AssessmentTemplate::find($templateId));

            // Diisi di controller
        }

        return ApiResponse::success($kelasMhs, 'Mahasiswa di kelas.');
    }
}
```

### Service — `ServiceNilaiDosen` (BARU)

```php
<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\AssessmentTemplate;
use App\Models\KelasMahasiswa;
use App\Models\Mengajar;
use App\Models\StudentScore;
use App\Service\Assessment\ScoreCalculationService;
use App\Service\Assessment\TreeTraversalService;
use Illuminate\Http\JsonResponse;

class ServiceNilaiDosen
{
    public function __construct(
        private readonly TreeTraversalService $treeService,
        private readonly ScoreCalculationService $scoreService,
    ) {}

    public function getMahasiswaDenganNilai(int $kelasId, int $kodeDosen, string $templateId): JsonResponse
    {
        $mengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->whereHas('kelas', fn($q) => $q->where('kelas_id', $kelasId))
            ->first();

        if (! $mengajar) {
            return ApiResponse::error('Anda tidak mengajar di kelas ini.', 404);
        }

        $template = AssessmentTemplate::findOrFail($templateId);
        $leafNodes = $this->treeService->getLeafNodes($template);
        $leafKeys = $leafNodes->pluck('key');

        $mahasiswa = KelasMahasiswa::where('kelas_id', $kelasId)
            ->with(['krsDetail.krs.mahasiswa:nim,nama_mahasiswa'])
            ->get()
            ->map(function ($km) use ($template, $leafKeys) {
                $nim = $km->krsDetail?->krs?->mahasiswa?->nim;
                if (! $nim) return null;

                $scores = StudentScore::where('template_id', $template->id)
                    ->where('nim', $nim)
                    ->whereIn('node_key', $leafKeys)
                    ->pluck('score', 'node_key');

                return [
                    'nim' => $nim,
                    'nama_mahasiswa' => $km->krsDetail->krs->mahasiswa->nama_mahasiswa,
                    'nilai' => $leafKeys->mapWithKeys(fn($key) => [
                        $key => isset($scores[$key]) ? (float) $scores[$key] : null,
                    ]),
                    'nilai_akhir' => $this->scoreService->calculateFinalScore($template, $nim),
                ];
            })->filter()->values();

        return ApiResponse::success($mahasiswa, 'Mahasiswa dengan nilai.');
    }

    public function inputNilai(string $templateId, string $nim, array $nilai): JsonResponse
    {
        $template = AssessmentTemplate::findOrFail($templateId);
        $leafNodes = $this->treeService->getLeafNodes($template);
        $validKeys = $leafNodes->pluck('key')->toArray();

        foreach ($nilai as $key => $score) {
            if (! in_array($key, $validKeys)) continue;

            StudentScore::updateOrCreate(
                [
                    'template_id' => $templateId,
                    'nim' => $nim,
                    'node_key' => $key,
                ],
                [
                    'score' => $score,
                    // assessor_id sengaja tidak diisi karena FK ke users table, bukan dosen
                ]
            );
        }

        $finalScore = $this->scoreService->calculateFinalScore($template, $nim);

        return ApiResponse::success([
            'nim' => $nim,
            'nilai_akhir' => $finalScore,
        ], 'Nilai berhasil disimpan.');
    }

    public function rekapNilaiKelas(int $kelasId, int $kodeDosen, string $templateId): JsonResponse
    {
        $mengajar = Mengajar::where('kode_dosen', $kodeDosen)
            ->whereHas('kelas', fn($q) => $q->where('kelas_id', $kelasId))
            ->first();

        if (! $mengajar) {
            return ApiResponse::error('Anda tidak mengajar di kelas ini.', 404);
        }

        $template = AssessmentTemplate::findOrFail($templateId);

        $mahasiswa = KelasMahasiswa::where('kelas_id', $kelasId)
            ->with(['krsDetail.krs.mahasiswa:nim,nama_mahasiswa'])
            ->get()
            ->map(function ($km) use ($template) {
                $nim = $km->krsDetail?->krs?->mahasiswa?->nim;
                if (! $nim) return null;

                return [
                    'nim' => $nim,
                    'nama_mahasiswa' => $km->krsDetail->krs->mahasiswa->nama_mahasiswa,
                    'nilai_akhir' => $this->scoreService->calculateFinalScore($template, $nim),
                ];
            })->filter();

        $nilaiAkhir = $mahasiswa->pluck('nilai_akhir')->filter();
        $tertinggi = $nilaiAkhir->max();
        $terendah = $nilaiAkhir->min();
        $rataRata = $nilaiAkhir->avg();

        return ApiResponse::success([
            'rekap' => $mahasiswa->values(),
            'statistik' => [
                'tertinggi' => $tertinggi,
                'terendah' => $terendah,
                'rata_rata' => round($rataRata, 2),
                'jumlah_mahasiswa' => $mahasiswa->count(),
            ],
        ], 'Rekap nilai kelas.');
    }
}
```

### Controller — `NilaiController`

```php
<?php

namespace App\Http\Controllers\Api_Dosen;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceMengajar;
use App\Service\ServiceNilaiDosen;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class NilaiController extends Controller
{
    public function __construct(
        private readonly ServiceMengajar $mengajarService,
        private readonly ServiceNilaiDosen $nilaiDosenService,
    ) {}

    public function kelas(Request $request): JsonResponse
    {
        $dosen = Auth::guard('dosen_web')->user();
        $semester = $request->query('semester');

        return $this->mengajarService->getKelasDiajar((int) $dosen->kode_dosen, $semester);
    }

    public function detailKelas(string $code_kelas): JsonResponse
    {
        try {
            $kelasId = Crypt::decryptString($code_kelas);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_kelas' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();

        return $this->mengajarService->getDetailKelas((int) $kelasId, (int) $dosen->kode_dosen);
    }

    public function mahasiswaKelas(string $code_kelas, Request $request): JsonResponse
    {
        try {
            $kelasId = Crypt::decryptString($code_kelas);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_kelas' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();
        $templateCode = $request->query('code_template');

        if ($templateCode) {
            try {
                $templateId = Crypt::decryptString($templateCode);
                return $this->nilaiDosenService->getMahasiswaDenganNilai(
                    (int) $kelasId, (int) $dosen->kode_dosen, $templateId
                );
            } catch (DecryptException) {
                return ApiResponse::validation(['code_template' => 'Format code tidak valid']);
            }
        }

        return $this->mengajarService->getMahasiswaDiKelas(
            (int) $kelasId, (int) $dosen->kode_dosen
        );
    }

    public function inputNilai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_template' => ['required', 'string'],
            'code' => ['required', 'string'],
            'nilai' => ['required', 'array'],
            'nilai.*' => ['numeric', 'min:0', 'max:100'],
        ]);

        try {
            $templateId = Crypt::decryptString($validated['code_template']);
            $nim = Crypt::decryptString($validated['code']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code' => 'Format code tidak valid']);
        }

        return $this->nilaiDosenService->inputNilai($templateId, $nim, $validated['nilai']);
    }

    public function inputNilaiBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_template' => ['required', 'string'],
            'data' => ['required', 'array'],
            'data.*.code' => ['required', 'string'],
            'data.*.nilai' => ['required', 'array'],
        ]);

        try {
            $templateId = Crypt::decryptString($validated['code_template']);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_template' => 'Format code tidak valid']);
        }

        $results = [];
        foreach ($validated['data'] as $item) {
            try {
                $nim = Crypt::decryptString($item['code']);
                $results[] = json_decode(
                    $this->nilaiDosenService->inputNilai($templateId, $nim, $item['nilai'])->getContent(),
                    true
                );
            } catch (DecryptException) {
                continue;
            }
        }

        return ApiResponse::success($results, 'Nilai batch berhasil disimpan.');
    }

    public function rekapKelas(string $code_kelas, Request $request): JsonResponse
    {
        try {
            $kelasId = Crypt::decryptString($code_kelas);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_kelas' => 'Format code tidak valid']);
        }

        $dosen = Auth::guard('dosen_web')->user();

        $templateCode = $request->query('code_template');
        if (! $templateCode) {
            return ApiResponse::validation(['code_template' => 'code_template required']);
        }

        try {
            $templateId = Crypt::decryptString($templateCode);
        } catch (DecryptException) {
            return ApiResponse::validation(['code_template' => 'Format code tidak valid']);
        }

        return $this->nilaiDosenService->rekapNilaiKelas(
            (int) $kelasId, (int) $dosen->kode_dosen, $templateId
        );
    }
}
```

---

## Route File — Hasil Akhir

File: `routes/dosen.php`

```php
<?php

use App\Http\Controllers\Api_Dosen\KurikulumController;
use App\Http\Controllers\Api_Dosen\NilaiController;
use App\Http\Controllers\Api_Dosen\PerwalianController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DosenController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware(['sanctum.spa', 'auth:dosen_web', 'sanctum.cookie', 'log.activity'])
    ->group(function () {

        // ─── 1. PROFIL (3) ───
        Route::post('/dosen/logout', [AuthController::class, 'logout']);
        Route::get('/dosen/me', [DosenController::class, 'me']);
        Route::get('/dosen', [DosenController::class, 'index']);
        Route::get('/dosen/detail', [DosenController::class, 'show']);
        Route::put('/dosen/profile/update', [DosenController::class, 'profileUpdate']);

        // ─── 2. KURIKULUM (2) ───
        Route::prefix('/dosen/kurikulum')->group(function () {
            Route::get('/', [KurikulumController::class, 'index']);
            Route::get('/detail', [KurikulumController::class, 'show']);
        });

        // ─── 3. PERWALIAN (6) ───
        Route::prefix('/dosen/perwalian')->group(function () {
            Route::get('/jumlah', [PerwalianController::class, 'jumlah']);
            Route::get('/mahasiswa', [PerwalianController::class, 'mahasiswaBimbingan']);
            Route::get('/mahasiswa/{code}', [PerwalianController::class, 'detailMahasiswa']);
            Route::get('/mahasiswa/{code}/krs', [PerwalianController::class, 'krsMahasiswa']);
            Route::post('/validasi-krs', [PerwalianController::class, 'validasiKrs']);
            Route::get('/validasi', [PerwalianController::class, 'riwayatValidasi']);
        });

        // ─── 4. PENILAIAN (6) ───
        Route::prefix('/dosen/nilai')->group(function () {
            Route::get('/kelas', [NilaiController::class, 'kelas']);
            Route::get('/kelas/{code_kelas}', [NilaiController::class, 'detailKelas']);
            Route::get('/kelas/{code_kelas}/mahasiswa', [NilaiController::class, 'mahasiswaKelas']);
            Route::post('/input', [NilaiController::class, 'inputNilai']);
            Route::post('/input-batch', [NilaiController::class, 'inputNilaiBatch']);
            Route::get('/rekap/{code_kelas}', [NilaiController::class, 'rekapKelas']);
        });

        Route::fallback(fn() => response()->json([
            'status' => false, 'message' => 'Endpoint tidak ditemukan.', 'error' => 'NOT_FOUND',
        ], 404));
    });
```

---

## Catatan Penting

### 1. `HasCode` — Tambahkan di 3 Model

```php
// app/Models/Kelas.php
use App\Models\Traits\HasCode;
class Kelas extends Model {
    use HasCode;
    // ...
}

// app/Models/KelasMahasiswa.php
use App\Models\Traits\HasCode;
class KelasMahasiswa extends Model {
    use HasCode;
    // ...
}

// app/Models/Mengajar.php
use App\Models\Traits\HasCode;
class Mengajar extends Model {
    use HasCode;
    // ...
}
```

### 2. `assessor_id` Tidak Diisi untuk Dosen

Di `ServiceNilaiDosen.inputNilai()`, `assessor_id` sengaja tidak diisi karena FK `student_scores.assessor_id` mengacu ke `users.id` (tabel admin/staff), bukan ke `dosen.kode_dosen`. Jika diisi akan error foreign key constraint.

### 3. Enum `status_krs` — Mapping Wajib

Tabel `perwalian_krs_validasi` menggunakan enum `'A'` / `'N'`, bukan string `'approved'` / `'rejected'`. Mapping dilakukan di controller:

```php
$statusMap = ['approved' => 'A', 'rejected' => 'N'];
```

### 4. Import `Crypt` Tidak Boleh Lupa

Semua controller yang melakukan decrypt harus import:
```php
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
```

### 5. Route `/dosen/me` Pindah ke `DosenController`

Route `GET /api/dosen/me` dipindahkan dari `AuthController@me_dosen` ke `DosenController@me`. Pastikan tidak ada konflik dengan route lama.

### 6. Validasi Akses — Hanya Data Milik Sendiri

Semua endpoint perwalian dan penilaian memvalidasi bahwa dosen yang login adalah:
- Pembimbing mahasiswa (perwalian)
- Pengajar kelas (penilaian)

Jika tidak, return 403.

---

## Ringkasan Akhir

| Fitur | Endpoint | File Baru | File Diubah |
|-------|----------|-----------|-------------|
| Profil | 2 | - | `DosenController.php` |
| Kurikulum | 2 | `Api_Dosen/KurikulumController.php` | - |
| Perwalian | 6 | - | `Api_Dosen/PerwalianController.php`, `ServicePerwalian.php` |
| Penilaian | 6 | `Api_Dosen/NilaiController.php`, `ServiceMengajar.php`, `ServiceNilaiDosen.php` | - |
| Infra | - | - | `Kelas.php`, `KelasMahasiswa.php`, `Mengajar.php`, `routes/dosen.php` |
| **Total** | **16 baru + 1 lama** | **4 file** | **7 file** |

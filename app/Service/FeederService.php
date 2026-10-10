<?php

namespace App\Service;

use App\Models\FeederCredential;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeederService
{
    protected ?string $token = null;
    protected ?string $wsUrl = null;
    protected int $timeout = 15;

    public function __construct()
    {
        $this->wsUrl = FeederCredential::getWsUrl();
        $this->token = FeederCredential::getValue('feeder_token');
    }

    /**
     * Dapatkan token sesi aktif atau lakukan login otomatis (GetToken).
     */
    public function getToken(bool $forceRefresh = false): ?string
    {
        if (! $forceRefresh && ! empty($this->token)) {
            return $this->token;
        }

        $username = FeederCredential::getValue('feeder_username');
        $password = FeederCredential::getValue('feeder_password');

        if (! $this->wsUrl || ! $username || ! $password) {
            throw new Exception('Kredensial Feeder belum lengkap. Silakan lengkapi di menu Konfigurasi Feeder.');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($this->wsUrl, [
                    'act' => 'GetToken',
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                if (($body['error_code'] ?? -1) === 0 && ! empty($body['data']['token'])) {
                    $newToken = $body['data']['token'];
                    $this->token = $newToken;
                    FeederCredential::setValue('feeder_token', $newToken, 'Token aktif diperbarui otomatis');
                    return $newToken;
                }

                $err = $body['error_desc'] ?? 'Gagal mendapatkan token Feeder.';
                throw new Exception("Autentikasi Feeder gagal: {$err}");
            }

            throw new Exception("HTTP error {$response->status()} saat menghubungi Feeder.");
        } catch (Throwable $e) {
            Log::error('FeederService getToken failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Kirim request standar ke Neo Feeder (JSON-RPC POST ws/live2.php).
     *
     * @param string $act Nama aksi (e.g. GetDetailNilaiPerkuliahanKelas, GetTranskripMahasiswa)
     * @param string|array $filter Filter query string atau array
     * @param string $order Urutan sorting
     * @param int $limit Batas record (default 500)
     * @param int $offset Offset data
     */
    public function request(
        string $act,
        string|array $filter = '',
        string $order = '',
        int $limit = 500,
        int $offset = 0
    ): array {
        $token = $this->getToken();

        // Normalisasi format filter jika array
        if (is_array($filter)) {
            $filterStr = implode(' AND ', array_map(
                fn ($k, $v) => "{$k} = '{$v}'",
                array_keys($filter),
                array_values($filter)
            ));
        } else {
            $filterStr = trim($filter);
        }

        $payload = [
            'act' => $act,
            'token' => $token,
            'filter' => $filterStr,
            'order' => $order,
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($this->wsUrl, $payload);

            if (! $response->successful()) {
                throw new Exception("HTTP {$response->status()} dari Feeder saat menjalankan aksi {$act}.");
            }

            $body = $response->json();

            // Jika token kedaluwarsa (kode error 100), refresh token dan coba sekali lagi
            if (($body['error_code'] ?? 0) === 100) {
                Log::warning("Token Feeder kedaluwarsa pada aksi {$act}, mencoba refresh token...");
                $token = $this->getToken(true);
                $payload['token'] = $token;

                $retryResponse = Http::timeout($this->timeout)->acceptJson()->post($this->wsUrl, $payload);
                $body = $retryResponse->json();
            }

            if (($body['error_code'] ?? 0) !== 0) {
                $msg = $body['error_desc'] ?? 'Terjadi kesalahan pada Web Service Feeder.';
                throw new Exception("Feeder error [{$act}]: {$msg}");
            }

            $rawRows = $body['data'] ?? [];
            $normalizedRows = array_map(fn ($r) => $this->normalizeRow($r), $rawRows);

            return [
                'error_code' => 0,
                'error_desc' => '',
                'count' => count($normalizedRows),
                'data' => $normalizedRows,
            ];

        } catch (Throwable $e) {
            Log::error("FeederService request [{$act}] error: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil seluruh data dengan mekanisme paging looping otomatis (chunk 500) sampai habis.
     */
    public function fetchAll(
        string $act,
        string|array $filter = '',
        string $order = '',
        int $chunkSize = 500
    ): array {
        $allData = [];
        $offset = 0;

        do {
            $res = $this->request($act, $filter, $order, $chunkSize, $offset);
            $rows = $res['data'] ?? [];
            $allData = array_merge($allData, $rows);
            $offset += $chunkSize;
        } while (count($rows) === $chunkSize);

        return $allData;
    }

    /**
     * Normalisasi nama-nama kolom respon Feeder ke standar seragam.
     */
    public function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $val) {
            $cleanKey = strtolower(trim($key));
            $normalized[$cleanKey] = is_string($val) ? trim($val) : $val;
        }

        // Penyeragaman key aliases
        $nim = $normalized['nim'] ?? $normalized['npm'] ?? $normalized['nomor_mahasiswa'] ?? null;
        $kodeMk = $normalized['kode_mata_kuliah'] ?? $normalized['kode_mk'] ?? $normalized['id_matakuliah'] ?? null;
        $namaMk = $normalized['nama_mata_kuliah'] ?? $normalized['nama_mk'] ?? $normalized['matakuliah'] ?? null;
        $namaMhs = $normalized['nama_mahasiswa'] ?? $normalized['nama'] ?? null;
        $namaKelas = $normalized['nama_kelas_kuliah'] ?? $normalized['nama_kelas'] ?? $normalized['kelas'] ?? null;

        $nilaiAngka = isset($normalized['nilai_angka'])
            ? (float) $normalized['nilai_angka']
            : (isset($normalized['na']) ? (float) $normalized['na'] : null);

        $nilaiHuruf = $normalized['nilai_huruf'] ?? $normalized['nh'] ?? $normalized['grade'] ?? null;

        $nilaiIndeks = isset($normalized['nilai_indeks'])
            ? (float) $normalized['nilai_indeks']
            : (isset($normalized['indeks']) ? (float) $normalized['indeks'] : null);

        $sks = isset($normalized['sks_mata_kuliah'])
            ? (float) $normalized['sks_mata_kuliah']
            : (isset($normalized['sks']) ? (float) $normalized['sks'] : null);

        return array_merge($normalized, [
            'nim' => $nim ? strtoupper($nim) : null,
            'nama_mahasiswa' => $namaMhs,
            'kode_mata_kuliah' => $kodeMk ? strtoupper($kodeMk) : null,
            'nama_mata_kuliah' => $namaMk,
            'nama_kelas' => $namaKelas,
            'nilai_angka' => $nilaiAngka,
            'nilai_huruf' => $nilaiHuruf ? strtoupper($nilaiHuruf) : null,
            'nilai_indeks' => $nilaiIndeks,
            'sks' => $sks,
            'id_kelas_kuliah' => $normalized['id_kelas_kuliah'] ?? null,
            'id_registrasi_mahasiswa' => $normalized['id_registrasi_mahasiswa'] ?? null,
            'is_transfer' => ! empty($normalized['id_nilai_transfer']),
            'is_konversi' => ! empty($normalized['id_konversi_aktivitas']),
        ]);
    }

    /**
     * Saring dan sembunyikan field-field ID internal teknis (UUID) dari Feeder PDDIKTI.
     * Menyembunyikan id_registrasi_mahasiswa, id_mahasiswa, id_kelas_kuliah, id_matkul, id_prodi, dll.
     */
    public function sanitizeFeederRow(array $row): array
    {
        return array_filter($row, function ($val, $key) {
            $cleanKey = strtolower(trim((string) $key));

            // Sembunyikan jika nama field diawali dengan 'id_' atau persis 'id'
            if ($cleanKey === 'id' || str_starts_with($cleanKey, 'id_')) {
                return false;
            }

            // Sembunyikan juga jika valuenya adalah string UUID 36 karakter
            if (is_string($val) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($val))) {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Mengambil detail nilai perkuliahan kelas (GetDetailNilaiPerkuliahanKelas).
     */
    public function getDetailNilaiPerkuliahanKelas(string|array $filter = '', string $order = ''): array
    {
        $rows = $this->fetchAll('GetDetailNilaiPerkuliahanKelas', $filter, $order);
        return array_map(fn ($r) => $this->sanitizeFeederRow($r), $rows);
    }

    /**
     * Dapatkan informasi mahasiswa (termasuk id_registrasi_mahasiswa) dari Feeder berdasarkan NIM via GetListMahasiswa.
     */
    public function getMahasiswaByNim(string $nim): ?array
    {
        $cleanNim = strtoupper(trim($nim));
        $res = $this->request('GetListMahasiswa', "nim = '{$cleanNim}'", '', 1, 0);
        $rows = $res['data'] ?? [];
        return ! empty($rows[0]) ? $rows[0] : null;
    }

    /**
     * Mengambil riwayat transkrip lengkap mahasiswa (GetTranskripMahasiswa + GetDetailNilaiPerkuliahanKelas).
     * Sesuai alur Feeder:
     * 1. Query GetDetailNilaiPerkuliahanKelas (filter nim) -> Nilai Kelas Reguler & id_registrasi_mahasiswa.
     * 2. Query GetTranskripMahasiswa (filter id_registrasi_mahasiswa) -> Nilai Transfer / Konversi / Transkrip.
     * 3. Konsolidasi kedua sumber menjadi riwayat komprehensif.
     */
    public function getTranskripMahasiswa(string $idOrNim): array
    {
        $clean = trim($idOrNim);
        $nim = null;
        $namaMhs = null;
        $idRegistrasi = null;
        $kelasRegulerRows = [];

        if (str_contains($clean, '-')) {
            $idRegistrasi = $clean;
        } else {
            $nim = strtoupper($clean);

            // Tahap 1: Ambil Nilai Kelas Reguler & id_registrasi_mahasiswa via GetDetailNilaiPerkuliahanKelas
            try {
                $kelasRegulerRows = $this->fetchAll('GetDetailNilaiPerkuliahanKelas', "nim = '{$nim}'");
                if (! empty($kelasRegulerRows[0]['id_registrasi_mahasiswa'])) {
                    $idRegistrasi = $kelasRegulerRows[0]['id_registrasi_mahasiswa'];
                    $namaMhs = $kelasRegulerRows[0]['nama_mahasiswa'] ?? null;
                }
            } catch (Throwable $e) {
                Log::warning("Gagal ambil kelas reguler Feeder untuk NIM {$nim}: " . $e->getMessage());
            }

            // Fallback jika belum terdaftar di kelas reguler: cari via GetListMahasiswa
            if (! $idRegistrasi) {
                $mhsFeeder = $this->getMahasiswaByNim($nim);
                if (! $mhsFeeder || empty($mhsFeeder['id_registrasi_mahasiswa'])) {
                    throw new Exception("Mahasiswa dengan NIM '{$nim}' tidak ditemukan di Feeder PDDIKTI.");
                }
                $idRegistrasi = $mhsFeeder['id_registrasi_mahasiswa'];
                $namaMhs = $mhsFeeder['nama_mahasiswa'] ?? null;
            }
        }

        // Tahap 2: Ambil Nilai Transfer / Konversi / Transkrip via GetTranskripMahasiswa
        $transkripRows = [];
        if ($idRegistrasi) {
            $transkripRows = $this->fetchAll('GetTranskripMahasiswa', "id_registrasi_mahasiswa = '{$idRegistrasi}'", 'kode_mata_kuliah');
        }

        // Tahap 3: Konsolidasi Data Nilai Kelas Reguler + Transkrip/Transfer
        $consolidated = [];

        // Masukkan data nilai kelas reguler terlebih dahulu
        foreach ($kelasRegulerRows as $rk) {
            $kode = strtoupper($rk['kode_mata_kuliah'] ?? '');
            if ($kode) {
                $consolidated[$kode] = $rk;
                $consolidated[$kode]['sumber_feeder'] = 'kelas_reguler';
            }
        }

        // Gabungkan nilai transkrip (nilai transfer, rekognisi MBKM, atau nilai historis lainnya)
        foreach ($transkripRows as $rt) {
            $kode = strtoupper($rt['kode_mata_kuliah'] ?? '');
            if (! $kode) {
                continue;
            }

            if (! isset($consolidated[$kode])) {
                $rt['nim'] = $nim ?? ($rt['nim'] ?? null);
                $rt['nama_mahasiswa'] = $namaMhs ?? ($rt['nama_mahasiswa'] ?? null);
                $rt['sumber_feeder'] = (! empty($rt['is_transfer']) || ! empty($rt['is_konversi'])) ? 'transfer_konversi' : 'transkrip';
                $consolidated[$kode] = $rt;
            } else {
                // Lengkapi atribut konversi/transfer jika ada
                if (! empty($rt['is_transfer'])) {
                    $consolidated[$kode]['is_transfer'] = true;
                }
                if (! empty($rt['is_konversi'])) {
                    $consolidated[$kode]['is_konversi'] = true;
                }
                if (empty($consolidated[$kode]['nilai_angka']) && isset($rt['nilai_angka'])) {
                    $consolidated[$kode]['nilai_angka'] = $rt['nilai_angka'];
                    $consolidated[$kode]['nilai_huruf'] = $rt['nilai_huruf'];
                    $consolidated[$kode]['nilai_indeks'] = $rt['nilai_indeks'];
                }
            }
        }

        // Pastikan NIM dan Nama Mahasiswa terisi
        $finalList = array_values($consolidated);
        if ($nim || $namaMhs) {
            foreach ($finalList as &$r) {
                if (empty($r['nim']) && $nim) {
                    $r['nim'] = $nim;
                }
                if (empty($r['nama_mahasiswa']) && $namaMhs) {
                    $r['nama_mahasiswa'] = $namaMhs;
                }
            }
            unset($r);
        }

        return array_map(fn ($r) => $this->sanitizeFeederRow($r), $finalList);
    }

    /**
     * Periksa status koneksi dan token aktif.
     */
    public function checkStatus(): array
    {
        $wsUrl = $this->wsUrl;
        $hasToken = ! empty($this->token);
        $statusOk = false;
        $message = '';
        $currentToken = $this->token;

        if (! $wsUrl) {
            return [
                'connected' => false,
                'has_token' => false,
                'ws_url' => null,
                'token' => null,
                'message' => 'Konfigurasi Feeder belum diatur. Silakan atur di /admin/feeder-config.',
            ];
        }

        try {
            $token = $this->getToken(false);
            $statusOk = ! empty($token);
            $currentToken = $token;
            $message = 'Koneksi ke Web Service Feeder PDDikti terhubung dan token aktif.';
        } catch (Throwable $e) {
            $statusOk = false;
            $message = 'Koneksi gagal: '.$e->getMessage();
        }

        return [
            'connected' => $statusOk,
            'has_token' => ! empty($currentToken),
            'ws_url' => $wsUrl,
            'token' => $currentToken,
            'message' => $message,
        ];
    }
}

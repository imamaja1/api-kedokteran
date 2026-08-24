<?php

namespace App\Service;

use App\Models\ApiConnection;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\PerwalianKrsValidasi;
use App\Models\SksRule;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceKeuangan
{
    /**
     * Sinkronisasi status pembayaran dari external API keuangan.
     *
     * Dipanggil saat KRS belum ada dan pembayaran lokal belum lunas.
     * Jika external API mengkonfirmasi pembayaran lunas, buat/update
     * record Pembayaran lokal + auto-create KRS.
     *
     * @return Pembayaran|null  Record Pembayaran lunas, atau null jika belum lunas/error
     */
    public function syncPembayaranFromExternal(string $nim, TahunAkademik $ta): ?Pembayaran
    {
        $conn = ApiConnection::where('is_active', true)
            ->where('name', 'Keuangan')
            ->first();

        if (! $conn) {
            Log::warning('ServiceKeuangan: ApiConnection "Keuangan" tidak ditemukan.');
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->get($conn->base_url, [
                    'nim' => $nim,
                    'tahun_akademik' => $ta->tahun_akademik,
                    'semester' => $ta->semester,
                ]);

            if ($response->failed()) {
                Log::warning("ServiceKeuangan: HTTP {$response->status()} untuk NIM {$nim}");
                return null;
            }

            $body = $response->json();

            // Pastikan response valid
            if (! isset($body['data']['is_lunas']) || ! $body['data']['is_lunas']) {
                return null;
            }

            $data = $body['data'];

            // Ambil data mahasiswa untuk program_studi_kode
            $mahasiswa = Mahasiswa::where('nim', $nim)->first();
            $kodeProdi = $mahasiswa?->program_studi_kode;

            // Hitung semester mahasiswa
            $serviceSemester = new ServiceSemester();
            $semesterMhs = $serviceSemester->hitung($nim, $ta);

            // Hitung SKS limit
            $sksLimit = $this->getSksLimitFromRule($nim, $kodeProdi, $semesterMhs);

            // Parse tanggal_bayar dari response
            $tanggalBayar = $data['jatuh_tempo'] ?? null;

            // Generate keterangan
            $keterangan = "Pembayaran UKT Semester {$semesterMhs}";

            // Create atau update Pembayaran
            $pembayaran = Pembayaran::where('nim', $nim)
                ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
                ->first();

            if ($pembayaran) {
                $pembayaran->update([
                    'status' => 'lunas',
                    'tanggal_bayar' => $tanggalBayar,
                    'keterangan' => $keterangan,
                    'sks_override' => $pembayaran->sks_override ?? $sksLimit,
                ]);
            } else {
                $pembayaran = Pembayaran::create([
                    'nim' => $nim,
                    'kode_tahun_akademik' => $ta->kode_tahun_akademik,
                    'status' => 'lunas',
                    'tanggal_bayar' => $tanggalBayar,
                    'keterangan' => $keterangan,
                    'status_mahasiswa' => 'aktif',
                    'sks_override' => $sksLimit,
                ]);
            }

            // Auto-create KRS + PerwalianKrsValidasi
            $this->autoCreateKrs($nim, $ta);

            return $pembayaran;

        } catch (\Exception $e) {
            Log::error("ServiceKeuangan: Gagal sync pembayaran NIM {$nim}: {$e->getMessage()}");
            return null;
        }
    }

    // ─── Private Methods ────────────────────────────────────────────────

    /**
     * Auto-create KRS + PerwalianKrsValidasi jika belum ada.
     */
    private function autoCreateKrs(string $nim, TahunAkademik $ta): void
    {
        $existingKrs = Krs::where('nim', $nim)
            ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
            ->exists();

        if ($existingKrs) {
            return;
        }

        $serviceSemester = new ServiceSemester();
        $semester = $serviceSemester->hitung($nim, $ta);

        Krs::create([
            'nim' => $nim,
            'kode_tahun_akademik' => $ta->kode_tahun_akademik,
            'semester' => $semester,
        ]);

        PerwalianKrsValidasi::create([
            'nim' => $nim,
            'kode_dosen_validator' => null,
            'status_krs' => 'N',
        ]);
    }

    /**
     * Dapatkan SKS limit dari rules berdasarkan IPK semester sebelumnya.
     */
    private function getSksLimitFromRule(string $nim, ?int $kodeProdi, int $currentSemester): int
    {
        if (! $kodeProdi || $currentSemester <= 1) {
            return 24;
        }

        $previousIpk = $this->findPreviousIpk($nim, $currentSemester - 1);

        if ($previousIpk === null) {
            return 24;
        }

        $rule = SksRule::cariRule($kodeProdi, $previousIpk);

        return $rule?->sks_yang_dapat_diambil ?? 24;
    }

    /**
     * Cari IPK dari semester sebelumnya (loop mundur jika cuti)
     */
    private function findPreviousIpk(string $nim, int $semester): ?float
    {
        while ($semester >= 1) {
            $krs = Krs::where('nim', $nim)->where('semester', $semester)->first();

            if ($krs) {
                $ipk = $this->computeIpkFromKrs($krs);
                if ($ipk !== null) {
                    return $ipk;
                }
                return null;
            }

            if (! $this->isCuti($nim, $semester)) {
                return null;
            }

            $semester--;
        }

        return null;
    }

    /**
     * Cek apakah mahasiswa cuti di semester tertentu
     */
    private function isCuti(string $nim, int $targetSemester): bool
    {
        $angkatan = (int) substr($nim, 0, 2);
        $tas = TahunAkademik::all();

        foreach ($tas as $ta) {
            $tahunSekarang = (int) explode('/', $ta->tahun_akademik)[0];
            $semesterTa = (int) $ta->semester;

            $calcSemester = (($tahunSekarang - (2000 + $angkatan)) * 2) + $semesterTa;

            if ($calcSemester === $targetSemester) {
                $pembayaran = Pembayaran::where('nim', $nim)
                    ->where('kode_tahun_akademik', $ta->kode_tahun_akademik)
                    ->first();

                return $pembayaran?->status_mahasiswa === 'cuti';
            }
        }

        return false;
    }

    /**
     * Hitung IPK dari KRS berdasarkan KHS detail
     */
    private function computeIpkFromKrs(Krs $krs): ?float
    {
        $details = \App\Models\KrsDetail::where('kode_krs', $krs->kode_krs)
            ->join('matakuliah', 'krs_detail.id_matakuliah', '=', 'matakuliah.id_matakuliah')
            ->leftJoin('khs_detail', 'khs_detail.kode_krs_detail', '=', 'krs_detail.kode_krs_detail')
            ->select('matakuliah.sks_teori', 'matakuliah.sks_praktik', 'khs_detail.score')
            ->whereNotNull('khs_detail.score')
            ->get();

        if ($details->isEmpty()) {
            return null;
        }

        $totalSks = 0;
        $totalWeighted = 0;

        foreach ($details as $detail) {
            $sks = ($detail->sks_teori ?? 0) + ($detail->sks_praktik ?? 0);
            $totalSks += $sks;
            $totalWeighted += $sks * $detail->score;
        }

        return $totalSks > 0 ? round($totalWeighted / $totalSks, 2) : null;
    }
}

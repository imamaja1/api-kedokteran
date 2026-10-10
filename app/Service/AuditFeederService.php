<?php

namespace App\Service;

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AuditFeederService
{
    protected FeederService $feederService;
    protected int $cacheTtl = 3600; // 1 Jam

    public function __construct(FeederService $feederService)
    {
        $this->feederService = $feederService;
    }

    /**
     * Resolusi ID kelas dari numeric integer atau encrypted code_kelas.
     */
    public function resolveKelasId(int|string $codeOrId): int
    {
        if (is_numeric($codeOrId)) {
            return (int) $codeOrId;
        }

        $kelas = Kelas::findByCode((string) $codeOrId);
        if (! $kelas) {
            throw new Exception("Kelas dengan kode '{$codeOrId}' tidak ditemukan.");
        }

        return (int) $kelas->kelas_id;
    }

    /**
     * Normalisasi string nama matakuliah untuk pencocokan Tier-2.
     * Menghapus spasi ganda, tanda bintang (*), tanda strip, kurung, dan karakter khusus.
     */
    public function normalizeMatakuliah(?string $name): string
    {
        if (! $name) {
            return '';
        }

        $cleaned = preg_replace('/[\*\-\_\(\)\[\]\/\.\,\s+]/', '', strtolower($name));
        return trim($cleaned ?? '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO A: KELAS KULIAH (Audit_kelas)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tarik dan simpan data nilai kelas dari Feeder ke cache sementara.
     */
    public function pullKelasData(int|string $codeOrId, bool $force = false): array
    {
        $kelasId = $this->resolveKelasId($codeOrId);
        $cacheKey = "feeder_pull_kelas_{$kelasId}";

        if (! $force && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['source'] = 'cache';
            if (isset($cached['data']) && is_array($cached['data'])) {
                $cached['data'] = array_map([$this->feederService, 'sanitizeFeederRow'], $cached['data']);
            }
            return $cached;
        }

        $kelas = Kelas::with(['matakuliah', 'namaKelas', 'tahunAkademik'])->find($kelasId);
        if (! $kelas) {
            throw new Exception("Kelas ID {$kelasId} tidak ditemukan di SISKA.");
        }

        $kodeMk = $kelas->matakuliah?->kode_matakuliah;

        // Ambil data nilai kelas dari Feeder (berdasarkan filter kode_mata_kuliah atau ambil semua)
        $filter = $kodeMk ? "kode_mata_kuliah = '{$kodeMk}'" : '';
        $feederRows = $this->feederService->getDetailNilaiPerkuliahanKelas($filter);
        $feederRows = array_map([$this->feederService, 'sanitizeFeederRow'], $feederRows);

        $payload = [
            'kelas_id' => $kelasId,
            'source' => 'feeder_live',
            'pulled_at' => Carbon::now()->toIso8601String(),
            'total_rows' => count($feederRows),
            'data' => $feederRows,
        ];

        Cache::put($cacheKey, $payload, $this->cacheTtl);

        return $payload;
    }

    /**
     * Audit komparasi nilai per kelas kuliah di memori (O(1)).
     */
    public function auditKelas(int|string $codeOrId, bool $forceRefresh = false): array
    {
        $kelasId = $this->resolveKelasId($codeOrId);

        // 1. Dapatkan data Feeder (dari cache atau baru ditarik)
        $pullResult = $this->pullKelasData($kelasId, $forceRefresh);
        $feederRows = $pullResult['data'] ?? [];

        // 2. Ambil data lokal SISKA
        $kelas = Kelas::with([
            'namaKelas',
            'matakuliah',
            'tahunAkademik',
            'programStudi',
            'kelasMahasiswa.krsDetail.krs.mahasiswa',
            'kelasMahasiswa.krsDetail.khsDetail',
            'kelasMahasiswa.krsDetail.matakuliah',
        ])->find($kelasId);

        if (! $kelas) {
            throw new Exception("Kelas ID {$kelasId} tidak ditemukan di SISKA.");
        }

        // Susun Indeks Feeder: Map key UPPER(nim)|UPPER(kode_mk) dan secondary map UPPER(nim)
        $feederIndex = [];
        $feederByNim = [];
        foreach ($feederRows as $fr) {
            $nim = strtoupper($fr['nim'] ?? '');
            $kodeMk = strtoupper($fr['kode_mata_kuliah'] ?? '');
            if ($nim && $kodeMk) {
                $feederIndex["{$nim}|{$kodeMk}"] = $fr;
            }
            if ($nim) {
                $feederByNim[$nim][] = $fr;
            }
        }

        $auditResults = [];
        $matchedFeederKeys = [];

        $countSesuai = 0;
        $countBerbeda = 0;
        $countTidakAdaDiFeeder = 0;
        $countNilaiSiskaKosong = 0;

        // 3. Bandingkan Mahasiswa SISKA dengan Feeder
        foreach ($kelas->kelasMahasiswa as $km) {
            $krsDetail = $km->krsDetail;
            $mhs = $krsDetail?->krs?->mahasiswa;
            $nim = strtoupper($mhs?->nim ?? $krsDetail?->krs?->nim ?? '');
            $kodeMk = strtoupper($krsDetail?->matakuliah?->kode_matakuliah ?? $kelas->matakuliah?->kode_matakuliah ?? '');
            $namaMk = $krsDetail?->matakuliah?->nama_matakuliah ?? $kelas->matakuliah?->nama_matakuliah ?? '';
            $namaMhs = $mhs?->nama_mahasiswa ?? '—';

            $khs = $krsDetail?->khsDetail;
            $siskaAngka = $khs?->nilai_akhir !== null ? (float) $khs->nilai_akhir : null;
            $siskaHuruf = $khs?->grade ? strtoupper(trim($khs->grade)) : null;
            $siskaIndeks = $khs?->score !== null ? (float) $khs->score : null;

            $primaryKey = "{$nim}|{$kodeMk}";
            $feederMatch = $feederIndex[$primaryKey] ?? null;

            $status = 'SESUAI';
            $catatan = null;
            $selisih = null;

            if ($feederMatch) {
                $matchedFeederKeys[$primaryKey] = true;
                $feederAngka = $feederMatch['nilai_angka'];
                $feederHuruf = $feederMatch['nilai_huruf'];
                $feederIndeks = $feederMatch['nilai_indeks'];

                if ($siskaAngka === null) {
                    $status = 'NILAI_SISKA_KOSONG';
                    $catatan = 'Mahasiswa ada di KRS/Kelas SISKA namun belum memiliki nilai KHS.';
                    $countNilaiSiskaKosong++;
                } elseif ($feederAngka === null) {
                    $status = 'FEEDER_BELUM_ADA_NILAI';
                    $catatan = 'Data mahasiswa ada di Feeder namun nilai angka di Feeder kosong.';
                    $countBerbeda++;
                } else {
                    $selisih = round(abs($siskaAngka - $feederAngka), 4);
                    if ($selisih <= 0.001) {
                        $status = 'SESUAI';
                        $countSesuai++;
                    } else {
                        $status = 'BERBEDA';
                        $catatan = "Selisih nilai angka: {$selisih} (SISKA: {$siskaAngka}, Feeder: {$feederAngka}).";
                        $countBerbeda++;
                    }
                }

                $auditResults[] = [
                    'nim' => $nim,
                    'nama_mahasiswa' => $namaMhs,
                    'kode_matakuliah' => $kodeMk,
                    'nama_matakuliah' => $namaMk,
                    'siska' => [
                        'nilai_angka' => $siskaAngka,
                        'nilai_huruf' => $siskaHuruf,
                        'nilai_indeks' => $siskaIndeks,
                    ],
                    'feeder' => [
                        'nilai_angka' => $feederAngka,
                        'nilai_huruf' => $feederHuruf,
                        'nilai_indeks' => $feederIndeks,
                    ],
                    'selisih_angka' => $selisih,
                    'status' => $status,
                    'catatan' => $catatan,
                ];

            } else {
                // Tidak cocok langsung: Periksa apakah ada kandidat null / fuzzy matching nama MK
                $fuzzyCandidate = null;
                $highestSimilarity = 0.0;

                if (isset($feederByNim[$nim])) {
                    foreach ($feederByNim[$nim] as $candidateRow) {
                        $candNama = $candidateRow['nama_mata_kuliah'] ?? '';
                        similar_text(
                            $this->normalizeMatakuliah($namaMk),
                            $this->normalizeMatakuliah($candNama),
                            $simPercent
                        );
                        if ($simPercent > $highestSimilarity && $simPercent >= 60.0) {
                            $highestSimilarity = $simPercent;
                            $fuzzyCandidate = $candidateRow;
                        }
                    }
                }

                if ($fuzzyCandidate) {
                    $matchedFeederKeys["{$nim}|".strtoupper($fuzzyCandidate['kode_mata_kuliah'] ?? '')] = true;
                    $status = 'KANDIDAT_NULL';
                    $catatan = "Kemiripan nama matakuliah {$highestSimilarity}% dengan MK Feeder: {$fuzzyCandidate['nama_mata_kuliah']} ({$fuzzyCandidate['kode_mata_kuliah']}).";
                    $countBerbeda++;

                    $auditResults[] = [
                        'nim' => $nim,
                        'nama_mahasiswa' => $namaMhs,
                        'kode_matakuliah' => $kodeMk,
                        'nama_matakuliah' => $namaMk,
                        'siska' => [
                            'nilai_angka' => $siskaAngka,
                            'nilai_huruf' => $siskaHuruf,
                            'nilai_indeks' => $siskaIndeks,
                        ],
                        'feeder' => [
                            'nilai_angka' => $fuzzyCandidate['nilai_angka'],
                            'nilai_huruf' => $fuzzyCandidate['nilai_huruf'],
                            'nilai_indeks' => $fuzzyCandidate['nilai_indeks'],
                            'kode_mata_kuliah' => $fuzzyCandidate['kode_mata_kuliah'],
                            'nama_mata_kuliah' => $fuzzyCandidate['nama_mata_kuliah'],
                        ],
                        'similarity_percent' => round($highestSimilarity, 1),
                        'selisih_angka' => null,
                        'status' => $status,
                        'catatan' => $catatan,
                    ];
                } else {
                    $status = 'TIDAK_ADA_DI_FEEDER';
                    $catatan = 'Mahasiswa terdaftar di SISKA namun tidak ditemukan di kelas Feeder.';
                    $countTidakAdaDiFeeder++;

                    $auditResults[] = [
                        'nim' => $nim,
                        'nama_mahasiswa' => $namaMhs,
                        'kode_matakuliah' => $kodeMk,
                        'nama_matakuliah' => $namaMk,
                        'siska' => [
                            'nilai_angka' => $siskaAngka,
                            'nilai_huruf' => $siskaHuruf,
                            'nilai_indeks' => $siskaIndeks,
                        ],
                        'feeder' => null,
                        'selisih_angka' => null,
                        'status' => $status,
                        'catatan' => $catatan,
                    ];
                }
            }
        }

        // Cek data Feeder yang tidak ada di SISKA
        $countTidakAdaDiSiska = 0;
        foreach ($feederRows as $fr) {
            $nim = strtoupper($fr['nim'] ?? '');
            $kodeMk = strtoupper($fr['kode_mata_kuliah'] ?? '');
            $primaryKey = "{$nim}|{$kodeMk}";

            if (! isset($matchedFeederKeys[$primaryKey])) {
                $countTidakAdaDiSiska++;
                $auditResults[] = [
                    'nim' => $nim,
                    'nama_mahasiswa' => $fr['nama_mahasiswa'] ?? '—',
                    'kode_matakuliah' => $kodeMk,
                    'nama_matakuliah' => $fr['nama_mata_kuliah'] ?? '—',
                    'siska' => null,
                    'feeder' => [
                        'nilai_angka' => $fr['nilai_angka'],
                        'nilai_huruf' => $fr['nilai_huruf'],
                        'nilai_indeks' => $fr['nilai_indeks'],
                    ],
                    'selisih_angka' => null,
                    'status' => 'TIDAK_ADA_DI_SISKA',
                    'catatan' => 'Data nilai tercatat di Feeder namun mahasiswa tidak terdaftar di kelas SISKA.',
                ];
            }
        }

        $totalSiska = count($kelas->kelasMahasiswa);
        $totalFeeder = count($feederRows);
        $persentase = $totalSiska > 0 ? round(($countSesuai / $totalSiska) * 100, 2) : 0;

        return [
            'kelas_info' => [
                'kelas_id' => $kelas->kelas_id,
                'code_kelas' => $kelas->toCode(),
                'nama_kelas' => $kelas->namaKelas?->nama_kelas ?? '—',
                'kode_matakuliah' => $kelas->matakuliah?->kode_matakuliah ?? '—',
                'nama_matakuliah' => $kelas->matakuliah?->nama_matakuliah ?? '—',
                'semester' => $kelas->semester,
                'tahun_akademik' => $kelas->tahunAkademik?->tahun_akademik ?? '—',
            ],
            'summary' => [
                'total_mahasiswa_siska' => $totalSiska,
                'total_mahasiswa_feeder' => $totalFeeder,
                'sesuai' => $countSesuai,
                'berbeda' => $countBerbeda,
                'tidak_ada_di_feeder' => $countTidakAdaDiFeeder,
                'tidak_ada_di_siska' => $countTidakAdaDiSiska,
                'nilai_siska_kosong' => $countNilaiSiskaKosong,
                'persentase_sinkron' => "{$persentase}%",
            ],
            'audit_results' => $auditResults,
            'cache_info' => [
                'source' => $pullResult['source'] ?? 'unknown',
                'pulled_at' => $pullResult['pulled_at'] ?? Carbon::now()->toIso8601String(),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO B: MAHASISWA / PETIKAN TRANSKRIP (Audit_petikan)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tarik dan simpan data transkrip mahasiswa dari Feeder ke cache sementara.
     */
    public function pullMahasiswaData(string $nim, bool $force = false): array
    {
        $cleanNim = strtoupper(trim($nim));
        $cacheKey = "feeder_pull_mhs_{$cleanNim}";

        if (! $force && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['source'] = 'cache';
            if (isset($cached['data']) && is_array($cached['data'])) {
                $cached['data'] = array_map([$this->feederService, 'sanitizeFeederRow'], $cached['data']);
            }
            return $cached;
        }

        $feederRows = $this->feederService->getTranskripMahasiswa($cleanNim);
        $feederRows = array_map([$this->feederService, 'sanitizeFeederRow'], $feederRows);

        $payload = [
            'nim' => $cleanNim,
            'source' => 'feeder_live',
            'pulled_at' => Carbon::now()->toIso8601String(),
            'total_rows' => count($feederRows),
            'data' => $feederRows,
        ];

        Cache::put($cacheKey, $payload, $this->cacheTtl);

        return $payload;
    }

    /**
     * Audit petikan transkrip mahasiswa di memori (konsolidasi retake & two-tier matching).
     */
    public function auditMahasiswa(string $nim, bool $forceRefresh = false): array
    {
        $cleanNim = strtoupper(trim($nim));

        // 1. Ambil data mahasiswa di SISKA
        $mahasiswa = Mahasiswa::with('programStudi')->where('nim', $cleanNim)->first();
        if (! $mahasiswa) {
            throw new Exception("Mahasiswa dengan NIM '{$cleanNim}' tidak ditemukan di SISKA.");
        }

        // 2. Dapatkan data transkrip Feeder
        $pullResult = $this->pullMahasiswaData($cleanNim, $forceRefresh);
        $feederRows = $pullResult['data'] ?? [];

        // 3. Ambil seluruh riwayat KRS & KHS Mahasiswa di SISKA
        $krsList = Krs::with([
            'krsDetail.matakuliah',
            'krsDetail.khsDetail',
            'tahunAkademik',
        ])->where('nim', $cleanNim)->get();

        // 4. KONSOLIDASI NILAI TERBAIK (Retake Handling)
        $consolidatedSiska = [];
        foreach ($krsList as $krs) {
            foreach ($krs->krsDetail as $kd) {
                $mk = $kd->matakuliah;
                if (! $mk) {
                    continue;
                }

                $kodeMk = strtoupper(trim($mk->kode_matakuliah));
                $namaMk = trim($mk->nama_matakuliah);
                $sks = (float) ($mk->sks ?? 0);
                $khs = $kd->khsDetail;

                $angka = $khs?->nilai_akhir !== null ? (float) $khs->nilai_akhir : null;
                $huruf = $khs?->grade ? strtoupper(trim($khs->grade)) : null;
                $indeks = $khs?->score !== null ? (float) $khs->score : null;

                $currentCandidate = [
                    'kode_matakuliah' => $kodeMk,
                    'nama_matakuliah' => $namaMk,
                    'sks' => $sks,
                    'nilai_angka' => $angka,
                    'nilai_huruf' => $huruf,
                    'nilai_indeks' => $indeks,
                    'semester' => $krs->semester,
                    'tahun_akademik' => $krs->tahunAkademik?->tahun_akademik ?? '—',
                ];

                if (! isset($consolidatedSiska[$kodeMk])) {
                    $consolidatedSiska[$kodeMk] = $currentCandidate;
                    $consolidatedSiska[$kodeMk]['is_retake'] = false;
                    $consolidatedSiska[$kodeMk]['history'] = [$currentCandidate];
                } else {
                    // Terjadi retake / matakuliah mengulang: pilih nilai terbaik (indeks tertinggi, lalu angka tertinggi)
                    $existing = $consolidatedSiska[$kodeMk];
                    $consolidatedSiska[$kodeMk]['is_retake'] = true;
                    $consolidatedSiska[$kodeMk]['history'][] = $currentCandidate;

                    $shouldReplace = false;
                    if ($indeks !== null && ($existing['nilai_indeks'] === null || $indeks > $existing['nilai_indeks'])) {
                        $shouldReplace = true;
                    } elseif ($indeks !== null && $indeks === $existing['nilai_indeks']) {
                        if ($angka !== null && ($existing['nilai_angka'] === null || $angka > $existing['nilai_angka'])) {
                            $shouldReplace = true;
                        }
                    }

                    if ($shouldReplace) {
                        $history = $consolidatedSiska[$kodeMk]['history'];
                        $consolidatedSiska[$kodeMk] = $currentCandidate;
                        $consolidatedSiska[$kodeMk]['is_retake'] = true;
                        $consolidatedSiska[$kodeMk]['history'] = $history;
                    }
                }
            }
        }

        // 5. Susun Indeks Feeder (Tier 1: Kode MK, Tier 2: Normalized Nama MK)
        $feederByCode = [];
        $feederByNormName = [];
        foreach ($feederRows as $idx => $fr) {
            $kode = strtoupper($fr['kode_mata_kuliah'] ?? '');
            $norm = $this->normalizeMatakuliah($fr['nama_mata_kuliah'] ?? '');
            if ($kode) {
                $feederByCode[$kode] = $fr;
            }
            if ($norm) {
                $feederByNormName[$norm] = $fr;
            }
        }

        $auditResults = [];
        $matchedFeederCodes = [];

        $countSesuai = 0;
        $countBerbeda = 0;
        $countTidakAdaDiFeeder = 0;
        $countKonversi = 0;

        // 6. Pencocokan 2 Tahap (Two-Tier Matching)
        foreach ($consolidatedSiska as $kodeMk => $siskaItem) {
            $feederMatch = null;
            $tierMatched = null;

            // Tier 1: Exact kode_matakuliah
            if (isset($feederByCode[$kodeMk])) {
                $feederMatch = $feederByCode[$kodeMk];
                $tierMatched = 'Tier-1 (Kode MK)';
            } else {
                // Tier 2: Fallback Normalized Nama Matakuliah
                $normName = $this->normalizeMatakuliah($siskaItem['nama_matakuliah']);
                if (isset($feederByNormName[$normName])) {
                    $feederMatch = $feederByNormName[$normName];
                    $tierMatched = 'Tier-2 (Nama MK Normalisasi)';
                }
            }

            if ($feederMatch) {
                $matchedCode = strtoupper($feederMatch['kode_mata_kuliah'] ?? '');
                $matchedFeederCodes[$matchedCode] = true;

                $feederAngka = $feederMatch['nilai_angka'];
                $feederHuruf = $feederMatch['nilai_huruf'];
                $feederIndeks = $feederMatch['nilai_indeks'];

                $status = 'SESUAI';
                $catatan = "Cocok via {$tierMatched}.";
                $selisih = null;

                if (! empty($feederMatch['is_konversi']) || ! empty($feederMatch['is_transfer'])) {
                    $status = 'KONVERSI_MBKM';
                    $catatan = 'Matakuliah hasil transfer atau konversi aktivitas MBKM di Feeder.';
                    $countKonversi++;
                } elseif ($siskaItem['nilai_angka'] === null) {
                    $status = 'SISKA_BELUM_ADA_NILAI';
                    $catatan = 'Di SISKA nilai belum diinput oleh dosen.';
                    $countBerbeda++;
                } elseif ($feederAngka === null) {
                    $status = 'FEEDER_BELUM_ADA_NILAI';
                    $catatan = 'Di Feeder nilai masih kosong.';
                    $countBerbeda++;
                } else {
                    $selisih = round(abs($siskaItem['nilai_angka'] - $feederAngka), 4);
                    if ($selisih <= 0.001) {
                        $status = 'SESUAI';
                        $countSesuai++;
                    } else {
                        $status = 'BERBEDA';
                        $catatan = "Selisih angka: {$selisih} (SISKA: {$siskaItem['nilai_angka']}, Feeder: {$feederAngka}).";
                        $countBerbeda++;
                    }
                }

                $auditResults[] = [
                    'kode_matakuliah' => $kodeMk,
                    'nama_matakuliah' => $siskaItem['nama_matakuliah'],
                    'sks' => $siskaItem['sks'],
                    'is_retake' => $siskaItem['is_retake'],
                    'tier_matching' => $tierMatched,
                    'siska' => [
                        'nilai_angka' => $siskaItem['nilai_angka'],
                        'nilai_huruf' => $siskaItem['nilai_huruf'],
                        'nilai_indeks' => $siskaItem['nilai_indeks'],
                    ],
                    'feeder' => [
                        'kode_matakuliah' => $feederMatch['kode_mata_kuliah'] ?? $kodeMk,
                        'nama_mata_kuliah' => $feederMatch['nama_mata_kuliah'] ?? $siskaItem['nama_matakuliah'],
                        'nama_kelas' => $feederMatch['nama_kelas'] ?? $feederMatch['nama_kelas_kuliah'] ?? null,
                        'sumber_feeder' => $feederMatch['sumber_feeder'] ?? 'feeder',
                        'nilai_angka' => $feederAngka,
                        'nilai_huruf' => $feederHuruf,
                        'nilai_indeks' => $feederIndeks,
                    ],
                    'selisih_angka' => $selisih,
                    'status' => $status,
                    'catatan' => $catatan,
                ];

            } else {
                $status = 'TIDAK_ADA_DI_FEEDER';
                $catatan = 'Matakuliah lulus di SISKA namun belum tercatat di transkrip Feeder.';
                $countTidakAdaDiFeeder++;

                $auditResults[] = [
                    'kode_matakuliah' => $kodeMk,
                    'nama_matakuliah' => $siskaItem['nama_matakuliah'],
                    'sks' => $siskaItem['sks'],
                    'is_retake' => $siskaItem['is_retake'],
                    'tier_matching' => null,
                    'siska' => [
                        'nilai_angka' => $siskaItem['nilai_angka'],
                        'nilai_huruf' => $siskaItem['nilai_huruf'],
                        'nilai_indeks' => $siskaItem['nilai_indeks'],
                    ],
                    'feeder' => null,
                    'selisih_angka' => null,
                    'status' => $status,
                    'catatan' => $catatan,
                ];
            }
        }

        // 7. Matakuliah di Feeder yang tidak ada di SISKA
        $countTidakAdaDiSiska = 0;
        foreach ($feederRows as $fr) {
            $kode = strtoupper($fr['kode_mata_kuliah'] ?? '');
            if (! isset($matchedFeederCodes[$kode])) {
                $countTidakAdaDiSiska++;
                $auditResults[] = [
                    'kode_matakuliah' => $kode,
                    'nama_matakuliah' => $fr['nama_mata_kuliah'] ?? '—',
                    'sks' => $fr['sks'] ?? null,
                    'is_retake' => false,
                    'tier_matching' => null,
                    'siska' => null,
                    'feeder' => [
                        'kode_matakuliah' => $kode,
                        'nama_mata_kuliah' => $fr['nama_mata_kuliah'] ?? '—',
                        'nama_kelas' => $fr['nama_kelas'] ?? $fr['nama_kelas_kuliah'] ?? null,
                        'sumber_feeder' => $fr['sumber_feeder'] ?? 'feeder',
                        'nilai_angka' => $fr['nilai_angka'],
                        'nilai_huruf' => $fr['nilai_huruf'],
                        'nilai_indeks' => $fr['nilai_indeks'],
                    ],
                    'selisih_angka' => null,
                    'status' => 'TIDAK_ADA_DI_SISKA',
                    'catatan' => 'Tercatat di transkrip Feeder namun tidak ditemukan di riwayat KRS/KHS SISKA.',
                ];
            }
        }

        $totalSiska = count($consolidatedSiska);
        $totalFeeder = count($feederRows);
        $persentase = $totalSiska > 0 ? round(($countSesuai / $totalSiska) * 100, 2) : 0;

        return [
            'mahasiswa_info' => [
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $mahasiswa->nama_mahasiswa,
                'program_studi' => $mahasiswa->programStudi?->nama_program_studi ?? '—',
                'status' => $mahasiswa->status,
            ],
            'summary' => [
                'total_matakuliah_siska' => $totalSiska,
                'total_matakuliah_feeder' => $totalFeeder,
                'sesuai' => $countSesuai,
                'berbeda' => $countBerbeda,
                'tidak_ada_di_feeder' => $countTidakAdaDiFeeder,
                'tidak_ada_di_siska' => $countTidakAdaDiSiska,
                'konversi_mbkm' => $countKonversi,
                'persentase_sinkron' => "{$persentase}%",
            ],
            'audit_results' => $auditResults,
            'cache_info' => [
                'source' => $pullResult['source'] ?? 'unknown',
                'pulled_at' => $pullResult['pulled_at'] ?? Carbon::now()->toIso8601String(),
            ],
        ];
    }
}

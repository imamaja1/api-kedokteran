<?php

namespace Database\Seeders;

use App\Models\ApiWorkflow;
use Illuminate\Database\Seeder;

class ApiWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataFile = base_path('code-update/doc/api_workflows.php');
        $workflows = file_exists($dataFile)
            ? (require $dataFile)['workflows'] ?? []
            : [];

        $order = 1;
        foreach ($workflows as $wf) {
            ApiWorkflow::updateOrCreate(
                ['id' => $wf['id']],
                [
                    'title'      => $wf['title'],
                    'badge'      => $wf['badge'] ?? null,
                    'icon'       => $wf['icon'] ?? 'route',
                    'summary'    => $wf['summary'],
                    'roles'      => $wf['roles'] ?? [],
                    'rules'      => $wf['rules'] ?? [],
                    'phases'     => $wf['phases'] ?? [],
                    'sort_order' => $order++,
                    'is_active'  => true,
                ]
            );
        }

        // Pastikan workflow penilaian-bertingkat selalu sinkron dengan endpoint terbaru
        $wfPenilaian = ApiWorkflow::find('penilaian-bertingkat');
        if ($wfPenilaian) {
            $phases = [];
            foreach ($wfPenilaian->phases as $phase) {
                if (str_contains($phase['phase'] ?? '', 'Fase 2')) {
                    $phase['endpoints'] = [
                        [
                            'method' => 'GET',
                            'url'    => '/api/staff/assessment/dosen/check',
                            'desc'   => 'Pre-check kesiapan kelas & template (anti-404)',
                        ],
                        [
                            'method' => 'POST',
                            'url'    => '/api/staff/assessment/dosen',
                            'desc'   => 'Kaitkan template aktif ke kelas kuliah',
                        ],
                        [
                            'method' => 'GET',
                            'url'    => '/api/staff/assessment/dosen/assignments',
                            'desc'   => 'Lihat pohon hirarki penugasan & dosen kelas (auto-create)',
                        ],
                        [
                            'method' => 'PUT',
                            'url'    => '/api/staff/assessment/dosen/assign',
                            'desc'   => 'Tugaskan dosen ke node kategori & root',
                        ],
                    ];
                    $phase['description'] = 'Melakukan pre-check kesiapan kelas (anti-404), membuka kelas kuliah, mengaitkan Template aktif ke kelas (auto-create jika template ada), dan memetakan Dosen Pengajar ke node kategori/root.';
                }

                if (str_contains($phase['phase'] ?? '', 'Fase 4') || str_contains($phase['phase'] ?? '', 'Fase 5')) {
                    continue;
                }

                $phases[] = $phase;
            }

            $phases[] = [
                'actor'       => 'Dosen Pengampu & Koordinator Kelas',
                'phase'       => 'Fase 4: Validasi & Revisi Komponen Nilai di Kelas (Dosen)',
                'endpoints'   => [
                    [
                        'method' => 'POST',
                        'url'    => '/api/dosen/assessment-dosen/validasi',
                        'desc'   => 'Validasi komponen nilai per mahasiswa di kelas',
                    ],
                    [
                        'method' => 'POST',
                        'url'    => '/api/dosen/assessment-dosen/validasi-bulk',
                        'desc'   => 'Validasi massal komponen nilai sekelas / per group',
                    ],
                    [
                        'method' => 'POST',
                        'url'    => '/api/dosen/assessment-dosen/revisi',
                        'desc'   => 'Kembalikan nilai komponen mahasiswa untuk revisi',
                    ],
                ],
                'description' => 'Dosen pengampu memvalidasi atau merevisi nilai komponen per mahasiswa di kelasnya tanpa langsung menerbitkan nilai ke KHS.',
            ];

            $phases[] = [
                'actor'       => 'Ketua Program Studi (Kaprodi) & Mahasiswa',
                'phase'       => 'Fase 5: Pengesahan Akhir ke KHS oleh Kaprodi',
                'endpoints'   => [
                    [
                        'method' => 'GET',
                        'url'    => '/api/dosen/kaprodi/penilaian/mahasiswa',
                        'desc'   => 'Review daftar nilai akhir mahasiswa sekelas',
                    ],
                    [
                        'method' => 'POST',
                        'url'    => '/api/dosen/kaprodi/penilaian/validasi',
                        'desc'   => 'Validasi final Kaprodi untuk menerbitkan nilai ke KHS',
                    ],
                    [
                        'method' => 'POST',
                        'url'    => '/api/dosen/kaprodi/penilaian/revisi',
                        'desc'   => 'Batalkan/revisi pengesahan KHS jika ada koreksi',
                    ],
                    [
                        'method' => 'GET',
                        'url'    => '/api/mhs/khs',
                        'desc'   => 'Mahasiswa melihat nilai akhir & huruf mutu resmi',
                    ],
                ],
                'description' => 'Kaprodi memeriksa rekap nilai kelas dan melakukan pengesahan khusus agar nilai akhir serta grade resmi diterbitkan ke KHS mahasiswa.',
            ];

            $wfPenilaian->phases = $phases;
            $wfPenilaian->save();
        }
    }
}

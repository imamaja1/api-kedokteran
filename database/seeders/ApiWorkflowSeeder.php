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
            $phases = $wfPenilaian->phases;
            // Update Phase 2
            foreach ($phases as &$phase) {
                if (str_contains($phase['phase'], 'Fase 2')) {
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
            }
            $wfPenilaian->phases = $phases;
            $wfPenilaian->save();
        }
    }
}

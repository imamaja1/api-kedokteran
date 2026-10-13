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
    }
}

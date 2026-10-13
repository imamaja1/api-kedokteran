<?php

namespace App\Http\Controllers;

use App\Models\ApiSection;
use App\Models\ApiWorkflow;

class DocsController extends Controller
{
    public function index()
    {
        $sections = ApiSection::with([
            'endpoints' => function ($q) {
                $q->orderBy('sort_order')->with([
                    'parameters' => fn($pq) => $pq->orderBy('sort_order')->with('sourceEndpoint.section')
                ]);
            }
        ])->orderBy('sort_order')->get();

        $workflows = [];
        try {
            $workflows = ApiWorkflow::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $workflows = [];
        }

        if (empty($workflows)) {
            $fallbackFile = base_path('code-update/doc/api_workflows.php');
            if (file_exists($fallbackFile)) {
                $workflows = (require $fallbackFile)['workflows'] ?? [];
            }
        }

        return view('docs.index', compact('sections', 'workflows'));
    }
    public function tester()
    {
        return view('docs.tester');
    }
}

<?php

namespace App\Service\Assessment;

use App\Models\AssessmentTemplate;
use App\Models\StudentScore;
use Illuminate\Support\Facades\DB;

class ScoreCalculationService
{
    public function __construct(
        private readonly TreeTraversalService $treeService,
    ) {}

    /**
     * Calculate final score dengan batch query (1 query all scores, recursive in-memory)
     * Sebelumnya: N+1 query per input node
     * Sekarang: 1 query batch + recursive calculation in memory
     */
    public function calculateFinalScore(AssessmentTemplate $template, string $nim): ?float
    {
        $scores = $this->loadScores($template, $nim);
        $rootKey = $template->structure['key'];

        return $this->calculateNodeScoreFromMap($template, $scores, $rootKey);
    }

    /**
     * Calculate node score from preloaded map (no DB queries)
     */
    public function calculateNodeScoreFromMap(AssessmentTemplate $template, array $scores, string $nodeKey): ?float
    {
        $node = $this->treeService->findNode($template, $nodeKey);

        if (! $node) {
            return null;
        }

        // Input node: return stored score from map
        if (($node['type'] ?? null) === 'input') {
            return $scores[$nodeKey] ?? null;
        }

        // Category/Root: calculate from children
        if (empty($node['children'])) {
            return null;
        }

        $totalScore = 0;
        foreach ($node['children'] as $child) {
            $childScore = $this->calculateNodeScoreFromMap($template, $scores, $child['key']);

            if ($childScore !== null) {
                $totalScore += $childScore * ($child['weight'] / 100);
            }
        }

        return $totalScore;
    }

    /**
     * Get all scores for student dengan batch query
     * 1 query untuk semua scores + recursive calculation in memory
     */
    public function getAllScoresForStudent(AssessmentTemplate $template, string $nim): array
    {
        $nodes = DB::table('assessment_node_indexes')
            ->where('template_id', $template->id)
            ->get();

        $scores = $this->loadScores($template, $nim);

        $result = [];

        foreach ($nodes as $node) {
            $result[$node->node_key] = [
                'node_name' => $node->node_name,
                'path' => $node->path,
                'is_input' => $node->is_input,
                'weight' => $node->weight,
                'score' => $this->calculateNodeScoreFromMap($template, $scores, $node->node_key),
            ];
        }

        return $result;
    }

    /**
     * Load all scores for student in ONE batch query
     */
    private function loadScores(AssessmentTemplate $template, string $nim): array
    {
        $rows = StudentScore::where('template_id', $template->id)
            ->where('nim', $nim)
            ->select('node_key', 'score')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->node_key] = (float) $row->score;
        }

        return $map;
    }

    public function getCalculationBreakdown(AssessmentTemplate $template, string $nim, string $nodeKey): array
    {
        $node = $this->treeService->findNode($template, $nodeKey);

        if (! $node) {
            return [];
        }

        $scores = $this->loadScores($template, $nim);

        $breakdown = [
            'node_key' => $nodeKey,
            'node_name' => $node['name'],
            'type' => $node['type'] ?? 'category',
            'final_score' => $this->calculateNodeScoreFromMap($template, $scores, $nodeKey),
            'components' => [],
        ];

        if (! empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $childScore = $this->calculateNodeScoreFromMap($template, $scores, $child['key']);

                $breakdown['components'][] = [
                    'key' => $child['key'],
                    'name' => $child['name'],
                    'weight_pct' => $child['weight'],
                    'score' => $childScore,
                    'weighted_contribution' => $childScore !== null ? $childScore * ($child['weight'] / 100) : null,
                ];
            }
        }

        return $breakdown;
    }
}

<?php

namespace App\Service\Assessment;

use App\Models\AssessmentTemplate;
use Illuminate\Support\Collection;

class AssessmentTreeBuilderService
{
    /**
     * Build assessment tree with scores attached to each node.
     */
    public function buildTreeWithScores(AssessmentTemplate $template, Collection $scores): array
    {
        return $this->attachScoresToNode($template->structure, $scores);
    }

    /**
     * Recursively attach scores to tree nodes.
     */
    private function attachScoresToNode(array $node, Collection $scores): array
    {
        $result = [
            'key' => $node['key'],
            'name' => $node['name'],
            'weight' => $node['weight'],
            'type' => $node['type'] ?? 'category',
        ];

        // Jika leaf node (input type), tambahkan score
        if (($node['type'] ?? null) === 'input') {
            $score = $scores->get($node['key']);
            $result['score'] = $score?->score ?? null;
            $result['dosen_kode_dosen'] = $score?->dosen_kode_dosen;
        }

        // Jika ada children, proses rekursif
        if (! empty($node['children'])) {
            $result['children'] = array_map(
                fn ($child) => $this->attachScoresToNode($child, $scores),
                $node['children']
            );

            // Hitung score kategori jika semua children sudah input
            if (($node['type'] ?? null) !== 'input') {
                $leafScores = $this->extractLeafScoresFromChildren($result['children']);
                $calculatedScore = $this->calculateCategoryScore($result['children']);
                $result['calculated_score'] = $calculatedScore;
                $result['filled_count'] = count(array_filter($leafScores, fn ($s) => $s !== null));
                $result['total_nodes'] = count($leafScores);
            }
        }

        return $result;
    }

    /**
     * Extract all leaf scores from children recursively.
     */
    private function extractLeafScoresFromChildren(array $children): array
    {
        $scores = [];

        foreach ($children as $child) {
            if (($child['type'] ?? null) === 'input') {
                $scores[] = $child['score'] ?? null;
            } elseif (! empty($child['children'])) {
                $scores = array_merge($scores, $this->extractLeafScoresFromChildren($child['children']));
            }
        }

        return $scores;
    }

    /**
     * Calculate weighted category score from children.
     */
    private function calculateCategoryScore(array $children): ?float
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($children as $child) {
            $childScore = null;

            if (isset($child['score']) && $child['score'] !== null) {
                $childScore = $child['score'];
            } elseif (isset($child['calculated_score']) && $child['calculated_score'] !== null) {
                $childScore = $child['calculated_score'];
            }

            if ($childScore !== null) {
                $totalScore += $childScore * ($child['weight'] / 100);
                $totalWeight += $child['weight'];
            }
        }

        return $totalWeight > 0 ? round($totalScore, 1) : null;
    }
}

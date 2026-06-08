<?php

namespace App\Service\Assessment;

use App\Models\AssessmentTemplate;

class TreeTraversalService
{
    public function buildTree(AssessmentTemplate $template): array
    {
        return $this->treeToArray($template->structure);
    }

    public function findNode(AssessmentTemplate $template, string $nodeKey): ?array
    {
        return $this->searchNode($template->structure, $nodeKey);
    }

    public function getLeafNodes(AssessmentTemplate $template): array
    {
        $leaves = [];
        $this->collectLeaves($template->structure, $leaves);
        return $leaves;
    }

    public function getPath(AssessmentTemplate $template, string $nodeKey): array
    {
        $path = [];
        $this->findPath($template->structure, $nodeKey, [], $path);
        return $path;
    }

    // ==================== Private ====================

    private function treeToArray(array $node): array
    {
        $result = [
            'key' => $node['key'],
            'name' => $node['name'],
            'weight' => $node['weight'],
            'type' => $node['type'] ?? 'category',
        ];

        if (!empty($node['children'])) {
            $result['children'] = array_map(fn($child) => $this->treeToArray($child), $node['children']);
        }

        return $result;
    }

    private function searchNode(array $node, string $targetKey): ?array
    {
        if ($node['key'] === $targetKey) {
            return $node;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $found = $this->searchNode($child, $targetKey);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function collectLeaves(array $node, array &$leaves): void
    {
        if ($node['type'] === 'input' || empty($node['children'])) {
            $leaves[] = $node;
            return;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->collectLeaves($child, $leaves);
            }
        }
    }

    private function findPath(array $node, string $targetKey, array $currentPath, &$resultPath): bool
    {
        $currentPath[] = $node;

        if ($node['key'] === $targetKey) {
            $resultPath = $currentPath;
            return true;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                if ($this->findPath($child, $targetKey, $currentPath, $resultPath)) {
                    return true;
                }
            }
        }

        return false;
    }
}

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

    public function getAssignableNodes(AssessmentTemplate $template, array $assignedMap = []): array
    {
        $nodes = [];
        $visitedKeys = [];
        if (!empty($template->structure)) {
            $this->collectAssignableNodes($template->structure, $assignedMap, 0, $nodes, $visitedKeys);
        }
        return $nodes;
    }

    public function buildTreeWithAssignments(AssessmentTemplate $template, array $assignedMap = []): ?array
    {
        if (empty($template->structure)) {
            return null;
        }
        return $this->traverseTreeWithAssignments($template->structure, $assignedMap, 0);
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
        if (($node['type'] ?? null) === 'input' || empty($node['children'])) {
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

    private function collectAssignableNodes(array $node, array $assignedMap, int $depth, array &$nodes, array &$visitedKeys): void
    {
        $key    = $node['key'] ?? '';
        $name   = $node['name'] ?? '';
        $weight = $node['weight'] ?? 0;
        $type   = $node['type'] ?? ($depth === 0 ? 'root' : 'category');
        $isAssignable = ($type !== 'input');

        $assigned = $assignedMap[$key] ?? null;

        $subComponents = [];
        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                if (($child['type'] ?? '') === 'input') {
                    $subComponents[] = [
                        'key'    => $child['key'] ?? '',
                        'name'   => $child['name'] ?? '',
                        'weight' => $child['weight'] ?? 0,
                    ];
                }
            }
        }

        if ($isAssignable && !empty($key) && !isset($visitedKeys[$key])) {
            $visitedKeys[$key] = true;
            $nodes[] = [
                'node_key'       => $key,
                'name'           => $name,
                'type'           => $type,
                'weight'         => $weight,
                'is_assigned'    => !is_null($assigned),
                'assigned_dosen' => $assigned,
                'sub_components' => $subComponents,
            ];
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->collectAssignableNodes($child, $assignedMap, $depth + 1, $nodes, $visitedKeys);
            }
        }
    }

    private function traverseTreeWithAssignments(array $node, array $assignedMap, int $depth): array
    {
        $key    = $node['key'] ?? '';
        $name   = $node['name'] ?? '';
        $weight = $node['weight'] ?? 0;
        $type   = $node['type'] ?? ($depth === 0 ? 'root' : 'category');
        $isAssignable = ($type !== 'input');

        $assigned = $assignedMap[$key] ?? null;

        $children = [];
        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $children[] = $this->traverseTreeWithAssignments($child, $assignedMap, $depth + 1);
            }
        }

        return [
            'key'            => $key,
            'name'           => $name,
            'type'           => $type,
            'weight'         => $weight,
            'is_assignable'  => $isAssignable,
            'assigned_dosen' => $assigned,
            'children'       => $children,
        ];
    }
}

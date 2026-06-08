<?php

namespace App\Service\Assessment;

use App\Models\AssessmentNodeIndex;
use App\Models\AssessmentTemplate;

class TemplateIndexerService
{
    public function indexTemplate(AssessmentTemplate $template): void
    {
        AssessmentNodeIndex::where('template_id', $template->id)->delete();

        $structure = $template->structure;
        $nodes = [];
        $this->flattenNode($structure, null, '', 0, $nodes);

        foreach ($nodes as $node) {
            AssessmentNodeIndex::create(array_merge($node, ['template_id' => $template->id]));
        }
    }

    private function flattenNode(array $node, ?string $parentKey, string $path, int $level, array &$nodes): void
    {
        $currentPath = empty($path) ? $node['key'] : $path . '.' . $node['key'];

        $nodes[] = [
            'node_key' => $node['key'],
            'parent_key' => $parentKey,
            'node_name' => $node['name'],
            'path' => $currentPath,
            'level' => $level,
            'weight' => $node['weight'],
            'is_input' => ($node['type'] === 'input'),
            'type' => $node['type'] ?? 'category',
        ];

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->flattenNode($child, $node['key'], $currentPath, $level + 1, $nodes);
            }
        }
    }
}

<?php

namespace App\Service\Assessment;

class TemplateValidatorService
{
    public function validate(array $structure): array
    {
        $errors = [];

        if (empty($structure)) {
            return ['valid' => false, 'errors' => ['Structure cannot be empty']];
        }

        if (!isset($structure['name']) || !isset($structure['weight'])) {
            return ['valid' => false, 'errors' => ['Root must have name and weight']];
        }

        if ($structure['weight'] != 100) {
            $errors[] = 'Root weight must be exactly 100';
        }

        $keys = [];
        $this->validateNode($structure, $errors, $keys);

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validateNode(array $node, &$errors, &$keys, $path = ''): void
    {
        if (empty($node['name'])) {
            $errors[] = "Node missing name at path: {$path}";
            return;
        }

        // Key will be auto-generated from name if empty, so only check if provided
        if (!empty($node['key'])) {
            if (isset($keys[$node['key']])) {
                $errors[] = "Duplicate key: {$node['key']}";
                return;
            }
            $keys[$node['key']] = true;
        }

        if (!is_numeric($node['weight']) || $node['weight'] < 0 || $node['weight'] > 100) {
            $nodeName = $node['key'] ?? $node['name'];
            $errors[] = "Invalid weight for {$nodeName}: must be 0-100";
        }

        if (($node['type'] ?? null) === 'input' && !empty($node['children'])) {
            $nodeName = $node['key'] ?? $node['name'];
            $errors[] = "Input node '{$nodeName}' cannot have children";
        }

        if (!empty($node['children'])) {
            $totalWeight = 0;

            foreach ($node['children'] as $child) {
                $totalWeight += $child['weight'] ?? 0;
                $this->validateNode($child, $errors, $keys, $path . '.' . $node['key']);
            }

            if (abs($totalWeight - 100) > 0.01) {
                $nodeName = $node['key'] ?? $node['name'];
                $errors[] = "Total weight for '{$nodeName}' is {$totalWeight}, must be 100";
            }
        }
    }
}

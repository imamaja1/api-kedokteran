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

        if (!isset($structure['key']) || !isset($structure['name']) || !isset($structure['weight'])) {
            return ['valid' => false, 'errors' => ['Root must have key, name, and weight']];
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
        if (empty($node['key']) || empty($node['name'])) {
            $errors[] = "Node missing key or name at path: {$path}";
            return;
        }

        if (isset($keys[$node['key']])) {
            $errors[] = "Duplicate key: {$node['key']}";
            return;
        }
        $keys[$node['key']] = true;

        if (!is_numeric($node['weight']) || $node['weight'] < 0 || $node['weight'] > 100) {
            $errors[] = "Invalid weight for {$node['key']}: must be 0-100";
        }

        if ($node['type'] === 'input' && !empty($node['children'])) {
            $errors[] = "Input node '{$node['key']}' cannot have children";
        }

        if (!empty($node['children'])) {
            $totalWeight = 0;

            foreach ($node['children'] as $child) {
                $totalWeight += $child['weight'] ?? 0;
                $this->validateNode($child, $errors, $keys, $path . '.' . $node['key']);
            }

            if (abs($totalWeight - 100) > 0.01) {
                $errors[] = "Total weight for '{$node['key']}' is {$totalWeight}, must be 100";
            }
        }
    }
}

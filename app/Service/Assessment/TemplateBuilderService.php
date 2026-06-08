<?php

namespace App\Service\Assessment;

use App\Models\AssessmentTemplate;
use Ramsey\Uuid\Uuid;

class TemplateBuilderService
{
    public function __construct(
        private readonly TemplateValidatorService $validatorService,
        private readonly TemplateIndexerService $indexerService,
    ) {}

    public function createTemplate(
        int $idMatakuliah,
        int $kodeKurikulumAngkatan,
        array $structure,
    ): AssessmentTemplate {
        $structure = $this->ensureKeysExist($structure);
        $validation = $this->validatorService->validate($structure);

        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'Invalid structure: ' . implode(', ', $validation['errors'])
            );
        }

        $template = AssessmentTemplate::create([
            'id' => Uuid::uuid4(),
            'id_matakuliah' => $idMatakuliah,
            'kode_kurikulum_angkatan' => $kodeKurikulumAngkatan,
            'versi' => 1,
            'structure' => $structure,
            'is_active' => true,
        ]);

        $this->indexerService->indexTemplate($template);

        return $template;
    }

    public function updateTemplate(
        AssessmentTemplate $template,
        array $structure,
    ): AssessmentTemplate {
        $structure = $this->ensureKeysExist($structure);
        $validation = $this->validatorService->validate($structure);

        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'Invalid structure: ' . implode(', ', $validation['errors'])
            );
        }

        $template->update(['is_active' => false]);

        $newTemplate = AssessmentTemplate::create([
            'id' => Uuid::uuid4(),
            'id_matakuliah' => $template->id_matakuliah,
            'kode_kurikulum_angkatan' => $template->kode_kurikulum_angkatan,
            'versi' => $template->versi + 1,
            'structure' => $structure,
            'is_active' => true,
        ]);

        $this->indexerService->indexTemplate($newTemplate);

        return $newTemplate;
    }

    public function getVersions(
        int $idMatakuliah,
        int $kodeKurikulumAngkatan,
    ): array {
        return AssessmentTemplate::where('id_matakuliah', $idMatakuliah)
            ->where('kode_kurikulum_angkatan', $kodeKurikulumAngkatan)
            ->orderBy('versi', 'desc')
            ->get()
            ->toArray();
    }

    public function getActiveTemplate(
        int $idMatakuliah,
        int $kodeKurikulumAngkatan,
    ): ?AssessmentTemplate {
        return AssessmentTemplate::where('id_matakuliah', $idMatakuliah)
            ->where('kode_kurikulum_angkatan', $kodeKurikulumAngkatan)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Auto-generate keys from name if key is empty
     * "Kuis Online" → "kuis_online"
     */
    private function ensureKeysExist(array $node, string $parentKey = ''): array
    {
        if (empty($node['key']) && !empty($node['name'])) {
            $node['key'] = $this->generateSlug($node['name']);
        }

        if (empty($node['key']) && empty($node['name'])) {
            throw new \InvalidArgumentException(
                'Node must have either key or name property'
            );
        }

        if (!empty($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as &$child) {
                $child = $this->ensureKeysExist($child, $node['key']);
            }
            unset($child);
        }

        return $node;
    }

    private function generateSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);

        return trim($slug, '_');
    }
}

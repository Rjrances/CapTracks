<?php

namespace App\Services;

use App\Models\DefenseRubricTemplate;

class DefenseRubricService
{
    public function normalizeStage(?string $stage): string
    {
        $value = trim((string) $stage);

        return match ($value) {
            'proposal', '60', '100' => $value,
            '60%', '60 defense', '60% defense' => '60',
            '100%', '100 defense', '100% defense' => '100',
            default => 'proposal',
        };
    }

    public function getActiveTemplateForStage(?string $stage): ?DefenseRubricTemplate
    {
        return DefenseRubricTemplate::query()
            ->with('criteria')
            ->where('stage', $this->normalizeStage($stage))
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }
}


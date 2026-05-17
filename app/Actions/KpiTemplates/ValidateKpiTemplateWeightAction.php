<?php

namespace App\Actions\KpiTemplates;

use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use Illuminate\Validation\ValidationException;

class ValidateKpiTemplateWeightAction
{
    public function ensureDoesNotExceedOneHundred(
        KpiTemplate $template,
        string $incomingWeight,
        ?KpiTemplateItem $ignoringItem = null,
    ): void {
        $currentWeight = $this->getCurrentWeight($template, $ignoringItem);
        $nextWeight = $currentWeight + $this->normalizeToHundredths($incomingWeight);

        if ($nextWeight > 10000) {
            throw ValidationException::withMessages([
                'weight' => 'The total item weight for a KPI template may not exceed 100.',
            ]);
        }
    }

    public function ensureEqualsOneHundred(KpiTemplate $template): void
    {
        if ($this->getCurrentWeight($template) !== 10000) {
            throw ValidationException::withMessages([
                'template' => 'The total item weight must equal 100 before the template can be published.',
            ]);
        }
    }

    private function getCurrentWeight(KpiTemplate $template, ?KpiTemplateItem $ignoringItem = null): int
    {
        $query = $template->items();

        if ($ignoringItem !== null) {
            $query->whereKeyNot($ignoringItem->getKey());
        }

        return $this->normalizeToHundredths((string) $query->sum('weight'));
    }

    private function normalizeToHundredths(string $value): int
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return 0;
        }

        if (! str_contains($normalized, '.')) {
            return (int) $normalized * 100;
        }

        [$whole, $decimal] = explode('.', $normalized, 2);
        $decimal = str_pad(substr($decimal, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $decimal;
    }
}

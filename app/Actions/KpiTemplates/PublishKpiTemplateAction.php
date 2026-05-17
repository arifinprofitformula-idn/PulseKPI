<?php

namespace App\Actions\KpiTemplates;

use App\Models\KpiTemplate;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishKpiTemplateAction
{
    public function __construct(
        private readonly ValidateKpiTemplateWeightAction $validateWeight,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(KpiTemplate $template, ?Carbon $publishedAt = null): KpiTemplate
    {
        if (! $template->is_active) {
            throw ValidationException::withMessages([
                'template' => 'Inactive templates must be activated before publishing.',
            ]);
        }

        if (! $template->items()->exists()) {
            throw ValidationException::withMessages([
                'template' => 'A KPI template must have at least one item before publishing.',
            ]);
        }

        $this->validateWeight->ensureEqualsOneHundred($template);

        return DB::transaction(function () use ($template, $publishedAt): KpiTemplate {
            $template->forceFill([
                'published_at' => $publishedAt ?? now(),
            ])->save();

            $template = $template->refresh();

            $this->activityLogService->log('kpi_template.published', $template, [
                'template_id' => $template->getKey(),
                'new' => [
                    'published_at' => filled($template->published_at) ? (string) $template->published_at : null,
                    'is_active' => $template->is_active,
                ],
            ]);

            return $template;
        });
    }
}

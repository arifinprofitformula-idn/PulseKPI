<?php

namespace App\Http\Controllers;

use App\Models\KpiAssessmentItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KpiAssessmentEvidenceDownloadController extends Controller
{
    public function __invoke(KpiAssessmentItem $item): StreamedResponse
    {
        $item->loadMissing('assessment.assessor', 'assessment.employee');

        Gate::authorize('downloadEvidence', $item->assessment);

        abort_unless(filled($item->evidence_file_path), 404);

        $disk = config('pulsekpi.assessments.evidence_disk', 'local');
        abort_unless(Storage::disk($disk)->exists($item->evidence_file_path), 404);

        return Storage::disk($disk)->download(
            $item->evidence_file_path,
            $item->evidence_original_name ?? basename($item->evidence_file_path)
        );
    }
}

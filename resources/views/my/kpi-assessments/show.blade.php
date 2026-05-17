<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('My KPI Assessment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @can('exportPdf', $assessment)
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('my.kpi-assessments.export-pdf', $assessment) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white">
                            Export PDF
                        </button>
                    </form>
                </div>
            @endcan

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Period</p>
                            <p>{{ $assessment->assignment?->period?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Template</p>
                            <p>{{ $assessment->assignment?->template?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                            <p>{{ $assessment->status->label() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Assessor</p>
                            <p>{{ $assessment->assessor?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">KPI Score</p>
                            <p>{{ $assessment->kpi_score }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Attendance Score</p>
                            <p>{{ $assessment->attendance_score }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Final Score</p>
                            <p>{{ $assessment->final_score }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Grade</p>
                            <p>{{ $assessment->grade ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold">KPI Items</h3>
                        <div class="mt-4 space-y-4">
                            @foreach ($assessment->items as $item)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium">{{ $item->template_item_name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Weight: {{ $item->template_item_weight }}
                                                @if ($item->template_item_is_required)
                                                    | Required
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right text-sm">
                                            <p>Score: {{ $item->score ?? 'Not assessed' }}</p>
                                            <p>Weighted: {{ $item->weighted_score }}</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Description</p>
                                            <p>{{ $item->template_item_description ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Target</p>
                                            <p>{{ $item->template_item_target_description }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Data Source</p>
                                            <p>{{ $item->template_item_data_source ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Actual Value</p>
                                            <p>{{ $item->actual_value ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Evidence Note</p>
                                        <p>{{ $item->evidence_note ?? '-' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Evidence File</p>
                                        @if (filled($item->evidence_file_path))
                                            <a
                                                href="{{ route('kpi-assessment-items.evidence.download', ['item' => $item]) }}"
                                                class="text-primary-600 underline"
                                            >
                                                {{ $item->evidence_original_name ?? 'Download evidence' }}
                                            </a>
                                        @else
                                            <p>-</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Working Days</p>
                            <p>{{ $assessment->attendanceAdjustment?->working_days ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sick Days</p>
                            <p>{{ $assessment->attendanceAdjustment?->sick_days ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Permission Days</p>
                            <p>{{ $assessment->attendanceAdjustment?->permission_days ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Absent Days</p>
                            <p>{{ $assessment->attendanceAdjustment?->absent_days ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Leave Days</p>
                            <p>{{ $assessment->attendanceAdjustment?->leave_days ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Attendance Deduction</p>
                            <p>{{ $assessment->attendance_deduction }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Assessment Notes</p>
                        <p>{{ $assessment->notes ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

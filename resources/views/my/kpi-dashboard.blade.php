<x-app-layout>
    <x-slot name="title">My KPI</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">My KPI Dashboard</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Pantau progres, status assessment, dan hasil KPI Anda.
                </p>
            </div>
            @if ($latestAssessment)
                <a href="{{ route('my.kpi-assessments.show', $latestAssessment) }}"
                   class="btn-primary hidden sm:inline-flex gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Lihat Assessment Terkini
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Stat cards ───────────────────────────────────────────── --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 px-4 sm:px-0">

                {{-- Assignment --}}
                <div class="stat-card flex items-start gap-4">
                    <div class="stat-card-icon bg-brand-50 dark:bg-brand-900/20">
                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Assignment</p>
                        <p class="mt-1 text-base font-bold text-gray-900 dark:text-gray-100 truncate">
                            {{ $latestAssignment?->template?->name ?? '—' }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500 truncate">
                            {{ $latestAssignment?->period?->name ?? 'Belum ada assignment' }}
                        </p>
                    </div>
                </div>

                {{-- Status --}}
                <div class="stat-card flex items-start gap-4">
                    <div class="stat-card-icon bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</p>
                        <div class="mt-1.5">
                            @if ($latestAssessment)
                                @php
                                    $statusKey = strtolower($latestAssessment->status->value ?? $latestAssessment->status->name ?? '');
                                    $badgeClass = match ($statusKey) {
                                        'draft'     => 'badge-draft',
                                        'submitted' => 'badge-submitted',
                                        'reviewed'  => 'badge-reviewed',
                                        'approved'  => 'badge-approved',
                                        'rejected'  => 'badge-rejected',
                                        'locked'    => 'badge-locked',
                                        default     => 'badge-draft',
                                    };
                                @endphp
                                <span class="{{ $badgeClass }}">{{ $latestAssessment->status->label() }}</span>
                            @else
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Final Score --}}
                <div class="stat-card flex items-start gap-4">
                    <div class="stat-card-icon bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Final Score</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $latestAssessment?->final_score ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Grade --}}
                <div class="stat-card flex items-start gap-4">
                    <div class="stat-card-icon bg-violet-50 dark:bg-violet-900/20">
                        <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Grade</p>
                        <div class="mt-1.5">
                            @if ($latestAssessment?->grade)
                                @php
                                    $gradeClass = match (strtoupper($latestAssessment->grade)) {
                                        'A' => 'grade-a',
                                        'B' => 'grade-b',
                                        'C' => 'grade-c',
                                        default => 'grade-d',
                                    };
                                @endphp
                                <span class="{{ $gradeClass }} text-sm font-bold px-3 py-1">
                                    {{ $latestAssessment->grade }}
                                </span>
                            @else
                                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Assessment history table ─────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mx-4 sm:mx-0">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Riwayat Assessment</h3>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Hasil dan status assessment KPI Anda per periode.
                    </p>
                </div>

                @if ($recentAssessments->isEmpty())
                    {{-- Empty state --}}
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada assessment</p>
                        <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                            Assessment KPI Anda akan muncul di sini setelah periode dimulai.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Periode</th>
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Template</th>
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Final Score</th>
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Grade</th>
                                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide sr-only">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($recentAssessments as $assessment)
                                    @php
                                        $sk = strtolower($assessment->status->value ?? $assessment->status->name ?? '');
                                        $bc = match ($sk) {
                                            'draft'     => 'badge-draft',
                                            'submitted' => 'badge-submitted',
                                            'reviewed'  => 'badge-reviewed',
                                            'approved'  => 'badge-approved',
                                            'rejected'  => 'badge-rejected',
                                            'locked'    => 'badge-locked',
                                            default     => 'badge-draft',
                                        };
                                        $gc = match (strtoupper((string) ($assessment->grade ?? ''))) {
                                            'A' => 'grade-a',
                                            'B' => 'grade-b',
                                            'C' => 'grade-c',
                                            'D' => 'grade-d',
                                            default => null,
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="py-3.5 px-6 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $assessment->assignment?->period?->name ?? '—' }}
                                        </td>
                                        <td class="py-3.5 px-6 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $assessment->assignment?->template?->name ?? '—' }}
                                        </td>
                                        <td class="py-3.5 px-6">
                                            <span class="{{ $bc }}">{{ $assessment->status->label() }}</span>
                                        </td>
                                        <td class="py-3.5 px-6 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $assessment->final_score ?? '—' }}
                                        </td>
                                        <td class="py-3.5 px-6">
                                            @if ($assessment->grade && $gc)
                                                <span class="{{ $gc }}">{{ $assessment->grade }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-6 text-right">
                                            <a href="{{ route('my.kpi-assessments.show', $assessment) }}"
                                               class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 hover:underline">
                                                Detail
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif
            </div>

        </div>
    </div>
</x-app-layout>

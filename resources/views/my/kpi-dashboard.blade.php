<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('My KPI Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-6 lg:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Latest Assignment</p>
                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $latestAssignment?->template?->name ?? '-' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $latestAssignment?->period?->name ?? 'No KPI assignment yet' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Latest Assessment Status</p>
                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $latestAssessment?->status?->label() ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Latest Final Score</p>
                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $latestAssessment?->final_score ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Latest Grade</p>
                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $latestAssessment?->grade ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold">Recent Assessment History</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Your latest KPI assessment results and workflow progress.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                                    <th class="py-3 pe-4">Period</th>
                                    <th class="py-3 pe-4">Template</th>
                                    <th class="py-3 pe-4">Status</th>
                                    <th class="py-3 pe-4">Final Score</th>
                                    <th class="py-3 pe-4">Grade</th>
                                    <th class="py-3 pe-4">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                                @forelse ($recentAssessments as $assessment)
                                    <tr>
                                        <td class="py-3 pe-4">{{ $assessment->assignment?->period?->name ?? '-' }}</td>
                                        <td class="py-3 pe-4">{{ $assessment->assignment?->template?->name ?? '-' }}</td>
                                        <td class="py-3 pe-4">{{ $assessment->status->label() }}</td>
                                        <td class="py-3 pe-4">{{ $assessment->final_score }}</td>
                                        <td class="py-3 pe-4">{{ $assessment->grade ?? '-' }}</td>
                                        <td class="py-3 pe-4">
                                            <a href="{{ route('my.kpi-assessments.show', $assessment) }}" class="text-primary-600 underline">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                            No KPI assessments available yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

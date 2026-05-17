<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('PulseKPI Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <p>{{ __('Use the KPI workspace that matches your role and access level.') }}</p>

                    <div class="flex flex-wrap gap-3">
                        @if (auth()->user()?->can('access admin panel') || auth()->user()?->hasRole('Super Admin'))
                            <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white">
                                {{ __('Open KPI Dashboard') }}
                            </a>
                        @endif

                        <a href="{{ route('my.kpi-dashboard') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-100 dark:border-gray-600">
                            {{ __('Open My KPI') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

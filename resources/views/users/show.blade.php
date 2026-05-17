<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                        <p>{{ $user->email }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Employee Code</p>
                            <p>{{ $user->employee_code ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Employment Status</p>
                            <p>{{ $user->employment_status ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Division</p>
                            <p>{{ $user->division?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Department</p>
                            <p>{{ $user->department?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Position</p>
                            <p>{{ $user->position?->name ?? '-' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Supervisor</p>
                            <p>{{ $user->supervisor?->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

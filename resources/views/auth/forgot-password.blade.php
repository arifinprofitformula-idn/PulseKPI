<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Reset Password</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Masukkan email Anda dan kami akan mengirimkan tautan untuk membuat password baru.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300 font-medium" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email"
                          :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full py-2.5 text-sm">
            {{ __('Kirim Tautan Reset') }}
        </x-primary-button>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Sudah ingat password?
            <a href="{{ route('login') }}" class="text-brand-600 dark:text-brand-400 hover:underline font-medium">
                Masuk
            </a>
        </p>
    </form>

</x-guest-layout>

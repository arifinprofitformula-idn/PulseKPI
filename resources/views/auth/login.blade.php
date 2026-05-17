<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk ke akun Anda</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Gunakan email dan password yang terdaftar.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300 font-medium" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="text-gray-700 dark:text-gray-300 font-medium" />
                @if (Route::has('password.request'))
                    <a class="text-xs text-brand-600 dark:text-brand-400 hover:underline focus:outline-none"
                       href="{{ route('password.request') }}">
                        {{ __('Lupa password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1.5 w-full"
                          type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700
                          text-brand-500 shadow-sm focus:ring-brand-500 dark:focus:ring-brand-400
                          dark:focus:ring-offset-gray-800">
            <label for="remember_me" class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Ingat saya') }}
            </label>
        </div>

        <x-primary-button class="w-full py-2.5 text-sm">
            {{ __('Masuk') }}
        </x-primary-button>
    </form>

</x-guest-layout>

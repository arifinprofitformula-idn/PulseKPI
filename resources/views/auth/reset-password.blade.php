<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Buat Password Baru</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Masukkan password baru yang kuat untuk akun Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300 font-medium" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email"
                          :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password Baru')" class="text-gray-700 dark:text-gray-300 font-medium" />
            <x-text-input id="password" class="block mt-1.5 w-full"
                          type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" class="text-gray-700 dark:text-gray-300 font-medium" />
            <x-text-input id="password_confirmation" class="block mt-1.5 w-full"
                          type="password" name="password_confirmation"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full py-2.5 text-sm">
            {{ __('Simpan Password') }}
        </x-primary-button>
    </form>

</x-guest-layout>

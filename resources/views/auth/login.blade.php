<x-guest-layout>
    <x-auth-card
        title="Selamat datang kembali"
        description="Masuk untuk melanjutkan ke workspace Anda."
    >
        <x-auth-session-status class="mb-6" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Email --}}
            <div class="space-y-1.5">
                <x-input-label for="email" :value="__('Email')" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <x-text-input
                        id="email"
                        class="block w-full pl-10"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@perusahaan.com"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" />
            </div>

            {{-- Password --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="password" :value="__('Password')" />
                    @if (Route::has('password.request'))
                        <a
                            class="text-sm font-medium text-brand-600 transition hover:text-brand-700 hover:underline focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2"
                            href="{{ route('password.request') }}"
                        >
                            {{ __('Lupa password?') }}
                        </a>
                    @endif
                </div>

                <div class="relative" x-data="{ show: false }">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </span>
                    <x-text-input
                        id="password"
                        class="block w-full pl-10 pr-10"
                        :type="'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password Anda"
                        x-bind:type="show ? 'text' : 'password'"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600 focus:outline-none"
                        x-on:click="show = !show"
                        :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" />
            </div>

            {{-- Remember me --}}
            <label for="remember_me" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="mt-0.5 rounded border-slate-300 text-brand-500 shadow-sm focus:ring-brand-500"
                >
                <span class="min-w-0 space-y-0.5">
                    <span class="block text-sm font-medium text-slate-800">{{ __('Ingat saya') }}</span>
                    <span class="block text-xs leading-5 text-slate-500">Tetap masuk di perangkat ini untuk akses yang lebih cepat.</span>
                </span>
            </label>

            {{-- Submit --}}
            <div class="pt-1">
                <x-primary-button class="w-full justify-center py-3 text-[0.8125rem]">
                    {{ __('Masuk') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>

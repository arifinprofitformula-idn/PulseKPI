<x-guest-layout>
    <x-auth-card
        title="Buat password baru"
        :description="'Tetapkan password baru untuk melanjutkan akses ke ' . config('branding.name', config('app.name')) . '. Gunakan kombinasi yang kuat dan mudah Anda simpan dengan aman.'"
    >
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="space-y-2">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    class="block w-full"
                    type="email"
                    name="email"
                    :value="old('email', $request->email)"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="space-y-2">
                <x-input-label for="password" :value="__('Password Baru')" />
                <x-text-input
                    id="password"
                    class="block w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="space-y-2">
                <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                <x-text-input
                    id="password_confirmation"
                    class="block w-full"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password baru"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                Setelah password diperbarui, Anda bisa kembali masuk dan melanjutkan proses KPI sesuai akses akun Anda.
            </div>

            <x-primary-button class="w-full justify-center">
                {{ __('Simpan password baru') }}
            </x-primary-button>
        </form>
    </x-auth-card>
</x-guest-layout>

<x-guest-layout>
    <x-auth-card
        title="Reset password"
        :description="'Masukkan email akun ' . config('branding.name', config('app.name')) . ' Anda. Kami akan mengirimkan tautan aman untuk membuat password baru.'"
    >
        <x-auth-session-status class="mb-6" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    class="block w-full"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="rounded-2xl border border-brand-100 bg-brand-50/80 px-4 py-3 text-sm leading-6 text-brand-900">
                Pastikan Anda memakai email yang terdaftar agar tautan reset dikirim ke alamat yang benar.
            </div>

            <div class="space-y-3 pt-2">
                <x-primary-button class="w-full justify-center">
                    {{ __('Kirim tautan reset') }}
                </x-primary-button>

                <a
                    href="{{ route('login') }}"
                    class="pulse-button-secondary w-full justify-center"
                >
                    Kembali ke login
                </a>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>

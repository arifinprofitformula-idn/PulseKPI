<x-guest-layout>
    <x-auth-card
        title="Verifikasi email Anda"
        :description="'Sebelum mulai memakai ' . config('branding.name', config('app.name')) . ', konfirmasi alamat email Anda melalui tautan yang kami kirimkan.'"
    >
        @if (session('status') === 'verification-link-sent')
            <x-auth-session-status
                class="mb-6"
                :status="__('Tautan verifikasi baru sudah dikirim ke alamat email yang Anda daftarkan.')"
            />
        @endif

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-600">
                Periksa inbox dan folder spam Anda. Setelah email diverifikasi, akses ke fitur {{ config('branding.name', config('app.name')) }} akan terbuka sesuai peran akun Anda.
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <x-primary-button class="w-full justify-center">
                        {{ __('Kirim ulang email verifikasi') }}
                    </x-primary-button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-secondary-button type="submit" class="w-full justify-center">
                        {{ __('Keluar') }}
                    </x-secondary-button>
                </form>
            </div>
        </div>
    </x-auth-card>
</x-guest-layout>

<x-guest-layout>
    <x-auth-card
        title="Konfirmasi password"
        :description="'Untuk melindungi perubahan sensitif di ' . config('branding.name', config('app.name')) . ', masukkan kembali password Anda sebelum melanjutkan.'"
    >
        <div class="mb-6 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            Area ini memerlukan verifikasi tambahan karena berkaitan dengan keamanan akun Anda.
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input
                    id="password"
                    class="block w-full"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="Masukkan password Anda"
                />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-6 text-slate-500">
                    Verifikasi ini tidak mengubah password Anda.
                </p>

                <x-primary-button class="justify-center sm:min-w-44">
                    {{ __('Konfirmasi') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>

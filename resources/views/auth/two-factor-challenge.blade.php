<x-guest-layout>
    <div class="mb-6">
        <div class="text-xs font-bold uppercase tracking-widest text-orange-500">Account protection</div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Two-factor authentication</h1>
        <p class="mt-2 text-sm text-gray-600">Enter the six-digit code from your authenticator app. You can use one emergency recovery code instead.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="code" value="Authenticator code" />
            <x-text-input id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                pattern="[0-9]{6}" maxlength="6" class="mt-1 block w-full tracking-[0.35em]" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <details class="rounded-lg border border-gray-200 p-4">
            <summary class="cursor-pointer text-sm font-semibold text-gray-700">Use a recovery code</summary>
            <div class="mt-3">
                <x-input-label for="recovery_code" value="One-time recovery code" />
                <x-text-input id="recovery_code" name="recovery_code" autocomplete="one-time-code" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>
        </details>

        <x-primary-button class="w-full justify-center">Verify and continue</x-primary-button>
    </form>
</x-guest-layout>

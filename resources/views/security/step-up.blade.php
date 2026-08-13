<x-guest-layout>
    <div class="mb-6">
        <div class="text-xs font-bold uppercase tracking-widest text-orange-500">Sensitive action</div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Verify it is you</h1>
        <p class="mt-2 text-sm text-gray-600">Confirm your password and current authenticator code. Verification remains valid for {{ config('security.step_up_window_minutes', 15) }} minutes.</p>
    </div>
    <form method="POST" action="{{ route('security.step-up.store') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="password" value="Current password" />
            <x-text-input id="password" type="password" name="password" autocomplete="current-password" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="code" value="Six-digit authenticator code" />
            <x-text-input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" class="mt-1 block w-full tracking-[0.35em]" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <x-primary-button class="w-full justify-center">Verify</x-primary-button>
    </form>
</x-guest-layout>

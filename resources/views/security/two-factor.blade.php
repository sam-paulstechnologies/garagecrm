<x-guest-layout>
    <div class="space-y-6">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-orange-500">SayaraForce Security</div>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Protect your SayaraForce account</h1>
            <p class="mt-2 text-sm text-gray-600">Scan a QR code with any standards-compliant authenticator app, enter the six-digit code, then save your recovery codes.</p>
        </div>

        @if (session('status')) <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">{{ session('status') }}</div> @endif
        @if (session('warning')) <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{{ session('warning') }}</div> @endif

        <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
            <div>
                <div class="font-semibold text-gray-900">Two-factor authentication</div>
                <div class="text-sm text-gray-600">{{ $required ? 'Required for your role' : 'Optional for your role' }}</div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                {{ $enabled ? 'Enabled' : 'Not enabled' }}
            </span>
        </div>

        @if (! $passwordConfirmed && ! $enabled)
            <form method="GET" action="{{ route('password.confirm') }}">
                <input type="hidden" name="return_to" value="{{ route('security.two-factor.show', absolute: false) }}">
                <x-primary-button class="w-full justify-center">Confirm password to begin</x-primary-button>
            </form>
        @elseif (! $secretGenerated && ! $enabled)
            <form method="POST" action="{{ route('security.two-factor.enable') }}">
                @csrf
                <x-primary-button class="w-full justify-center">Generate secure setup QR</x-primary-button>
            </form>
        @elseif (! $enabled)
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                <div class="mx-auto inline-block rounded-lg bg-white p-3">{!! $qrCodeSvg !!}</div>
                <p class="mt-3 text-sm text-gray-600">Scan this QR code. The account is not protected until the code below is verified.</p>
            </div>
            <form method="POST" action="{{ route('security.two-factor.confirm') }}" class="space-y-3">
                @csrf
                <x-input-label for="code" value="Current six-digit code" />
                <x-text-input id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                    pattern="[0-9]{6}" maxlength="6" class="block w-full tracking-[0.35em]" required />
                <x-input-error :messages="$errors->get('code')" />
                <x-primary-button class="w-full justify-center">Confirm and enable</x-primary-button>
            </form>
        @else
            <div class="space-y-3">
                <a href="{{ route('security.step-up.show') }}" class="block rounded-lg border border-gray-300 px-4 py-3 text-center text-sm font-semibold text-gray-800">Complete recent security verification</a>
                <form method="POST" action="{{ route('security.two-factor.recovery-codes.regenerate') }}">
                    @csrf
                    <button class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-800">Regenerate recovery codes</button>
                </form>
                <form method="POST" action="{{ route('security.two-factor.disable') }}">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-700">Disable two-factor authentication</button>
                </form>
                <p class="text-xs text-gray-500">Sensitive actions require password and authenticator verification within the last {{ $stepUpMinutes }} minutes.</p>
            </div>
        @endif

        @if ($team->isNotEmpty())
            <div class="border-t border-gray-200 pt-5">
                <h2 class="font-semibold text-gray-900">Team security status</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($team as $member)
                        <div class="rounded-lg bg-gray-50 p-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span>{{ $member['name'] }} · {{ ucfirst($member['role']) }}</span>
                                <span>{{ $member['required'] ? 'Required' : 'Optional' }} · {{ $member['enabled'] ? 'Enabled' : 'Not enabled' }}</span>
                            </div>
                            @if ($member['can_reset'] && $member['enabled'])
                                <form method="POST" action="{{ route('security.two-factor.admin-reset', $member['id']) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="reason" required minlength="10" maxlength="500" placeholder="Verified recovery reason"
                                        class="min-w-0 flex-1 rounded border-gray-300 text-xs">
                                    <button class="rounded border border-red-200 px-3 py-1 text-xs font-semibold text-red-700">Reset 2FA</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-200 pt-4">
            @csrf
            <button class="text-sm font-semibold text-gray-600">Sign out</button>
        </form>
    </div>
</x-guest-layout>

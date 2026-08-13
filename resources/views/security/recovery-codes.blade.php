<x-guest-layout>
    <div class="space-y-5">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-orange-500">Emergency access</div>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Save your recovery codes</h1>
            <p class="mt-2 text-sm text-gray-600">Each code works once. Store them privately; SayaraForce support and administrators cannot retrieve them for you.</p>
        </div>

        <div id="recovery-codes" class="grid grid-cols-1 gap-2 rounded-xl bg-gray-950 p-5 font-mono text-sm text-white sm:grid-cols-2">
            @foreach ($recoveryCodes as $recoveryCode)<div>{{ $recoveryCode }}</div>@endforeach
        </div>

        <div class="flex gap-3 print:hidden">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('recovery-codes').innerText)" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">Copy</button>
            <button type="button" onclick="window.print()" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">Print</button>
        </div>

        <form method="POST" action="{{ route('security.two-factor.recovery-codes.acknowledge') }}" class="space-y-4 print:hidden">
            @csrf
            <label class="flex gap-3 text-sm text-gray-700">
                <input type="checkbox" name="acknowledged" value="1" required class="mt-1 rounded border-gray-300">
                <span>I have saved these recovery codes in a secure place.</span>
            </label>
            <x-input-error :messages="$errors->get('acknowledged')" />
            <x-primary-button class="w-full justify-center">Continue to SayaraForce</x-primary-button>
        </form>
    </div>
</x-guest-layout>

@php
    $ai = $aiStatus ?? [
        'enabled'   => false,
        'threshold' => 0.60,
        'first'     => false,
        'label'     => 'AI Off',
        'color'     => '#9ca3af',
    ];
@endphp

<div class="mt-2 flex items-center gap-2">

    {{-- AI STATUS BADGE --}}
    <span
        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
        style="background-color: {{ $ai['enabled'] ? '#ecfdf5' : '#f3f4f6' }}; color: {{ $ai['color'] }};"
    >
        <span
            class="w-2 h-2 rounded-full mr-1.5"
            style="background-color: {{ $ai['color'] }}"
        ></span>
        {{ $ai['label'] }}
    </span>

    {{-- META INFO --}}
    <span class="text-xs text-gray-500">
        Thr: {{ number_format((float)($ai['threshold'] ?? 0.6), 2) }}

        <span class="mx-1 text-gray-300">•</span>

        First reply: {{ !empty($ai['first']) ? 'On' : 'Off' }}

        <span class="mx-1 text-gray-300">•</span>

        {{-- AI SETTINGS LINK (SAFE) --}}
        @if (\Illuminate\Support\Facades\Route::has('admin.ai.edit'))
            <a href="{{ route('admin.ai.edit') }}" class="underline text-blue-600">
                AI Settings
            </a>
        @else
            <span
                class="text-gray-400 cursor-not-allowed"
                title="AI settings not enabled yet"
            >
                AI Settings
            </span>
        @endif

        <span class="mx-1 text-gray-300">•</span>

        {{-- BUSINESS PROFILE LINK (SAFE) --}}
        @if (\Illuminate\Support\Facades\Route::has('admin.business.edit'))
            <a href="{{ route('admin.business.edit') }}" class="underline text-blue-600">
                Business Profile
            </a>
        @else
            <span
                class="text-gray-400 cursor-not-allowed"
                title="Business profile not configured yet"
            >
                Business Profile
            </span>
        @endif
    </span>
</div>

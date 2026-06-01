{{-- resources/views/admin/dashboard/partials/_ai_status_badge.blade.php --}}

@php
    $ai = $aiStatus ?? [
        'enabled' => false,
        'threshold' => 0.60,
        'first' => false,
        'label' => 'AI Off',
        'color' => '#9ca3af',
    ];

    $aiEnabled = (bool) ($ai['enabled'] ?? false);
    $aiLabel = $ai['label'] ?? ($aiEnabled ? 'AI On' : 'AI Off');
    $aiThreshold = $ai['threshold'] ?? 0.60;
    $firstReply = !empty($ai['first']);

    $aiSettingsRoute = \Illuminate\Support\Facades\Route::has('admin.ai.edit')
        ? route('admin.ai.edit')
        : null;

    $businessProfileRoute = \Illuminate\Support\Facades\Route::has('admin.business.edit')
        ? route('admin.business.edit')
        : null;
@endphp

<div class="flex flex-wrap items-center gap-2">
    <span
        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold
        {{ $aiEnabled ? 'border-emerald-400/30 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-800 text-slate-400' }}"
    >
        <span
            class="mr-1.5 h-2 w-2 rounded-full {{ $aiEnabled ? 'bg-emerald-400' : 'bg-slate-500' }}"
        ></span>
        {{ $aiLabel }}
    </span>

    <span class="inline-flex items-center rounded-full border border-slate-800 bg-slate-950/60 px-3 py-1 text-xs font-medium text-slate-400">
        Thr {{ number_format((float) $aiThreshold, 2) }}
    </span>

    <span class="inline-flex items-center rounded-full border border-slate-800 bg-slate-950/60 px-3 py-1 text-xs font-medium text-slate-400">
        First reply: {{ $firstReply ? 'On' : 'Off' }}
    </span>

    @if ($aiSettingsRoute)
        <a
            href="{{ $aiSettingsRoute }}"
            class="inline-flex items-center rounded-full border border-blue-400/30 bg-blue-500/10 px-3 py-1 text-xs font-bold text-blue-300 hover:bg-blue-500/20"
        >
            AI Settings
        </a>
    @else
        <span
            class="inline-flex cursor-not-allowed items-center rounded-full border border-slate-800 bg-slate-900 px-3 py-1 text-xs font-bold text-slate-600"
            title="AI settings not enabled yet"
        >
            AI Settings
        </span>
    @endif

    @if ($businessProfileRoute)
        <a
            href="{{ $businessProfileRoute }}"
            class="inline-flex items-center rounded-full border border-orange-400/30 bg-orange-500/10 px-3 py-1 text-xs font-bold text-orange-300 hover:bg-orange-500/20"
        >
            Business Profile
        </a>
    @else
        <span
            class="inline-flex cursor-not-allowed items-center rounded-full border border-slate-800 bg-slate-900 px-3 py-1 text-xs font-bold text-slate-600"
            title="Business profile not configured yet"
        >
            Business Profile
        </span>
    @endif
</div>
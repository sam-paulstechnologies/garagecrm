@extends('layouts.app')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
@endphp

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Audience Engine
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Audience Segmentation
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                View system-defined audience groups, trigger rules, message purpose, and real audience counts for WhatsApp journeys.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Integration Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                <a href="{{ route('admin.whatsapp.mappings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    WhatsApp Mappings
                </a>
            @endif

            <span class="inline-flex items-center rounded-xl border border-blue-400/20 bg-blue-500/10 px-4 py-2.5 text-sm font-extrabold text-blue-300">
                System-defined segments
            </span>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <div class="font-extrabold text-red-200">Please fix the following:</div>

            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Why this matters
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            These segments help SayaraForce prepare the right WhatsApp follow-up audience — retention, service reminders, feedback, dormant customers, high-value customers, and more.
        </p>
    </div>

    {{-- Empty --}}
    @if($segmentations->isEmpty())
        <div class="{{ $cardClass }}">
            <div class="px-6 py-16 text-center">
                <div class="mx-auto max-w-md">
                    <h3 class="text-xl font-extrabold text-white">
                        No audience segmentations found
                    </h3>

                    <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                        Please insert the default system-defined audience segmentations first.
                    </p>
                </div>
            </div>
        </div>
    @else

        {{-- Summary --}}
        @php
            $totalSegments = $segmentations->count();
            $enabledSegments = $segmentations->filter(fn ($item) => (bool) $item->company_is_enabled)->count();
            $totalAudience = $segmentations->sum(fn ($item) => (int) ($item->audience_count ?? 0));
        @endphp

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5 shadow-xl shadow-black/20">
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Total Segments
                </p>
                <p class="mt-2 text-3xl font-black text-white">
                    {{ number_format($totalSegments) }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    System-defined audience groups
                </p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5 shadow-xl shadow-black/20">
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Enabled Segments
                </p>
                <p class="mt-2 text-3xl font-black text-green-300">
                    {{ number_format($enabledSegments) }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    Active for this company
                </p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5 shadow-xl shadow-black/20">
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Total Audience
                </p>
                <p class="mt-2 text-3xl font-black text-orange-300">
                    {{ number_format($totalAudience) }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    Combined live audience count
                </p>
            </div>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($segmentations as $segmentation)
                @php
                    $isEnabled = (bool) $segmentation->company_is_enabled;
                    $audiencePreview = collect($segmentation->audience_preview ?? []);

                    $statusClass = $isEnabled
                        ? 'bg-green-500/10 text-green-300 ring-green-400/20'
                        : 'bg-red-500/10 text-red-300 ring-red-400/20';

                    $category = $segmentation->category ?? 'Audience';
                    $audienceCount = (int) ($segmentation->audience_count ?? 0);
                @endphp

                <div x-data="{ open: false }" class="{{ $cardClass }}">

                    {{-- Card Body --}}
                    <div class="{{ $cardBodyClass }}">

                        {{-- Top --}}
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <span class="inline-flex rounded-full bg-blue-500/10 px-3 py-1 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                                    {{ $category }}
                                </span>

                                <h2 class="mt-4 text-xl font-black leading-7 text-white">
                                    {{ $segmentation->name }}
                                </h2>

                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    {{ $segmentation->trigger_timing_label ?? 'Trigger timing not defined' }}
                                </p>
                            </div>

                            {{-- Toggle --}}
                            <form method="POST"
                                  action="{{ route('admin.audience-segmentations.toggle', $segmentation->id) }}">
                                @csrf
                                @method('PATCH')

                                <input type="hidden" name="is_enabled" value="0">

                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input
                                        type="checkbox"
                                        name="is_enabled"
                                        value="1"
                                        class="peer sr-only"
                                        onchange="this.form.submit()"
                                        {{ $isEnabled ? 'checked' : '' }}
                                    >

                                    <div class="h-6 w-11 rounded-full bg-slate-700 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-500 after:bg-white after:transition-all after:content-[''] peer-checked:bg-orange-500 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </form>
                        </div>

                        {{-- Description --}}
                        <p class="mt-4 min-h-[72px] text-sm font-medium leading-6 text-slate-400">
                            {{ $segmentation->description ?? 'No description added.' }}
                        </p>

                        {{-- Stats --}}
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Audience
                                </p>

                                <p class="mt-2 text-3xl font-black text-white">
                                    {{ number_format($audienceCount) }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Status
                                </p>

                                <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>

                        {{-- Trigger --}}
                        <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Trigger
                            </p>

                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-300">
                                {{ $segmentation->trigger_description ?? 'No trigger description available.' }}
                            </p>
                        </div>

                        {{-- Button --}}
                        <button
                            type="button"
                            @click="open = true"
                            class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600"
                        >
                            View Details
                        </button>
                    </div>

                    {{-- Modal --}}
                    <div
                        x-show="open"
                        x-cloak
                        class="fixed inset-0 z-50 overflow-y-auto"
                        aria-labelledby="modal-title-{{ $segmentation->id }}"
                        role="dialog"
                        aria-modal="true"
                    >
                        {{-- Overlay --}}
                        <div
                            class="fixed inset-0 bg-black/70 backdrop-blur-sm"
                            @click="open = false"
                        ></div>

                        {{-- Modal Panel --}}
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div
                                x-show="open"
                                x-transition
                                @click.stop
                                class="relative w-full max-w-6xl overflow-hidden rounded-3xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/40"
                            >

                                {{-- Modal Header --}}
                                <div class="border-b border-white/10 bg-slate-950/50 px-6 py-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 id="modal-title-{{ $segmentation->id }}" class="text-2xl font-black text-white">
                                                    {{ $segmentation->name }}
                                                </h3>

                                                <span class="inline-flex rounded-full bg-blue-500/10 px-3 py-1 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                                                    {{ $category }}
                                                </span>

                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                                    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </div>

                                            <p class="mt-2 text-sm font-medium text-slate-500">
                                                Audience Segmentation Details
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            @click="open = false"
                                            class="rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm font-extrabold text-slate-300 transition hover:border-red-400/30 hover:text-red-300"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                {{-- Modal Body --}}
                                <div class="max-h-[75vh] overflow-y-auto px-6 py-6">

                                    {{-- Summary Stats --}}
                                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                                Audience Count
                                            </p>

                                            <p class="mt-2 text-3xl font-black text-white">
                                                {{ number_format($audienceCount) }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                                Trigger Timing
                                            </p>

                                            <p class="mt-2 text-sm font-extrabold text-slate-200">
                                                {{ $segmentation->trigger_timing_label ?? 'Not defined' }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                                Trigger Event
                                            </p>

                                            <p class="mt-2 text-sm font-extrabold text-slate-200">
                                                {{ $segmentation->trigger_event ?? 'Not defined' }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                                Automation Status
                                            </p>

                                            <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                                {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                                        {{-- Details --}}
                                        <div class="space-y-6 lg:col-span-2">

                                            <div class="rounded-3xl border border-white/10 bg-slate-950/55 p-5">
                                                <h4 class="text-lg font-extrabold text-white">
                                                    Segment Description
                                                </h4>

                                                <p class="mt-3 text-sm font-medium leading-7 text-slate-400">
                                                    {{ $segmentation->description ?? 'No description added.' }}
                                                </p>
                                            </div>

                                            <div class="rounded-3xl border border-white/10 bg-slate-950/55 p-5">
                                                <h4 class="text-lg font-extrabold text-white">
                                                    Trigger Rule
                                                </h4>

                                                <p class="mt-3 text-sm font-medium leading-7 text-slate-400">
                                                    {{ $segmentation->trigger_description ?? 'No trigger description available.' }}
                                                </p>
                                            </div>

                                            <div class="rounded-3xl border border-white/10 bg-slate-950/55 p-5">
                                                <h4 class="text-lg font-extrabold text-white">
                                                    Audience Preview
                                                </h4>

                                                @if($audiencePreview->isEmpty())
                                                    <p class="mt-3 text-sm font-medium text-slate-500">
                                                        No preview records available for this segment.
                                                    </p>
                                                @else
                                                    <div class="mt-4 overflow-x-auto">
                                                        <table class="min-w-full text-sm">
                                                            <thead class="border-b border-white/10">
                                                                <tr>
                                                                    <th class="px-3 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Name</th>
                                                                    <th class="px-3 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Phone</th>
                                                                    <th class="px-3 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Source</th>
                                                                    <th class="px-3 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Status</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody class="divide-y divide-white/10">
                                                                @foreach($audiencePreview as $person)
                                                                    <tr>
                                                                        <td class="px-3 py-3 font-semibold text-white">
                                                                            {{ data_get($person, 'name', '—') }}
                                                                        </td>

                                                                        <td class="px-3 py-3 font-semibold text-slate-400">
                                                                            {{ data_get($person, 'phone', data_get($person, 'phone_norm', '—')) }}
                                                                        </td>

                                                                        <td class="px-3 py-3 font-semibold text-slate-400">
                                                                            {{ data_get($person, 'source', '—') }}
                                                                        </td>

                                                                        <td class="px-3 py-3 font-semibold text-slate-400">
                                                                            {{ data_get($person, 'status', '—') }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Side Info --}}
                                        <aside class="space-y-6">

                                            <div class="rounded-3xl border border-white/10 bg-slate-950/55 p-5">
                                                <h4 class="text-lg font-extrabold text-white">
                                                    Segment Usage
                                                </h4>

                                                <ul class="mt-4 list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                                                    <li>Used for WhatsApp targeting.</li>
                                                    <li>Can be enabled or disabled per company.</li>
                                                    <li>Audience count is calculated from live CRM data.</li>
                                                    <li>One customer can belong to multiple segments.</li>
                                                </ul>
                                            </div>

                                            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5">
                                                <h4 class="text-lg font-extrabold text-orange-200">
                                                    Recommended Action
                                                </h4>

                                                <p class="mt-3 text-sm font-medium leading-6 text-orange-100/80">
                                                    Keep this segment enabled only if the garage wants this audience to be used for future follow-up or campaign journeys.
                                                </p>
                                            </div>

                                            <form method="POST"
                                                  action="{{ route('admin.audience-segmentations.toggle', $segmentation->id) }}">
                                                @csrf
                                                @method('PATCH')

                                                <input type="hidden" name="is_enabled" value="{{ $isEnabled ? 0 : 1 }}">

                                                <button type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-xl {{ $isEnabled ? 'bg-red-600 hover:bg-red-700 shadow-red-500/20' : 'bg-green-600 hover:bg-green-700 shadow-green-500/20' }} px-5 py-3 text-sm font-extrabold text-white shadow-lg transition">
                                                    {{ $isEnabled ? 'Disable Segment' : 'Enable Segment' }}
                                                </button>
                                            </form>

                                        </aside>
                                    </div>
                                </div>

                                {{-- Modal Footer --}}
                                <div class="border-t border-white/10 bg-slate-950/50 px-6 py-4">
                                    <div class="flex justify-end">
                                        <button type="button"
                                                @click="open = false"
                                                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                                            Close
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
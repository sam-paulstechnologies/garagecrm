@extends('layouts.app')

@section('content')
<div class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Audience Segmentation
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        View system-defined audience groups, trigger rules, message purpose, and real audience counts.
                    </p>
                </div>

                <div>
                    <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700">
                        System-defined segments
                    </span>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Empty --}}
        @if($segmentations->isEmpty())
            <div class="rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="px-6 py-12 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        No audience segmentations found
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Please insert the default system-defined audience segmentations first.
                    </p>
                </div>
            </div>
        @else

            {{-- Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($segmentations as $segmentation)
                    @php
                        $isEnabled = (bool) $segmentation->company_is_enabled;
                        $audiencePreview = collect($segmentation->audience_preview ?? []);
                    @endphp

                    <div
                        x-data="{ open: false }"
                        class="rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden"
                    >
                        {{-- Card Body --}}
                        <div class="p-6">

                            {{-- Top --}}
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 border border-indigo-100">
                                        {{ $segmentation->category ?? 'Audience' }}
                                    </span>

                                    <h2 class="mt-3 text-lg font-bold text-gray-900">
                                        {{ $segmentation->name }}
                                    </h2>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $segmentation->trigger_timing_label }}
                                    </p>
                                </div>

                                {{-- Toggle --}}
                                <form method="POST"
                                      action="{{ route('admin.audience-segmentations.toggle', $segmentation->id) }}">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="is_enabled" value="0">

                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="is_enabled"
                                            value="1"
                                            class="sr-only peer"
                                            onchange="this.form.submit()"
                                            {{ $isEnabled ? 'checked' : '' }}
                                        >
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </form>
                            </div>

                            {{-- Description --}}
                            <p class="mt-4 text-sm text-gray-600 min-h-[60px]">
                                {{ $segmentation->description }}
                            </p>

                            {{-- Stats --}}
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <p class="text-xs text-gray-500">Audience</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ number_format((int) $segmentation->audience_count) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <p class="text-xs text-gray-500">Status</p>
                                    @if($isEnabled)
                                        <p class="mt-2 text-sm font-bold text-green-600">Enabled</p>
                                    @else
                                        <p class="mt-2 text-sm font-bold text-red-600">Disabled</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Trigger --}}
                            <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Trigger
                                </p>
                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $segmentation->trigger_description }}
                                </p>
                            </div>

                            {{-- Button --}}
                            <button
                                type="button"
                                @click="open = true"
                                class="mt-5 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                            >
                                View Details
                            </button>
                        </div>

                        {{-- Modal --}}
                        <div
                            x-show="open"
                            x-cloak
                            class="fixed inset-0 z-50 overflow-y-auto"
                            aria-labelledby="modal-title"
                            role="dialog"
                            aria-modal="true"
                        >
                            {{-- Overlay --}}
                            <div
                                class="fixed inset-0 bg-black/50"
                                @click="open = false"
                            ></div>

                            {{-- Modal Panel --}}
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div
                                    x-show="open"
                                    x-transition
                                    @click.stop
                                    class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl overflow-hidden"
                                >
                                    {{-- Modal Header --}}
                                    <div class="border-b border-gray-100 px-6 py-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 id="modal-title" class="text-xl font-bold text-gray-900">
                                                        {{ $segmentation->name }}
                                                    </h3>

                                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 border border-indigo-100">
                                                        {{ $segmentation->category ?? 'Audience' }}
                                                    </span>

                                                    @if($isEnabled)
                                                        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 border border-green-100">
                                                            Enabled
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 border border-red-100">
                                                            Disabled
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-1 text-sm text-gray-500">
                                                    Audience Segmentation Details
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                @click="open = false"
                                                class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modal Body --}}
                                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6">

                                        {{-- Summary Stats --}}
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                                <p class="text-xs text-gray-500">Audience Count</p>
                                                <p class="mt-1 text-2xl font-bold text-gray-900">
                                                    {{ number_format((int) $segmentation->audience_count) }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                                <p class="text-xs text-gray-500">Trigger Timing</p>
                                                <p class="mt-2 text-sm font-bold text-gray-900">
                                                    {{ $segmentation->trigger_timing_label }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                                <p class="text-xs text-gray-500">Trigger Event</p>
                                                <p class="mt-2 text-sm font-bold text-gray-900">
                                                    {{ $segmentation->trigger_event ?? 'Not defined' }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                                <p class="text-xs text-gray-500">Automation Status</p>
                                                @if($isEnabled)
                                                    <p class="mt-2 text-sm font-bold text-green-600">Enabled</p>
                                                @else
                                                    <p class="mt-2 text-sm font-bold text-red-600">Disabled</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Detail Blocks --}}
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                                <h4 class="text-sm font-bold text-gray-900">Description</h4>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    {{ $segmentation->description ?: 'No description available.' }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                                <h4 class="text-sm font-bold text-gray-900">Audience Rule</h4>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    {{ $segmentation->audience_rule_description ?: 'No audience rule available.' }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                                <h4 class="text-sm font-bold text-gray-900">Trigger Logic</h4>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    {{ $segmentation->trigger_description ?: 'No trigger rule available.' }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-gray-100 bg-white p-5">
                                                <h4 class="text-sm font-bold text-gray-900">Message Purpose</h4>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    {{ $segmentation->message_description ?: 'No message description available.' }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Message Preview --}}
                                        <div class="rounded-xl border border-gray-100 bg-white p-5 mb-6">
                                            <h4 class="text-sm font-bold text-gray-900">
                                                Example Message Preview
                                            </h4>
                                            <div class="mt-3 rounded-xl bg-green-50 border border-green-100 p-4 text-sm text-gray-700">
                                                {{ $segmentation->example_message ?: 'No example message available.' }}
                                            </div>
                                        </div>

                                        {{-- Audience Preview --}}
                                        <div class="rounded-xl border border-gray-100 bg-white overflow-hidden">
                                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
                                                <h4 class="text-sm font-bold text-gray-900">
                                                    Audience Preview
                                                </h4>
                                                <p class="text-xs text-gray-500">
                                                    Showing first {{ $audiencePreview->count() }} records
                                                </p>
                                            </div>

                                            @if($audiencePreview->isEmpty())
                                                <div class="px-5 py-10 text-center">
                                                    <h5 class="text-sm font-semibold text-gray-900">
                                                        No audience found
                                                    </h5>
                                                    <p class="mt-1 text-sm text-gray-500">
                                                        No lead or customer currently matches this segmentation rule.
                                                    </p>
                                                </div>
                                            @else
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-100">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phone</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reason</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Source</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Last Activity</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 bg-white">
                                                            @foreach($audiencePreview as $audience)
                                                                <tr>
                                                                    <td class="px-4 py-3">
                                                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                                                            {{ ucfirst($audience['type'] ?? 'record') }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                                        {{ $audience['name'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                                        {{ $audience['phone'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                                        {{ $audience['email'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                                        {{ $audience['reason'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                                        {{ $audience['source'] ?? 'N/A' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                                        {{ $audience['last_activity'] ?? 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                @if((int) $segmentation->audience_count > $audiencePreview->count())
                                                    <div class="border-t border-gray-100 bg-gray-50 px-5 py-3 text-xs text-gray-500">
                                                        More records exist in this segmentation. Full export/view can be added later.
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Modal Footer --}}
                                    <div class="flex items-center justify-between gap-4 border-t border-gray-100 bg-gray-50 px-6 py-4">
                                        <form method="POST"
                                              action="{{ route('admin.audience-segmentations.toggle', $segmentation->id) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden" name="is_enabled" value="{{ $isEnabled ? 0 : 1 }}">

                                            @if($isEnabled)
                                                <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                                                    Disable Trigger
                                                </button>
                                            @else
                                                <button type="submit" class="rounded-xl border border-green-200 bg-white px-4 py-2 text-sm font-semibold text-green-600 hover:bg-green-50">
                                                    Enable Trigger
                                                </button>
                                            @endif
                                        </form>

                                        <button
                                            type="button"
                                            @click="open = false"
                                            class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                                        >
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- End Modal --}}

                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
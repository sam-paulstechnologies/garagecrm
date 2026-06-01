@extends('layouts.app')

@section('title', 'WhatsApp Templates')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
    $selectClass = $inputClass;
@endphp

<style>
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-900\/80,
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-900,
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-950\/35,
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-950\/50,
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-950\/70 {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .bg-slate-800 {
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .border-white\/10 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page :where(h1, h2, h3, p, div, td, th, label, span, a, button).text-white,
    html[data-theme="light"] .sf-whatsapp-templates-page input.text-white,
    html[data-theme="light"] .sf-whatsapp-templates-page select.text-white {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-slate-200,
    html[data-theme="light"] .sf-whatsapp-templates-page .text-slate-300,
    html[data-theme="light"] .sf-whatsapp-templates-page .text-slate-400 {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-blue-200,
    html[data-theme="light"] .sf-whatsapp-templates-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-blue-100\/80 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-yellow-300 {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page :where(.bg-orange-500, .bg-orange-600, .bg-red-600).text-white,
    html[data-theme="light"] .sf-whatsapp-templates-page :where(.bg-orange-500, .bg-orange-600, .bg-red-600) .text-white {
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page input,
    html[data-theme="light"] .sf-whatsapp-templates-page select {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page input::placeholder {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page table,
    html[data-theme="light"] .sf-whatsapp-templates-page tbody {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page thead {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-whatsapp-templates-page tbody tr:hover {
        background: #f3f6fb !important;
    }
</style>

<div class="sf-whatsapp-templates-page mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                WhatsApp Library
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                WhatsApp Templates
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Manage reusable WhatsApp templates used by journey events, booking confirmations, feedback flows, and manager escalations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    WhatsApp Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                <a href="{{ route('admin.whatsapp.mappings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Template Mappings
                </a>
            @endif

            <a href="{{ route('admin.whatsapp.templates.create') }}"
               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                + New Template
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Template library
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            These templates are linked to journey mappings. Keep template names clear so admins can quickly identify what each message is used for.
        </p>
    </div>

    {{-- Filters --}}
    <form method="GET" class="{{ $cardClass }} mb-6">
        <div class="border-b border-white/10 bg-slate-950/35 px-6 py-4">
            <h2 class="text-lg font-extrabold text-white">
                Search & Filter
            </h2>
            <p class="mt-1 text-sm font-medium text-slate-500">
                Find templates by name, language, status, or category.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 px-6 py-6 md:grid-cols-5">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search name / language / category"
                   class="{{ $inputClass }} md:col-span-2">

            <select name="status" class="{{ $selectClass }}">
                <option value="">Any status</option>
                @foreach(['draft','active','archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>

            <select name="category" class="{{ $selectClass }}">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category')===$cat)>
                        {{ $cat }}
                    </option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button class="inline-flex w-full items-center justify-center rounded-xl bg-slate-800 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-slate-700">
                    Filter
                </button>

                <a href="{{ route('admin.whatsapp.templates.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm font-extrabold text-slate-300 transition hover:border-orange-400/30 hover:text-white">
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="{{ $cardClass }}">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-white/10 bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Provider Tpl</th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Language</th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Category</th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Updated</th>
                        <th class="px-4 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/10">
                    @forelse($templates as $t)
                        @php
                            $statusClass = match($t->status) {
                                'active' => 'bg-green-500/10 text-green-300 ring-green-400/20',
                                'archived' => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
                                default => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
                            };
                        @endphp

                        <tr class="transition hover:bg-white/[0.03]">
                            <td class="px-4 py-4 align-top">
                                <a class="font-extrabold text-white hover:text-orange-300"
                                   href="{{ route('admin.whatsapp.templates.show', $t) }}">
                                    {{ $t->name }}
                                </a>
                            </td>

                            <td class="px-4 py-4 align-top font-semibold text-slate-400">
                                {{ $t->provider_template ?: '—' }}
                            </td>

                            <td class="px-4 py-4 align-top font-semibold text-slate-300">
                                {{ strtoupper($t->language) }}
                            </td>

                            <td class="px-4 py-4 align-top font-semibold text-slate-400">
                                {{ $t->category ?: '—' }}
                            </td>

                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                    {{ ucfirst($t->status) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 align-top font-semibold text-slate-500">
                                {{ optional($t->updated_at)->diffForHumans() }}
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.whatsapp.templates.show', $t) }}"
                                       class="inline-flex items-center rounded-lg border border-white/10 bg-slate-950/50 px-3 py-1.5 text-xs font-extrabold text-slate-300 hover:border-blue-400/30 hover:text-blue-300">
                                        View
                                    </a>

                                    <a href="{{ route('admin.whatsapp.templates.edit', $t) }}"
                                       class="inline-flex items-center rounded-lg border border-white/10 bg-slate-950/50 px-3 py-1.5 text-xs font-extrabold text-slate-300 hover:border-green-400/30 hover:text-green-300">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.whatsapp.templates.destroy', $t) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Delete this template?');">
                                        @csrf
                                        @method('DELETE')

                                        <button class="inline-flex items-center rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs font-extrabold text-red-300 hover:bg-red-500/20">
                                            Delete
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.whatsapp.templates.test_send', $t) }}"
                                          method="POST"
                                          class="inline-flex items-center gap-2">
                                        @csrf

                                        <input name="to_phone"
                                               placeholder="+9715XXXXXXX"
                                               class="w-36 rounded-lg border border-white/10 bg-slate-950/70 px-2 py-1.5 text-xs font-semibold text-white placeholder:text-slate-600 outline-none focus:border-orange-400/50"
                                               required
                                               pattern="^\+\d{8,20}$">

                                        <button class="inline-flex items-center rounded-lg border border-orange-400/20 bg-orange-500/10 px-3 py-1.5 text-xs font-extrabold text-orange-300 hover:bg-orange-500/20">
                                            Test
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center">
                                <div class="mx-auto max-w-md">
                                    <div class="text-lg font-extrabold text-white">
                                        No templates yet
                                    </div>

                                    <p class="mt-2 text-sm font-medium text-slate-500">
                                        Create your first WhatsApp template and map it to a customer journey event.
                                    </p>

                                    <a href="{{ route('admin.whatsapp.templates.create') }}"
                                       class="mt-5 inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                                        + New Template
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $templates->links() }}
    </div>
</div>
@endsection

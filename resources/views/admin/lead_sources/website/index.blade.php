@php use Illuminate\Support\Str; @endphp

@extends('layouts.app')

@section('title', 'Website Forms')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
@endphp

<style>
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-900\/80,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-900,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/35,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/55,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/60,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/70 {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .border-white\/10 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix :where(h1, h2, h3, p, div, td, th, label).text-white,
    html[data-theme="light"] .sf-growth-visual-fix input.text-white,
    html[data-theme="light"] .sf-growth-visual-fix textarea.text-white {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-slate-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-slate-300,
    html[data-theme="light"] .sf-growth-visual-fix .text-slate-400 {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-blue-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-blue-100\/80 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-red-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-orange-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-orange-100\/80 {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix input,
    html[data-theme="light"] .sf-growth-visual-fix textarea {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix input::placeholder,
    html[data-theme="light"] .sf-growth-visual-fix textarea::placeholder {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix thead {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix tbody {
        border-color: #e2e8f0 !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix tbody tr:hover {
        background: #f3f6fb !important;
    }
</style>

<div class="sf-growth-visual-fix mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Website Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Website Forms
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Manage lead capture forms that can be embedded on your website or landing pages.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Lead Sources
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
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
            Website form capture
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            Create website forms, copy the embed snippet, and capture incoming enquiries as leads with website source tagging.
        </p>
    </div>

    {{-- Create Form --}}
    <section class="{{ $cardClass }} mb-6">
        <div class="{{ $cardHeaderClass }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        Create New Website Form
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Add a new embeddable form for a website, landing page, campaign, or service page.
                    </p>
                </div>

                <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                    Capture
                </span>
            </div>
        </div>

        <div class="{{ $cardBodyClass }}">
            <form method="POST"
                  action="{{ route('admin.lead-sources.website.store') }}"
                  class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @csrf

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">
                        Form Name
                    </label>

                    <input name="form_name"
                           class="{{ $inputClass }}"
                           placeholder="Example: Website Contact Form"
                           value="{{ old('form_name') }}"
                           required>

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        Use a clear name so you know where this form is embedded.
                    </p>
                </div>

                <div class="flex items-end">
                    <button class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                        Save Form
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Forms Table --}}
    <section class="{{ $cardClass }}">
        <div class="{{ $cardHeaderClass }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        Created Forms
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        View embed code and API endpoint for each website form.
                    </p>
                </div>

                <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                    {{ $forms->count() }} Forms
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-white/10 bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Form
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Token
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Last Lead
                        </th>
                        <th class="px-4 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/10">
                    @forelse ($forms as $form)
                        <tr class="transition hover:bg-white/[0.03]">
                            <td class="px-4 py-4 align-top">
                                <div class="font-extrabold text-white">
                                    {{ $form->config['form_name'] ?? $form->name }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Website lead capture form
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded-full border border-white/10 bg-slate-950/70 px-3 py-1.5 text-xs font-extrabold text-slate-300">
                                    {{ Str::limit($form->form_token, 16) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 align-top font-semibold text-slate-400">
                                {{ $form->last_received_at?->diffForHumans() ?? '—' }}
                            </td>

                            <td class="px-4 py-4 align-top text-right">
                                <a href="{{ route('admin.lead-sources.website.show', $form) }}"
                                   class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700">
                                    View Embed
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-14 text-center">
                                <div class="mx-auto max-w-md">
                                    <h3 class="text-lg font-extrabold text-white">
                                        No website forms created yet
                                    </h3>

                                    <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                                        Create your first form above, then copy the embed snippet to your website.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

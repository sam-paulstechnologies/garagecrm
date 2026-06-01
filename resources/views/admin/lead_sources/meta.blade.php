@extends('layouts.app')

@section('title', 'Meta Lead Forms')

@section('content')
@php
    use App\Models\LeadSource;
    use Illuminate\Support\Facades\Route;

    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';

    $sessionPages = collect(session('meta_pages', []));

    $formsRaw = $meta->forms_json ?? [];

    if (is_string($formsRaw)) {
        $connectedForms = json_decode($formsRaw, true) ?: [];
    } elseif (is_array($formsRaw)) {
        $connectedForms = $formsRaw;
    } else {
        $connectedForms = [];
    }

    $metaLeadSources = collect();

    if (auth()->check()) {
        $metaLeadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('type', 'meta')
            ->get()
            ->keyBy(function ($source) {
                $config = $source->config;

                if (is_string($config)) {
                    $config = json_decode($config, true) ?: [];
                }

                return data_get($config, 'form_id');
            });
    }
@endphp

<style>
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-900\/80,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-900,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/35,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/50,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/55,
    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-950\/70 {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .bg-slate-800 {
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .border-white\/10 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix :where(h1, h2, h3, h4, p, div, td, th, label, li).text-white {
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

    html[data-theme="light"] .sf-growth-visual-fix .text-green-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-red-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-yellow-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-yellow-300 {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-yellow-100\/80 {
        color: #854d0e !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-orange-200,
    html[data-theme="light"] .sf-growth-visual-fix .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix .text-orange-100\/80 {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix table,
    html[data-theme="light"] .sf-growth-visual-fix tbody {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-growth-visual-fix thead {
        background: #f8fafc !important;
    }
</style>

<div class="sf-growth-visual-fix mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-green-400/20 bg-green-500/10 px-5 py-4 text-sm font-bold text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-5 py-4 text-sm font-bold text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Meta Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Meta Lead Forms
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Connect your Facebook Page and sync Facebook / Instagram lead forms into SayaraForce.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Lead Sources
            </a>

            @if(Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Integration Settings
                </a>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Meta lead capture
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            Once connected, Facebook and Instagram lead forms can be synced into the CRM and tagged as Meta leads for follow-up, conversion, and campaign ROI.
        </p>
    </div>

    {{-- Page Selection After OAuth --}}
    @if($sessionPages->isNotEmpty())
        <section class="mb-6 {{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <h2 class="text-lg font-extrabold text-white">
                    Select Facebook Page
                </h2>

                <p class="mt-1 text-sm font-medium text-slate-500">
                    Choose the garage Facebook Page that should send Meta lead forms into SayaraForce.
                </p>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($sessionPages as $page)
                        <form method="POST" action="{{ route('admin.lead-sources.meta.select-page') }}"
                              class="rounded-2xl border border-white/10 bg-slate-950/55 p-5">
                            @csrf

                            <input type="hidden" name="page_id" value="{{ $page['id'] ?? '' }}">
                            <input type="hidden" name="page_name" value="{{ $page['name'] ?? 'Facebook Page' }}">

                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Facebook Page
                            </p>

                            <p class="mt-2 text-lg font-black text-white">
                                {{ $page['name'] ?? 'Facebook Page' }}
                            </p>

                            <p class="mt-1 break-all text-xs font-semibold text-slate-500">
                                Page ID: {{ $page['id'] ?? '-' }}
                            </p>

                            <button type="submit"
                                    class="mt-5 inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                                Use This Page
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Facebook Page Connection
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Connect or manage the Facebook Page used for lead form sync.
                            </p>
                        </div>

                        @if(!$meta)
                            <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-extrabold text-red-300 ring-1 ring-red-400/20">
                                Not Connected
                            </span>
                        @else
                            <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">

                    @if(!$meta)
                        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5">
                            <p class="text-sm font-extrabold text-yellow-200">
                                No Facebook Page connected yet.
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                                Connect a Facebook Page to start syncing Meta lead forms into SayaraForce.
                            </p>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('admin.lead-sources.meta.connect') }}"
                               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                                Connect Facebook
                            </a>
                        </div>
                    @else
                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-5">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Connected Page
                            </p>

                            <p class="mt-2 text-xl font-black text-white">
                                {{ $meta->page_name }}
                            </p>

                            <p class="mt-2 break-all text-sm font-medium leading-6 text-slate-400">
                                Page ID: {{ $meta->page_id }}
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                                Lead forms are synced from this page.
                            </p>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">

                            <form method="POST" action="{{ route('admin.lead-sources.meta.refresh') }}">
                                @csrf

                                <button class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-slate-700">
                                    Refresh Forms
                                </button>
                            </form>

                            <a href="{{ route('admin.lead-sources.meta.connect') }}"
                               class="inline-flex items-center justify-center rounded-xl border border-orange-400/20 bg-orange-500/10 px-5 py-3 text-sm font-extrabold text-orange-300 transition hover:bg-orange-500/20">
                                Change Page
                            </a>

                            <form method="POST"
                                  action="{{ route('admin.lead-sources.meta.disconnect') }}"
                                  onsubmit="return confirm('Disconnect this Meta page? Existing Meta lead sources will be marked inactive.');">
                                @csrf

                                <button class="inline-flex items-center justify-center rounded-xl border border-red-400/20 bg-red-500/10 px-5 py-3 text-sm font-extrabold text-red-300 transition hover:bg-red-500/20">
                                    Disconnect
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            </section>

            {{-- Synced Forms --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Synced Lead Forms
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                These forms are converted into Meta lead sources.
                            </p>
                        </div>

                        <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-extrabold text-slate-300 ring-1 ring-white/10">
                            {{ count($connectedForms) }} Forms
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    @if(empty($connectedForms))
                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-5">
                            <p class="text-sm font-extrabold text-slate-300">
                                No forms synced yet.
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                                Connect a Facebook Page or click Refresh Forms after connecting.
                            </p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-white/10">
                            <table class="min-w-full divide-y divide-white/10">
                                <thead class="bg-slate-950/70">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                            Form
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                            Form ID
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                            Meta Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                            CRM Source
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-white/10 bg-slate-950/35">
                                    @foreach($connectedForms as $form)
                                        @php
                                            $formId = $form['id'] ?? null;
                                            $source = $formId ? $metaLeadSources->get($formId) : null;
                                        @endphp

                                        <tr>
                                            <td class="px-4 py-4">
                                                <p class="text-sm font-extrabold text-white">
                                                    {{ $form['name'] ?? 'Untitled Form' }}
                                                </p>

                                                @if(!empty($form['created_time']))
                                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                                        Created: {{ $form['created_time'] }}
                                                    </p>
                                                @endif
                                            </td>

                                            <td class="px-4 py-4">
                                                <p class="break-all text-xs font-semibold text-slate-400">
                                                    {{ $formId ?? '-' }}
                                                </p>
                                            </td>

                                            <td class="px-4 py-4">
                                                <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                                                    {{ $form['status'] ?? 'Available' }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                @if($source)
                                                    @if($source->status === 'active')
                                                        <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="rounded-full bg-yellow-500/10 px-2.5 py-0.5 text-xs font-extrabold text-yellow-300 ring-1 ring-yellow-400/20">
                                                            {{ ucfirst($source->status) }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-extrabold text-red-300 ring-1 ring-red-400/20">
                                                        Missing
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        How this works
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Connect a Facebook Page.</li>
                        <li>Select the garage Page after OAuth.</li>
                        <li>SayaraForce fetches available lead forms.</li>
                        <li>Each form becomes a Meta lead source.</li>
                        <li>The Page is subscribed to leadgen webhooks.</li>
                        <li>New leads can then enter CRM automatically.</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20">
                <h2 class="text-lg font-extrabold text-orange-200">
                    Required Meta Permission
                </h2>

                <p class="mt-3 text-sm font-medium leading-6 text-orange-100/80">
                    The Meta app must have access to pages_show_list, pages_read_engagement, pages_manage_metadata, and leads_retrieval for this flow to work correctly.
                </p>
            </section>

            <section class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-6 shadow-xl shadow-black/20">
                <h2 class="text-lg font-extrabold text-blue-200">
                    Next Step
                </h2>

                <p class="mt-3 text-sm font-medium leading-6 text-blue-100/80">
                    After this page works, we move to webhook ingest and dedupe logic so real Meta leads create CRM leads correctly.
                </p>
            </section>

        </aside>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Meta Lead Forms')

@section('content')
@php
    use App\Models\LeadSource;
    use Illuminate\Support\Facades\Route;

    $isMediaTeam = auth()->check()
        && strtolower(trim((string) auth()->user()->role)) === 'media_team';

    $cardClass = 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900/80';
    $cardHeaderClass = 'border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-white/10 dark:bg-slate-950/35';
    $cardBodyClass = 'px-5 py-5';

    $sessionPages = collect(session('meta_pages', []));

    $formsRaw = $meta->forms_json ?? [];

    if (is_string($formsRaw)) {
        $connectedForms = json_decode($formsRaw, true) ?: [];
    } elseif (is_array($formsRaw)) {
        $connectedForms = $formsRaw;
    } else {
        $connectedForms = [];
    }

    if (is_array($connectedForms) && array_key_exists('data', $connectedForms) && is_array($connectedForms['data'])) {
        $connectedForms = $connectedForms['data'];
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

    $connectedFormIds = collect($connectedForms)
        ->map(fn ($form) => (string) ($form['id'] ?? ''))
        ->filter()
        ->all();

    foreach ($metaLeadSources as $sourceFormId => $source) {
        $sourceFormId = (string) $sourceFormId;

        if ($sourceFormId === '' || in_array($sourceFormId, $connectedFormIds, true)) {
            continue;
        }

        $config = $source->config;

        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        $connectedForms[] = [
            'id' => $sourceFormId,
            'name' => data_get($config, 'form_name', $source->name),
            'status' => data_get($config, 'form_status', 'Synced'),
            'created_time' => data_get($config, 'form_created_at'),
        ];
    }
@endphp

<div class="mx-auto max-w-[90rem] px-4 py-8 sm:px-6 lg:px-8">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-bold text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-800 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-700 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200">
                Meta Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 md:text-4xl dark:text-white">
                Meta Lead Forms
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                Connect your Facebook Page and sync Facebook / Instagram lead forms into SayaraForce.
            </p>
        </div>

        @if(! $isMediaTeam)
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.lead-sources.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 shadow-sm transition hover:border-orange-300 hover:text-orange-700 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-orange-400/30 dark:hover:text-white">
                    Back to Lead Sources
                </a>

                @if(Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 shadow-sm transition hover:border-orange-300 hover:text-orange-700 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-orange-400/30 dark:hover:text-white">
                    Integration Settings
                </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50/70 px-5 py-4 dark:border-blue-400/20 dark:bg-blue-500/10">
        <p class="text-sm font-extrabold text-blue-800 dark:text-blue-200">
            Meta lead capture
        </p>

        <p class="mt-1.5 text-sm font-medium leading-6 text-blue-700 dark:text-blue-100/80">
            Once connected, Facebook and Instagram lead forms are synced for visibility. Only forms marked Capture Active can create CRM leads.
        </p>
    </div>

    {{-- Page Selection After OAuth --}}
    @if($sessionPages->isNotEmpty())
        <section class="mb-6 {{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">
                    Select Facebook Page
                </h2>

                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">
                    Choose the garage Facebook Page that should send Meta lead forms into SayaraForce.
                </p>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($sessionPages as $page)
                        <form method="POST" action="{{ route('admin.lead-sources.meta.select-page') }}"
                              class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-950/55">
                            @csrf

                            <input type="hidden" name="page_id" value="{{ $page['id'] ?? '' }}">
                            <input type="hidden" name="page_name" value="{{ $page['name'] ?? 'Facebook Page' }}">

                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Facebook Page
                            </p>

                            <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">
                                {{ $page['name'] ?? 'Facebook Page' }}
                            </p>

                            <p class="mt-1 break-all text-xs font-semibold text-slate-500 dark:text-slate-400">
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
                            <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">
                                Facebook Page Connection
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">
                                Connect or manage the Facebook Page used for lead form sync.
                            </p>
                        </div>

                        @if(!$meta)
                            <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-extrabold text-red-700 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-400/20">
                                Not Connected
                            </span>
                        @else
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-extrabold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                                Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">

                    @if(!$meta)
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-400/20 dark:bg-amber-500/10">
                            <p class="text-sm font-extrabold text-amber-800 dark:text-amber-200">
                                No Facebook Page connected yet.
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-amber-700 dark:text-amber-100/80">
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
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-slate-950/45">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Connected Page
                            </p>

                            <p class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                                {{ $meta->page_name }}
                            </p>

                            <p class="mt-2 break-all text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                                Page ID: {{ $meta->page_id }}
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                                Lead forms are synced from this page.
                            </p>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">

                            <form method="POST" action="{{ route('admin.lead-sources.meta.refresh') }}">
                                @csrf

                                <button class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-extrabold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700 sm:w-auto">
                                    Refresh Forms
                                </button>
                            </form>

                            <a href="{{ route('admin.lead-sources.meta.connect') }}"
                               class="inline-flex items-center justify-center rounded-xl border border-orange-200 bg-orange-50 px-5 py-2.5 text-sm font-extrabold text-orange-700 transition hover:bg-orange-100 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200 dark:hover:bg-orange-500/20">
                                Change Page
                            </a>

                            <form method="POST"
                                  action="{{ route('admin.lead-sources.meta.disconnect') }}"
                                  onsubmit="return confirm('Disconnect this Meta page? Existing Meta lead sources will be marked inactive.');">
                                @csrf

                                <button class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-extrabold text-red-700 transition hover:bg-red-100 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20 sm:w-auto">
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
                            <h2 class="text-lg font-extrabold text-slate-950 dark:text-white">
                                Synced Lead Forms
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">
                                Synced from Meta. Enable capture only for pilot-approved forms.
                            </p>

                            <p class="mt-1 text-xs font-bold text-orange-700 dark:text-orange-200">
                                Use Enable Capture only for pilot-approved forms. Disabled forms stay synced here but will not create CRM leads.
                            </p>
                        </div>

                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10">
                            {{ count($connectedForms) }} Forms
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    @if(empty($connectedForms))
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-slate-950/45">
                            <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                No forms synced yet.
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600 dark:text-slate-400">
                                Connect a Facebook Page or click Refresh Forms after connecting.
                            </p>
                        </div>
                    @else
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-400/20 dark:bg-amber-500/10">
                                <p class="text-sm font-bold leading-6 text-amber-800 dark:text-amber-200">
                                    Use Enable Capture only for pilot-approved forms. Disabled forms stay synced here but will not create CRM leads.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                @foreach($connectedForms as $form)
                                    @php
                                        $formId = $form['id'] ?? null;
                                        $source = $formId ? $metaLeadSources->get($formId) : null;
                                        $captureActive = $source?->isActive() ?? false;
                                        $statusLabel = $captureActive ? 'Capture Active' : 'Capture Disabled';
                                        $statusClass = $captureActive
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
                                            : 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20';
                                    @endphp

                                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-orange-200 hover:shadow-md dark:border-white/10 dark:bg-slate-950/45 dark:hover:border-orange-400/20">
                                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_280px]">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-extrabold text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20">
                                                        Synced from Meta
                                                    </span>

                                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>

                                                    @if($source)
                                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10">
                                                            CRM Source #{{ $source->id }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <h3 class="mt-3 break-words text-base font-black text-slate-950 dark:text-white">
                                                    {{ $form['name'] ?? 'Untitled Form' }}
                                                </h3>

                                                <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                                    {{ $captureActive ? 'This form is allowed to create leads in SayaraForce.' : 'This form is synced for visibility but cannot create leads yet.' }}
                                                </p>

                                                <div class="mt-4 grid grid-cols-1 gap-2 text-xs font-semibold text-slate-700 md:grid-cols-3 dark:text-slate-300">
                                                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/10 dark:bg-slate-900/55">
                                                        <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-500">Form ID</p>
                                                        <p class="mt-1 break-all text-slate-800 dark:text-slate-200">{{ $formId ?? '-' }}</p>
                                                    </div>

                                                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/10 dark:bg-slate-900/55">
                                                        <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-500">Meta Status</p>
                                                        <p class="mt-1 text-slate-800 dark:text-slate-200">{{ $form['status'] ?? 'Available' }}</p>
                                                    </div>

                                                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/10 dark:bg-slate-900/55">
                                                        <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-500">Created</p>
                                                        <p class="mt-1 break-words text-slate-800 dark:text-slate-200">{{ $form['created_time'] ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-slate-900/55">
                                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Capture Control
                                                </p>

                                                <div class="mt-3 flex items-center justify-between gap-3">
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </div>

                                                <p class="mt-3 text-xs font-semibold leading-5 text-slate-600 dark:text-slate-300">
                                                    {{ $captureActive ? 'Disable this form to stop new CRM leads from this Meta form.' : 'Enable this form only when it is approved for the pilot.' }}
                                                </p>

                                                <div class="mt-4">
                                                @if($source)
                                                    <form method="POST" action="{{ route('admin.lead-sources.meta.forms.capture', $source) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <input type="hidden" name="status" value="{{ $captureActive ? 'inactive' : 'active' }}">

                                                        <button type="submit"
                                                                class="inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $captureActive ? 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20' : 'bg-orange-500 text-white shadow-lg shadow-orange-500/20 hover:bg-orange-600' }}">
                                                            {{ $captureActive ? 'Disable Capture' : 'Enable Capture' }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-extrabold text-red-700 dark:border-red-400/20 dark:bg-red-500/10 dark:text-red-200">
                                                        Source Missing
                                                    </span>
                                                @endif
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-4">

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-base font-extrabold text-slate-950 dark:text-white">
                        How this works
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="space-y-3 text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                        <li class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500"></span>
                            <span>Connect a Facebook Page and sync available lead forms.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500"></span>
                            <span>New forms become Meta lead sources, disabled by default.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500"></span>
                            <span>Only Capture Active forms can create CRM leads.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-orange-200 bg-orange-50/80 p-5 shadow-sm dark:border-orange-400/20 dark:bg-orange-500/10">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-xs font-black text-orange-700 dark:bg-orange-500/15 dark:text-orange-200">
                        API
                    </span>
                    <div>
                <h2 class="text-base font-extrabold text-orange-800 dark:text-orange-200">
                    Required Meta Permission
                </h2>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-700 dark:text-orange-100/80">
                    The Meta app must have access to pages_show_list, pages_read_engagement, pages_manage_metadata, and leads_retrieval for this flow to work correctly.
                </p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-400/20 dark:bg-blue-500/10">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                        CRM
                    </span>
                    <div>
                <h2 class="text-base font-extrabold text-blue-800 dark:text-blue-200">
                    Next Step
                </h2>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-700 dark:text-blue-100/80">
                    After this page works, we move to webhook ingest and dedupe logic so real Meta leads create CRM leads correctly.
                </p>
                    </div>
                </div>
            </section>

        </aside>
    </div>
</div>
@endsection

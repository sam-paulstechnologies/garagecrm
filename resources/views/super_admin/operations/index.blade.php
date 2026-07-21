@extends('super_admin.layout')

@section('super_admin_content')
    <style>
        .ops-shell { min-height: 720px; }
        .ops-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.25rem; align-items: start; }
        .ops-toolbar { position: sticky; top: 0.75rem; z-index: 30; backdrop-filter: blur(14px); }
        .ops-toolbar-grid { display: grid; grid-template-columns: minmax(260px, 1fr) 190px 90px 130px; gap: .75rem; align-items: center; }
        .ops-graph-frame { position: relative; min-height: 680px; overflow: hidden; }
        .ops-canvas { position: relative; min-height: 680px; transform-origin: 0 0; transition: transform .18s ease; }
        .ops-edge-layer { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
        .ops-node {
            position: absolute; width: 190px; min-height: 88px; border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .28); background: rgba(15, 23, 42, .88);
            box-shadow: 0 18px 48px rgba(2, 6, 23, .22); color: #f8fafc; cursor: grab;
            text-align: left; padding: 12px; transition: border-color .16s ease, transform .16s ease, opacity .16s ease;
        }
        .ops-node:focus, .ops-node.is-selected { border-color: #34d399; outline: 3px solid rgba(52, 211, 153, .18); }
        .ops-node.is-hidden { opacity: .16; pointer-events: none; }
        .ops-node:hover { transform: translateY(-2px); }
        .ops-node small { color: #94a3b8; display: block; font-weight: 900; text-transform: uppercase; font-size: .66rem; letter-spacing: .06em; }
        .ops-node strong { display: block; margin-top: 5px; font-size: .86rem; line-height: 1.25; }
        .ops-node span { display: block; margin-top: 7px; color: #cbd5e1; font-size: .72rem; line-height: 1.35; }
        .ops-node[data-group="domain"] { border-color: rgba(251, 146, 60, .52); }
        .ops-node[data-group="workflow"] { border-color: rgba(52, 211, 153, .52); }
        .ops-node[data-group="route"] { border-color: rgba(96, 165, 250, .48); }
        .ops-node[data-group="file"] { border-color: rgba(216, 180, 254, .52); }
        .ops-minimap { position: absolute; right: 1rem; bottom: 1rem; width: 190px; height: 120px; border-radius: 16px; border: 1px solid rgba(148, 163, 184, .25); background: rgba(2, 6, 23, .72); }
        .ops-mini-dot { position: absolute; width: 5px; height: 5px; border-radius: 999px; background: #34d399; }
        .ops-detail { max-height: 680px; overflow: auto; }
        .ops-fullscreen { position: fixed !important; inset: 0 !important; z-index: 80; padding: 18px; background: #020617; overflow: auto; }
        html[data-theme="light"] .ops-node { background: #ffffff; color: #0f172a; border-color: #d9e1ec; box-shadow: 0 16px 42px rgba(15, 23, 42, .10); }
        html[data-theme="light"] .ops-node span { color: #475569; }
        html[data-theme="light"] .ops-node small { color: #64748b; }
        html[data-theme="light"] .ops-minimap { background: rgba(248, 250, 252, .92); }
        @media (max-width: 900px) {
            .ops-workspace { display: grid; grid-template-columns: 1fr; }
            .ops-toolbar-grid { grid-template-columns: 1fr; }
            .ops-graph-frame { min-height: auto; overflow: visible; }
            .ops-canvas { min-height: auto; transform: none !important; display: grid; gap: .8rem; }
            .ops-edge-layer, .ops-minimap { display: none; }
            .ops-node { position: relative; left: auto !important; top: auto !important; width: 100%; }
        }
    </style>

    <div id="ops-root" class="ops-shell" data-view="{{ $graphView }}" data-data-url="{{ route('super-admin.operations.data', [], false) }}" data-node-url="/super-admin/operations-center/api/graph/node">
        <div class="sa-card mb-5 rounded-3xl p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-orange-300">Operations Center</p>
                    <h1 class="mt-2 text-3xl font-black">{{ $title }}</h1>
                    <p class="sa-muted mt-2 max-w-4xl text-sm">{{ $subtitle }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('super-admin.operations.view', 'journey-flow') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ $view === 'journey-flow' ? 'bg-orange-500 text-white' : 'bg-white/10 text-white' }}">Journey Flow</a>
                    <a href="{{ route('super-admin.operations.view', 'mind-map') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ $view === 'mind-map' ? 'bg-orange-500 text-white' : 'bg-white/10 text-white' }}">Mind Map</a>
                    <a href="{{ route('super-admin.operations.view', 'technical-map') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ $view === 'technical-map' ? 'bg-orange-500 text-white' : 'bg-white/10 text-white' }}">Technical Map</a>
                </div>
            </div>
        </div>

        <div id="ops-workspace" class="ops-workspace">
            <section class="sa-card rounded-3xl p-4">
                <div class="ops-toolbar ops-toolbar-grid sa-soft mb-4 rounded-2xl p-3">
                    <input id="ops-search" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Search routes, workflows, controllers, files">
                    <select id="ops-group-filter" class="sa-input rounded-2xl px-4 py-3 text-sm"><option value="">All groups</option></select>
                    <button id="ops-fit" class="rounded-2xl bg-white/10 px-4 py-3 text-xs font-black text-white">Fit</button>
                    <button id="ops-fullscreen" class="rounded-2xl bg-orange-500 px-4 py-3 text-xs font-black text-white">Fullscreen</button>
                </div>
                <div id="ops-metrics" class="mb-3 grid gap-2 text-xs font-bold sm:grid-cols-4"></div>
                <div class="ops-graph-frame rounded-3xl border border-slate-500/20 bg-slate-950/30">
                    <div id="ops-canvas" class="ops-canvas">
                        <svg id="ops-edges" class="ops-edge-layer"></svg>
                    </div>
                    <div id="ops-minimap" class="ops-minimap" aria-hidden="true"></div>
                </div>
            </section>
            <aside class="sa-card ops-detail rounded-3xl p-5">
                <h2 class="text-lg font-black">Selected Node</h2>
                <div id="ops-detail" class="sa-muted mt-4 text-sm">Select a node to inspect route permissions, source files, page links, and relationships.</div>
            </aside>
        </div>
    </div>

    @include('super_admin.operations.partials.graph-renderer')
@endsection

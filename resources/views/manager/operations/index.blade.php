@extends('layouts.manager')

@section('title', $title)

@section('content')
    <style>
        .ops-shell { min-height: 720px; }
        .ops-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.25rem; align-items: start; }
        .ops-toolbar { position: sticky; top: 0.75rem; z-index: 30; backdrop-filter: blur(14px); }
        .ops-toolbar-grid { display: grid; grid-template-columns: minmax(260px, 1fr) 190px 90px 130px 150px; gap: .75rem; align-items: center; }
        .ops-card, .sa-card { background: var(--sf-surface); border: 1px solid var(--sf-border-light); border-radius: 18px; box-shadow: var(--sf-soft-shadow); color: var(--sf-text); }
        .ops-soft, .sa-soft { background: var(--sf-surface-soft); border: 1px solid var(--sf-border-light); color: var(--sf-text); }
        .sa-muted { color: var(--sf-muted); }
        .sa-label { color: var(--sf-muted); font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .sa-input { background: var(--sf-input-bg); border: 1px solid var(--sf-border-light); color: var(--sf-input-text); }
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
        .ops-minimap { position: absolute; right: 1rem; bottom: 1rem; width: 190px; height: 120px; border-radius: 16px; border: 1px solid rgba(148, 163, 184, .25); background: rgba(2, 6, 23, .72); }
        .ops-mini-dot { position: absolute; width: 5px; height: 5px; border-radius: 999px; background: #34d399; }
        .ops-detail { max-height: 680px; overflow: auto; }
        body.ops-scroll-lock { overflow: hidden; }
        .ops-fullscreen { position: fixed !important; inset: 0 !important; z-index: 4000; padding: 18px; background: var(--sf-bg); overflow: hidden; grid-template-columns: minmax(0, 1fr) 380px; }
        .ops-fullscreen .ops-detail { max-height: calc(100vh - 36px); overflow: auto; }
        .ops-fullscreen .ops-graph-frame { height: calc(100vh - 168px); }
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

    <div id="ops-root" class="ops-shell" data-view="{{ $graphView }}" data-data-url="{{ route('manager.operations.data', [], false) }}" data-node-url="/manager/operations-center/api/graph/node" data-detail-level="manager">
        <div class="ops-card mb-4 p-4 p-md-5">
            <p class="manager-eyebrow mb-2">Operations Center</p>
            <div class="d-flex flex-column flex-xl-row gap-3 justify-content-between">
                <div>
                    <h1 class="sf-page-title">{{ $title }}</h1>
                    <p class="sf-page-subtitle">{{ $subtitle }}</p>
                </div>
                <a href="{{ route('manager.dashboard') }}" class="sf-action-button orange align-self-start">Back to Dashboard</a>
            </div>
        </div>

        <div id="ops-workspace" class="ops-workspace">
            <section class="ops-card p-3">
                <div class="ops-toolbar ops-toolbar-grid ops-soft mb-3 rounded-4 p-3">
                    <input id="ops-search" class="sa-input rounded-4 px-3 py-2 text-sm" placeholder="Search journey, customer, booking, job, invoice">
                    <select id="ops-group-filter" class="sa-input rounded-4 px-3 py-2 text-sm"><option value="">All groups</option></select>
                    <button id="ops-fit" class="sf-action-button primary" type="button">Fit</button>
                    <button id="ops-fullscreen" class="sf-action-button orange" type="button">Fullscreen</button>
                    <button id="ops-detail-toggle" class="sf-action-button secondary" type="button">Collapse Details</button>
                </div>
                <div id="ops-metrics" class="mb-3 grid gap-2 text-xs font-bold sm:grid-cols-4"></div>
                <div class="ops-graph-frame rounded-4 border border-secondary-subtle">
                    <div id="ops-canvas" class="ops-canvas">
                        <svg id="ops-edges" class="ops-edge-layer"></svg>
                    </div>
                    <div id="ops-minimap" class="ops-minimap" aria-hidden="true"></div>
                </div>
            </section>
            <aside id="ops-detail-panel" class="ops-card ops-detail p-4">
                <h2 class="h5 fw-black">Selected Node</h2>
                <div id="ops-detail" class="sa-muted mt-3 text-sm">Select a node to inspect manager-safe journey details and available page links.</div>
            </aside>
        </div>
    </div>

    @include('super_admin.operations.partials.graph-renderer')
@endsection

@extends('layouts.app')

@section('content')
<style>
.lead-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}
.lead-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,.06);
    transition: all .25s ease;
}
.lead-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(0,0,0,.08);
}
.lead-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 14px;
}
.pill-green { background:#e8f5e9; color:#2e7d32; }
.pill-blue  { background:#e3f2fd; color:#1565c0; }
.pill-cyan  { background:#e0f7fa; color:#006064; }
.lead-icon { font-size: 28px; margin-bottom: 10px; }
.lead-title { font-weight: 700; font-size: 18px; margin-bottom: 6px; }
.lead-desc { color: #6b7280; font-size: 14px; margin-bottom: 18px; }
.lead-status { font-size: 13px; margin-bottom: 14px; color: #374151; }
.lead-btn {
    display: block;
    width: 100%;
    text-align: center;
    padding: 12px;
    border-radius: 10px;
    color: #fff;
    font-weight: 600;
    text-decoration: none;
}
.btn-whatsapp { background:#22c55e; }
.btn-website  { background:#2563eb; }
.btn-meta     { background:#0ea5e9; }
</style>

<div class="px-6 py-6">

    <h1 class="text-2xl font-semibold mb-1">Lead Sources</h1>
    <p class="text-sm text-gray-500 mb-6">
        Configure how leads enter GarageCRM
    </p>

    <div class="lead-grid">

        {{-- WhatsApp --}}
        <div class="lead-card">
            <div class="lead-pill pill-green">WhatsApp • Connected</div>
            <div class="lead-icon">💬</div>

            <div class="lead-title">WhatsApp Conversations</div>
            <div class="lead-desc">
                Automatically capture and manage leads directly from customer WhatsApp chats.
            </div>

            <div class="lead-status">
                Status: <strong>Auto-capture enabled</strong>
            </div>

            <a href="{{ route('admin.lead-sources.whatsapp') }}"
               class="lead-btn btn-whatsapp">
                Configure WhatsApp Flow
            </a>
        </div>

        {{-- Website --}}
        <div class="lead-card">
            <div class="lead-pill pill-blue">Website • Ready</div>
            <div class="lead-icon">🌐</div>

            <div class="lead-title">Website Forms</div>
            <div class="lead-desc">
                Capture leads from contact forms and landing pages in real time.
            </div>

            <div class="lead-status">
                Status: <strong>Forms active</strong>
            </div>

            <a href="{{ route('admin.lead-sources.website.index') }}"
               class="lead-btn btn-website">
                Manage Forms & Webhooks
            </a>
        </div>

        {{-- Meta --}}
        <div class="lead-card">
            <div class="lead-pill pill-cyan">Meta • Setup Required</div>
            <div class="lead-icon">📣</div>

            <div class="lead-title">Meta Lead Ads</div>
            <div class="lead-desc">
                Sync Facebook & Instagram Lead Ads automatically into GarageCRM.
            </div>

            <div class="lead-status">
                Status: <strong>Account not connected</strong>
            </div>

            <a href="{{ route('admin.lead-sources.meta') }}"
               class="lead-btn btn-meta">
                Connect Meta Account
            </a>
        </div>

    </div>
</div>
@endsection

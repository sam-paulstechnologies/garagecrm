@extends('layouts.app')

@section('title', 'Custom Website Lead Form')

@push('styles')
<style>
    .sf-lead-custom-form-page .sf-card,
    .sf-lead-custom-form-page .sf-lead-code-panel {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(15, 23, 42, 0.86);
        color: #e2e8f0;
    }

    .sf-lead-custom-form-page .sf-card-header,
    .sf-lead-custom-form-page .sf-lead-code-header {
        border-color: rgba(255, 255, 255, 0.10);
    }

    .sf-lead-custom-form-page .sf-btn-secondary {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    html[data-theme="light"] .sf-lead-custom-form-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-card,
    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-code-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-card-header,
    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-code-header {
        border-color: #e2e8f0 !important;
        background: linear-gradient(180deg, #ffffff, #f8fafc) !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-section-title,
    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-panel-title,
    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-code-panel code {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-section-subtitle,
    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-muted {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-lead-code-panel pre {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .text-green-300 {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .text-yellow-300 {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .text-orange-100\/80,
    html[data-theme="light"] .sf-lead-custom-form-page .text-blue-100\/80,
    html[data-theme="light"] .sf-lead-custom-form-page .text-green-100\/80,
    html[data-theme="light"] .sf-lead-custom-form-page .text-yellow-100\/80 {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .bg-green-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .bg-yellow-500\/10 {
        background: #fefce8 !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .border-white\/10,
    html[data-theme="light"] .sf-lead-custom-form-page .border-orange-400\/20,
    html[data-theme="light"] .sf-lead-custom-form-page .border-blue-400\/20,
    html[data-theme="light"] .sf-lead-custom-form-page .border-green-400\/20,
    html[data-theme="light"] .sf-lead-custom-form-page .border-yellow-400\/20 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-lead-custom-form-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }
</style>
@endpush

@section('content')
<div class="sf-page sf-lead-custom-form-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Capture
            </div>

            <h1 class="sf-page-title mt-3">
                Custom Website Lead Form
            </h1>

            <p class="sf-page-subtitle">
                Embed this snippet on your website to capture leads directly into SayaraForce.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                ← Back to Leads
            </a>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Embed Code --}}
        <div class="lg:col-span-2">
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Website Embed Snippet
                    </h2>

                    <p class="sf-section-subtitle">
                        Copy and paste this code into the page where you want the lead form to appear.
                    </p>
                </div>

                <div class="sf-card-body space-y-5">

                    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5">
                        <div class="text-sm font-extrabold text-orange-300">
                            Embed Code
                        </div>

                        <p class="mt-1 text-xs font-medium leading-5 text-orange-100/80">
                            This snippet connects the website form to your company account.
                        </p>
                    </div>

                    <div class="sf-lead-code-panel overflow-hidden rounded-3xl border border-white/10 bg-slate-950 shadow-xl shadow-black/20">
                        <div class="sf-lead-code-header flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3">
                            <div>
                                <div class="sf-lead-panel-title text-sm font-extrabold text-white">
                                    HTML Snippet
                                </div>
                                <div class="sf-lead-muted text-xs font-medium text-slate-500">
                                    Paste before closing body tag or inside your page content.
                                </div>
                            </div>

                            <span class="sf-badge-orange">
                                Company ID: {{ auth()->user()->company_id }}
                            </span>
                        </div>

                        <pre class="overflow-x-auto p-5 text-sm leading-7 text-slate-100"><code>&lt;script src="https://sayaraforce.com/embed/lead-form.js"&gt;&lt;/script&gt;
&lt;div data-sayaraforce-form="{{ auth()->user()->company_id }}"&gt;&lt;/div&gt;</code></pre>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                            <div class="font-extrabold text-blue-300">
                                What it does
                            </div>

                            <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                                Captures customer name, phone, email, and enquiry details from your website.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                            <div class="font-extrabold text-green-300">
                                Where leads go
                            </div>

                            <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                                Leads will appear under your Admin Leads section for follow-up and qualification.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Side Instructions --}}
        <div class="space-y-6">

            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Setup Steps
                    </h2>

                    <p class="sf-section-subtitle">
                        Follow this sequence when adding the form.
                    </p>
                </div>

                <div class="sf-card-body">
                    <ul class="sf-lead-muted space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                1
                            </span>
                            <span>Copy the embed snippet from this page.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                2
                            </span>
                            <span>Paste it into your website page where the form should appear.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                3
                            </span>
                            <span>Submit a test lead from your website.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                4
                            </span>
                            <span>Check if the lead appears in the Leads page.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="rounded-3xl border border-yellow-400/20 bg-yellow-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-yellow-300">
                    Important
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                    Use this snippet only on trusted websites. The company ID connects the submitted lead to your account.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Testing Tip
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    After embedding, submit a test lead using a phone number that is not already in the CRM to confirm the flow.
                </p>
            </div>

        </div>

    </div>

</div>
@endsection

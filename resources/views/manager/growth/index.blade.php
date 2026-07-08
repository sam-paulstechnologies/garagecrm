@extends('layouts.manager')

@section('title', 'Manager Growth')

@section('content')

    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                     style="background: rgba(234, 88, 12, 0.14); color: #fdba74; font-size: 12px; font-weight: 900;">
                    Manager View · Read-only v1
                </div>

                <h1 class="sf-page-title">
                    Growth Overview
                </h1>

                <p class="sf-page-subtitle" style="max-width: 760px;">
                    Track operational growth signals from leads, WhatsApp conversations, and completed jobs.
                    This page is manager-safe and does not expose admin-only campaign or system settings.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('manager.inbox.index') }}" class="sf-action-button light">
                    Open Inbox
                </a>

                <a href="{{ route('manager.leads.index') }}" class="sf-action-button primary">
                    View Leads
                </a>
            </div>
        </div>
    </div>

    {{-- Growth Cards --}}
    <div class="row g-3 mb-4">
        @foreach ($growthCards as $card)
            @php
                $tone = $card['tone'] ?? 'blue';

                $toneMap = [
                    'blue' => [
                        'gradient' => 'linear-gradient(135deg, #2563eb, #60a5fa)',
                        'icon' => '#',
                    ],
                    'orange' => [
                        'gradient' => 'linear-gradient(135deg, #ea580c, #fb923c)',
                        'icon' => '↗',
                    ],
                    'green' => [
                        'gradient' => 'linear-gradient(135deg, #16a34a, #86efac)',
                        'icon' => '✓',
                    ],
                    'purple' => [
                        'gradient' => 'linear-gradient(135deg, #9333ea, #c084fc)',
                        'icon' => '✉',
                    ],
                ];

                $style = $toneMap[$tone] ?? $toneMap['blue'];
            @endphp

            <div class="col-12 col-md-6 col-xl-3">
                <div class="sf-stat-card h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <p class="sf-stat-label">
                                {{ $card['label'] }}
                            </p>

                            <h2 class="sf-stat-value">
                                {{ number_format((int) $card['value']) }}
                            </h2>

                            <p class="sf-stat-help">
                                {{ $card['helper'] }}
                            </p>
                        </div>

                        <div class="d-flex align-items-center justify-content-center text-white"
                             style="width: 48px; height: 48px; border-radius: 18px; background: {{ $style['gradient'] }}; font-weight: 900; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);">
                            {{ $style['icon'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">

        {{-- Lead Growth --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Lead Growth</h2>
                        <p class="sf-panel-subtitle">Lead quality and movement summary.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="d-grid gap-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Total Leads</span>
                            <span class="fw-black text-dark">{{ number_format($leadBreakdown['total']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Today</span>
                            <span class="fw-black text-dark">{{ number_format($leadBreakdown['today']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">New This Month</span>
                            <span class="fw-black text-dark">{{ number_format($leadBreakdown['new_this_month']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Qualified / Assigned</span>
                            <span class="fw-black text-success">{{ number_format($leadBreakdown['qualified']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Disqualified</span>
                            <span class="fw-black text-danger">{{ number_format($leadBreakdown['disqualified']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Hot Leads</span>
                            <span class="fw-black" style="color: #ea580c;">
                                {{ number_format($leadBreakdown['hot']) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('manager.leads.index') }}" class="sf-action-button primary w-100">
                            Open Leads
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- WhatsApp Activity --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">WhatsApp Activity</h2>
                        <p class="sf-panel-subtitle">Conversation and reply movement.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="d-grid gap-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Total Conversations</span>
                            <span class="fw-black text-dark">{{ number_format($conversationBreakdown['total']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Active Last 30 Days</span>
                            <span class="fw-black text-dark">{{ number_format($conversationBreakdown['active_last_30_days']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Unread Conversations</span>
                            <span class="fw-black" style="color: #ea580c;">
                                {{ number_format($conversationBreakdown['unread']) }}
                            </span>
                        </div>

                        <hr class="my-1">

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Messages Last 30 Days</span>
                            <span class="fw-black text-dark">{{ number_format($whatsappBreakdown['total_last_30_days']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Inbound</span>
                            <span class="fw-black text-primary">{{ number_format($whatsappBreakdown['inbound_last_30_days']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Outbound</span>
                            <span class="fw-black text-success">{{ number_format($whatsappBreakdown['outbound_last_30_days']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Human Replies</span>
                            <span class="fw-black" style="color: #9333ea;">
                                {{ number_format($whatsappBreakdown['human_replies_last_30_days']) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('manager.inbox.index') }}" class="sf-action-button orange w-100">
                            Open Inbox
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Job Conversion --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Job Conversion</h2>
                        <p class="sf-panel-subtitle">Operational conversion signals.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @php
                        $completionRate = $jobBreakdown['total'] > 0
                            ? round(($jobBreakdown['completed'] / $jobBreakdown['total']) * 100)
                            : 0;
                    @endphp

                    <div class="d-grid gap-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Total Jobs</span>
                            <span class="fw-black text-dark">{{ number_format($jobBreakdown['total']) }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted fw-bold">Completed Jobs</span>
                            <span class="fw-black text-success">{{ number_format($jobBreakdown['completed']) }}</span>
                        </div>

                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted fw-bold">Completion Rate</span>
                                <span class="fw-black text-dark">{{ $completionRate }}%</span>
                            </div>

                            <div style="height: 10px; border-radius: 999px; background: #e5e7eb; overflow: hidden;">
                                <div style="height: 100%; width: {{ min($completionRate, 100) }}%; border-radius: 999px; background: linear-gradient(90deg, #2563eb, #ea580c);"></div>
                            </div>
                        </div>

                        <div class="p-3 rounded-4"
                             style="background: #f8fafc; border: 1px solid #e5e7eb;">
                            <div class="fw-black text-dark mb-1">Manager Note</div>
                            <p class="mb-0 text-muted fw-semibold" style="line-height: 1.7;">
                                This page is focused on operational performance only.
                                Campaign creation, template changes, and automation configuration remain Admin-only.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('manager.jobs.index') }}" class="sf-action-button primary w-100">
                            Open Jobs
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Safe Access Notice --}}
    <div class="mt-4 rounded-4 overflow-hidden"
         style="background: #0f172a; box-shadow: 0 18px 50px rgba(15, 23, 42, 0.16);">
        <div class="p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h2 class="text-white fw-black mb-2">
                        Manager-safe Growth Access
                    </h2>

                    <p class="mb-0 text-white-50 fw-semibold" style="max-width: 820px; line-height: 1.7;">
                        Managers can view growth performance and act on leads, conversations, bookings, and jobs.
                        Admin-only controls like campaign creation, WhatsApp mappings, API credentials, and automation rules are intentionally hidden.
                    </p>
                </div>

                <a href="{{ route('manager.dashboard') }}" class="sf-action-button light">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

@endsection

@extends('layouts.manager')

@section('title', 'Manager Settings')

@section('content')

    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                     style="background: rgba(37, 99, 235, 0.14); color: #93c5fd; font-size: 12px; font-weight: 900;">
                    Manager View · Safe Operational Settings
                </div>

                <h1 class="sf-page-title">
                    Manager Settings
                </h1>

                <p class="sf-page-subtitle" style="max-width: 780px;">
                    This page is reserved for manager-safe operational settings only.
                    Admin-only settings like WhatsApp credentials, Meta webhooks, billing, AI policy,
                    payment gateway, and role permissions are intentionally hidden.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('manager.growth.index') }}" class="sf-action-button light">
                    Growth
                </a>

                <a href="{{ route('manager.dashboard') }}" class="sf-action-button primary">
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Manager User / Access Summary --}}
    <div class="sf-panel mb-4">
        <div class="sf-panel-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center text-white"
                         style="width: 58px; height: 58px; border-radius: 22px; background: linear-gradient(135deg, #2563eb, #ea580c); font-weight: 950; box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);">
                        {{ strtoupper(substr($user->name ?? 'M', 0, 1)) }}
                    </div>

                    <div>
                        <h2 class="mb-1 fw-black text-dark">
                            {{ $user->name ?? 'Manager' }}
                        </h2>

                        <p class="mb-0 text-muted fw-semibold">
                            {{ $user->email ?? 'Manager account' }}
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge"
                          style="background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                        Role: Manager
                    </span>

                    <span class="badge"
                          style="background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                        Restricted Admin Controls
                    </span>

                    <span class="badge"
                          style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                        Operational Access
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Cards --}}
    <div class="row g-3 mb-4">
        @foreach ($settingsCards as $card)
            @php
                $tone = $card['tone'] ?? 'blue';

                $toneMap = [
                    'blue' => [
                        'icon' => '⏰',
                        'gradient' => 'linear-gradient(135deg, #2563eb, #60a5fa)',
                        'badgeBg' => 'rgba(37, 99, 235, 0.12)',
                        'badgeColor' => '#2563eb',
                        'buttonBg' => 'rgba(37, 99, 235, 0.08)',
                        'buttonColor' => '#2563eb',
                    ],
                    'orange' => [
                        'icon' => '🛠',
                        'gradient' => 'linear-gradient(135deg, #ea580c, #fb923c)',
                        'badgeBg' => 'rgba(234, 88, 12, 0.12)',
                        'badgeColor' => '#ea580c',
                        'buttonBg' => 'rgba(234, 88, 12, 0.08)',
                        'buttonColor' => '#ea580c',
                    ],
                    'green' => [
                        'icon' => '🔔',
                        'gradient' => 'linear-gradient(135deg, #16a34a, #86efac)',
                        'badgeBg' => 'rgba(22, 163, 74, 0.12)',
                        'badgeColor' => '#16a34a',
                        'buttonBg' => 'rgba(22, 163, 74, 0.08)',
                        'buttonColor' => '#16a34a',
                    ],
                    'purple' => [
                        'icon' => '💬',
                        'gradient' => 'linear-gradient(135deg, #9333ea, #c084fc)',
                        'badgeBg' => 'rgba(147, 51, 234, 0.12)',
                        'badgeColor' => '#9333ea',
                        'buttonBg' => 'rgba(147, 51, 234, 0.08)',
                        'buttonColor' => '#9333ea',
                    ],
                ];

                $style = $toneMap[$tone] ?? $toneMap['blue'];
            @endphp

            <div class="col-12 col-md-6 col-xl-3">
                <div class="sf-panel h-100">
                    <div class="sf-panel-body">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <div class="d-flex align-items-center justify-content-center text-white"
                                 style="width: 48px; height: 48px; border-radius: 18px; background: {{ $style['gradient'] }}; font-weight: 900; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);">
                                {{ $style['icon'] }}
                            </div>

                            <span class="badge"
                                  style="background: {{ $style['badgeBg'] }}; color: {{ $style['badgeColor'] }};">
                                {{ $card['status'] }}
                            </span>
                        </div>

                        <h2 class="sf-panel-title mb-2">
                            {{ $card['title'] }}
                        </h2>

                        <p class="text-muted fw-semibold mb-4" style="line-height: 1.7; min-height: 72px;">
                            {{ $card['description'] }}
                        </p>

                        <button type="button"
                                disabled
                                class="w-100 border-0 rounded-3 py-2 fw-black"
                                style="background: {{ $style['buttonBg'] }}; color: {{ $style['buttonColor'] }}; opacity: 0.7; cursor: not-allowed;">
                            Configure Later
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">

        {{-- Allowed Manager Settings --}}
        <div class="col-12 col-xl-8">
            <div class="sf-panel h-100">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Allowed Manager Settings</h2>
                        <p class="sf-panel-subtitle">
                            These are the areas we can safely expose to managers in the next version.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 h-100"
                                 style="background: #f8fafc; border: 1px solid #e5e7eb;">
                                <div class="fw-black text-dark mb-2">
                                    Business Hours
                                </div>

                                <p class="mb-0 text-muted fw-semibold" style="line-height: 1.7;">
                                    Let managers update operational opening hours used for appointment planning and service availability.
                                </p>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 h-100"
                                 style="background: #f8fafc; border: 1px solid #e5e7eb;">
                                <div class="fw-black text-dark mb-2">
                                    Service Availability
                                </div>

                                <p class="mb-0 text-muted fw-semibold" style="line-height: 1.7;">
                                    Control service slots, capacity, pickup availability, and booking visibility for customers.
                                </p>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 h-100"
                                 style="background: #f8fafc; border: 1px solid #e5e7eb;">
                                <div class="fw-black text-dark mb-2">
                                    Notification Preferences
                                </div>

                                <p class="mb-0 text-muted fw-semibold" style="line-height: 1.7;">
                                    Decide which manager alerts should appear for leads, bookings, jobs, invoices, and unread conversations.
                                </p>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 h-100"
                                 style="background: #f8fafc; border: 1px solid #e5e7eb;">
                                <div class="fw-black text-dark mb-2">
                                    Quick Replies
                                </div>

                                <p class="mb-0 text-muted fw-semibold" style="line-height: 1.7;">
                                    Create safe reusable reply snippets for manager-side customer conversations.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Restricted Settings --}}
        <div class="col-12 col-xl-4">
            <div class="h-100 rounded-4 overflow-hidden"
                 style="background: #0f172a; box-shadow: 0 18px 50px rgba(15, 23, 42, 0.16);">
                <div class="p-4 border-bottom" style="border-color: rgba(255, 255, 255, 0.08) !important;">
                    <h2 class="text-white fw-black mb-1">
                        Admin-only Settings
                    </h2>

                    <p class="mb-0 text-white-50 fw-semibold">
                        Hidden from manager access.
                    </p>
                </div>

                <div class="p-4">
                    <div class="d-grid gap-3">
                        @foreach ($restrictedSettings as $item)
                            <div class="d-flex align-items-start gap-3">
                                <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width: 22px; height: 22px; border-radius: 999px; background: rgba(220, 38, 38, 0.14); color: #fca5a5; font-size: 11px; font-weight: 900;">
                                    !
                                </div>

                                <div class="text-white fw-bold" style="font-size: 13px; line-height: 1.5;">
                                    {{ $item }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Next Build Plan --}}
    <div class="sf-panel mt-4">
        <div class="sf-panel-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h2 class="fw-black text-dark mb-2">
                        Next Step
                    </h2>

                    <p class="mb-0 text-muted fw-semibold" style="max-width: 820px; line-height: 1.7;">
                        This page currently loads safely as a manager-safe settings landing page.
                        Next, we can decide which setting to make editable first: business hours,
                        notifications, service availability, or quick replies.
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('manager.growth.index') }}" class="sf-action-button orange">
                        Open Growth
                    </a>

                    <a href="{{ route('manager.inbox.index') }}" class="sf-action-button primary">
                        Open Inbox
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
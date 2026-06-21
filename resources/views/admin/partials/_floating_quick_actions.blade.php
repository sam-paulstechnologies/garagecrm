{{-- resources/views/admin/partials/_floating_quick_actions.blade.php --}}

@php
    $quickActions = [
        [
            'label' => 'Add Client',
            'route' => 'admin.clients.create',
            'fallback' => url('/admin/clients/create'),
            'icon' => 'client',
            'tone' => 'blue',
        ],
        [
            'label' => 'Add Lead',
            'route' => 'admin.leads.create',
            'fallback' => url('/admin/leads/create'),
            'icon' => 'lead',
            'tone' => 'blue',
        ],
        [
            'label' => 'New Opportunity',
            'route' => 'admin.opportunities.create',
            'fallback' => url('/admin/opportunities/create'),
            'icon' => 'opportunity',
            'tone' => 'purple',
        ],
        [
            'label' => 'New Booking',
            'route' => 'admin.bookings.create',
            'fallback' => url('/admin/bookings/create'),
            'icon' => 'booking',
            'tone' => 'indigo',
        ],
        [
            'label' => 'New Job',
            'route' => 'admin.jobs.create',
            'fallback' => url('/admin/jobs/create'),
            'icon' => 'job',
            'tone' => 'emerald',
        ],
        [
            'label' => 'New Invoice',
            'route' => 'admin.invoices.create',
            'fallback' => url('/admin/invoices/create'),
            'icon' => 'invoice',
            'tone' => 'rose',
        ],
    ];

    $iconTones = [
        'blue' => 'text-blue-200 ring-blue-300/20 group-hover:text-blue-100',
        'purple' => 'text-purple-200 ring-purple-300/20 group-hover:text-purple-100',
        'indigo' => 'text-indigo-200 ring-indigo-300/20 group-hover:text-indigo-100',
        'emerald' => 'text-emerald-200 ring-emerald-300/20 group-hover:text-emerald-100',
        'rose' => 'text-rose-200 ring-rose-300/20 group-hover:text-rose-100',
    ];
@endphp

<div class="sf-floating-quick-action-shell fixed z-[9999]">
    <div class="sf-floating-quick-actions overflow-visible rounded-l-2xl border border-r-0 border-orange-500/50 bg-emerald-950/95 shadow-2xl backdrop-blur">
        @foreach ($quickActions as $action)
            @php
                $href = \Illuminate\Support\Facades\Route::has($action['route'])
                    ? route($action['route'])
                    : $action['fallback'];
                $isActive = request()->routeIs($action['route']);
                $iconTone = $iconTones[$action['tone'] ?? 'blue'] ?? $iconTones['blue'];
            @endphp

            <a
                href="{{ $href }}"
                aria-label="{{ $action['label'] }}"
                class="group relative flex h-16 w-16 items-center justify-center border-b border-white/10 text-white transition last:border-b-0 first:rounded-tl-2xl last:rounded-bl-2xl {{ $isActive ? 'sf-floating-action-active' : '' }}"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-900/45 ring-1 transition group-hover:scale-105 group-hover:bg-emerald-900/20 {{ $iconTone }}">
                    {{-- Client Icon --}}
                    @if ($action['icon'] === 'client')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 0-8 0" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 21a7 7 0 0 1 14 0" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8h3m-1.5-1.5v3" />
                        </svg>

                    {{-- Lead Icon --}}
                    @elseif ($action['icon'] === 'lead')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>

                    {{-- Opportunity Icon --}}
                    @elseif ($action['icon'] === 'opportunity')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.07 4.93l-2.83 2.83M7.76 16.24l-2.83 2.83" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8" />
                        </svg>

                    {{-- Booking Icon --}}
                    @elseif ($action['icon'] === 'booking')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4M16 2v4M3 10h18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                        </svg>

                    {{-- Job Icon --}}
                    @elseif ($action['icon'] === 'job')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 0 0-5 5L3 18v3h3l6.7-6.7a4 4 0 0 0 5-5l-2.4 2.4-2.8-2.8 2.2-2.6Z" />
                        </svg>

                    {{-- Invoice Icon --}}
                    @else
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-3-2V5a2 2 0 0 1 2-2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 12h6M9 16h4" />
                        </svg>
                    @endif
                </span>

                {{-- Slide-out label --}}
                <span class="sf-floating-tooltip">
                    {{ $action['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</div>

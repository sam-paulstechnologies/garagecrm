{{-- resources/views/admin/partials/_floating_quick_actions.blade.php --}}

@php
    $quickActions = [
        [
            'label' => 'Add Client',
            'route' => 'admin.clients.create',
            'fallback' => url('/admin/clients/create'),
            'icon' => 'client',
        ],
        [
            'label' => 'Add Lead',
            'route' => 'admin.leads.create',
            'fallback' => url('/admin/leads/create'),
            'icon' => 'lead',
        ],
        [
            'label' => 'New Opportunity',
            'route' => 'admin.opportunities.create',
            'fallback' => url('/admin/opportunities/create'),
            'icon' => 'opportunity',
        ],
        [
            'label' => 'New Booking',
            'route' => 'admin.bookings.create',
            'fallback' => url('/admin/bookings/create'),
            'icon' => 'booking',
        ],
        [
            'label' => 'New Invoice',
            'route' => 'admin.invoices.create',
            'fallback' => url('/admin/invoices/create'),
            'icon' => 'invoice',
        ],
    ];
@endphp

<div class="fixed right-0 top-1/2 z-[9999] hidden -translate-y-1/2 lg:block">
    <div class="sf-floating-quick-actions overflow-visible rounded-l-2xl border border-r-0 border-orange-500/30 bg-emerald-950/95 shadow-2xl backdrop-blur">
        @foreach ($quickActions as $action)
            @php
                $href = \Illuminate\Support\Facades\Route::has($action['route'])
                    ? route($action['route'])
                    : $action['fallback'];
            @endphp

            <a
                href="{{ $href }}"
                title="{{ $action['label'] }}"
                class="group relative flex h-16 w-16 items-center justify-center border-b border-white/10 text-white transition last:border-b-0 hover:bg-orange-500 first:rounded-tl-2xl last:rounded-bl-2xl"
            >
                {{-- Client Icon --}}
                @if ($action['icon'] === 'client')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 0-8 0" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 21a7 7 0 0 1 14 0" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 8h3m-1.5-1.5v3" />
                    </svg>

                {{-- Lead Icon --}}
                @elseif ($action['icon'] === 'lead')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 13h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l3 3" />
                    </svg>

                {{-- Opportunity Icon --}}
                @elseif ($action['icon'] === 'opportunity')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 21h14" />
                    </svg>

                {{-- Booking Icon --}}
                @elseif ($action['icon'] === 'booking')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 11h16" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 16 2 2 4-5" />
                    </svg>

                {{-- Invoice Icon --}}
                @else
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-3 2-3-2-3 2V5a2 2 0 0 1 2-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 12h6M9 16h4" />
                    </svg>
                @endif

                {{-- Slide-out label --}}
                <span class="pointer-events-none absolute right-20 top-1/2 hidden -translate-y-1/2 whitespace-nowrap rounded-l-xl rounded-r-md bg-slate-950 px-4 py-3 text-xs font-extrabold text-white shadow-xl ring-1 ring-white/10 group-hover:block">
                    {{ $action['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</div>
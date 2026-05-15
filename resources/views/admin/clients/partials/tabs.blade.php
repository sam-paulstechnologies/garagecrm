{{-- resources/views/admin/clients/partials/tabs.blade.php --}}

@php
    $tabs = [
        [
            'label' => 'Overview',
            'href' => '#client-overview',
            'icon' => '📌',
        ],
        [
            'label' => 'Vehicles',
            'href' => '#client-vehicles',
            'icon' => '🚗',
        ],
        [
            'label' => 'Service History',
            'href' => '#client-service-history',
            'icon' => '🛠️',
        ],
        [
            'label' => 'Leads',
            'href' => '#client-leads',
            'icon' => '🎯',
        ],
        [
            'label' => 'Opportunities',
            'href' => '#client-opportunities',
            'icon' => '💼',
        ],
        [
            'label' => 'Bookings',
            'href' => '#client-bookings',
            'icon' => '📅',
        ],
        [
            'label' => 'Communications',
            'href' => '#client-communications',
            'icon' => '💬',
        ],
        [
            'label' => 'Documents',
            'href' => '#client-documents',
            'icon' => '📄',
        ],
        [
            'label' => 'Invoices',
            'href' => '#client-invoices',
            'icon' => '🧾',
        ],
        [
            'label' => 'Notes',
            'href' => '#client-notes',
            'icon' => '📝',
        ],
        [
            'label' => 'Activity',
            'href' => '#client-activity',
            'icon' => '⚡',
        ],
    ];
@endphp

<div class="space-y-4" id="client-overview">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Client Workspace
            </h2>

            <p class="sf-section-subtitle">
                Jump to client vehicles, bookings, invoices, notes, communications, and activity.
            </p>
        </div>

        <span class="sf-badge-orange">
            Quick Navigation
        </span>
    </div>

    {{-- Desktop / Tablet Navigation --}}
    <div class="hidden flex-wrap gap-2 md:flex">
        @foreach($tabs as $tab)
            <a href="{{ $tab['href'] }}"
               class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-2 text-sm font-extrabold text-slate-300 transition hover:border-orange-400/30 hover:bg-orange-500/10 hover:text-orange-300">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Mobile Select --}}
    <div class="md:hidden">
        <label for="client-section-jump" class="sf-label">
            Jump to section
        </label>

        <select id="client-section-jump"
                class="sf-select"
                onchange="if(this.value) window.location.href = this.value;">
            <option value="">Select section...</option>

            @foreach($tabs as $tab)
                <option value="{{ $tab['href'] }}">
                    {{ $tab['icon'] }} {{ $tab['label'] }}
                </option>
            @endforeach
        </select>
    </div>

</div>
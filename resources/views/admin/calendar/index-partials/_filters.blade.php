@php
    $filters = $calendarFilters ?? [];
@endphp

<div
    id="sfCalendarFilters"
    class="sf-card sf-calendar-filter-panel"
    data-calendar-filter-panel
>
    <div class="sf-card-header sf-calendar-filter-summary" data-calendar-panel-summary>
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <h2 class="sf-section-title">Search & Filter Calendar</h2>

            <span class="sf-calendar-filter-pill">
                {{ $calendarStatuses[$filters['status'] ?? 'all'] ?? 'All booking calendar items' }}
            </span>

            <span class="sf-calendar-filter-pill">
                {{ $calendarSlots[$filters['slot'] ?? 'all'] ?? 'All slots' }}
            </span>

            @if(($filters['assigned_user'] ?? 'all') !== 'all')
                <span class="sf-calendar-filter-pill">
                    {{ ($filters['assigned_user'] ?? '') === 'unassigned' ? 'Unassigned' : 'Assigned user selected' }}
                </span>
            @endif
        </div>

        <button
            type="button"
            class="sf-btn-secondary"
            data-calendar-panel-toggle
            aria-expanded="false"
        >
            Show Filters
        </button>
    </div>

    <div class="sf-card-body hidden" data-calendar-panel-body>
        <div class="sf-calendar-filters-grid">
            <label class="sf-calendar-filter-field">
                <span>Assigned User</span>
                <select data-calendar-filter="assigned_user">
                    <option value="all" @selected(($filters['assigned_user'] ?? 'all') === 'all')>All users</option>
                    <option value="unassigned" @selected(($filters['assigned_user'] ?? 'all') === 'unassigned')>Unassigned</option>
                    @foreach($calendarAssignedUsers ?? [] as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['assigned_user'] ?? '') === (string) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="sf-calendar-filter-field">
                <span>Booking State</span>
                <select data-calendar-filter="status">
                    @foreach($calendarStatuses ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="sf-calendar-filter-field">
                <span>Slot</span>
                <select data-calendar-filter="slot">
                    @foreach($calendarSlots ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['slot'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-white/10 pt-4">
            <button type="button" class="sf-btn-secondary" data-calendar-reset>
                Reset
            </button>
        </div>
    </div>
</div>

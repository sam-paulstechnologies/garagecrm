@php
    $filters = $calendarFilters ?? [];
@endphp

<div class="sf-card sf-calendar-filter-panel">
    <div class="sf-card-header">
        <div>
            <h2 class="sf-section-title">Calendar Filters</h2>
            <p class="sf-section-subtitle">Filter the read-only feed without leaving the calendar.</p>
        </div>

        <div class="flex flex-wrap gap-2 text-xs font-black">
            <span class="sf-calendar-stat-chip">Manager Confirmation</span>
            <span class="sf-calendar-stat-chip">Booking Confirmed</span>
            <span class="sf-calendar-stat-chip">Rescheduling Required</span>
            <span class="sf-calendar-stat-chip">Unassigned</span>
        </div>
    </div>

    <div class="sf-card-body">
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
    </div>
</div>

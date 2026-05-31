{{-- resources/views/admin/bookings/index-partials/_styles.blade.php --}}

<style>
    .sf-bookings-page {
        color: #e2e8f0;
    }

    .sf-booking-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-booking-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-booking-title,
    .sf-booking-value,
    .sf-booking-table td {
        color: #f8fafc;
    }

    .sf-booking-muted,
    .sf-booking-table th {
        color: #94a3b8;
    }

    .sf-booking-input,
    .sf-booking-select {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-booking-input::placeholder {
        color: #64748b;
    }

    .sf-booking-input:focus,
    .sf-booking-select:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-bookings-page .sf-btn-primary,
    .sf-bookings-page .sf-btn-secondary,
    .sf-bookings-page .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-bookings-page .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
    }

    .sf-bookings-page .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-bookings-page .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-bookings-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-bookings-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-booking-accent-title,
    .sf-booking-accent-value {
        color: #f8fafc;
    }

    .sf-booking-accent-muted {
        color: rgba(226, 232, 240, 0.78);
    }

    .sf-booking-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9);
    }

    html[data-theme="light"] .sf-bookings-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-booking-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-booking-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-booking-title,
    html[data-theme="light"] .sf-booking-value,
    html[data-theme="light"] .sf-booking-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-muted,
    html[data-theme="light"] .sf-booking-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-booking-input,
    html[data-theme="light"] .sf-booking-select {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-input::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-booking-table tbody tr {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-booking-table tbody tr:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-link {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-booking-accent-title {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-booking-accent-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-accent-muted {
        color: #64748b !important;
    }
</style>

<style>
    .sf-bookings-archive-page {
        color: #e2e8f0;
    }

    .sf-bookings-archive-page .sf-booking-panel,
    .sf-bookings-archive-page .sf-booking-soft-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-bookings-archive-page .sf-booking-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-bookings-archive-page .sf-booking-title,
    .sf-bookings-archive-page .sf-booking-table td,
    .sf-bookings-archive-page .sf-booking-value {
        color: #f8fafc;
    }

    .sf-bookings-archive-page .sf-booking-muted,
    .sf-bookings-archive-page .sf-booking-table th,
    .sf-bookings-archive-page .sf-booking-faint {
        color: #94a3b8;
    }

    .sf-bookings-archive-page .sf-booking-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-bookings-archive-page .sf-booking-link:hover {
        color: #fed7aa;
    }

    .sf-bookings-archive-page .sf-booking-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07);
    }

    .sf-bookings-archive-page .sf-booking-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9);
    }

    .sf-bookings-archive-page .sf-btn-primary,
    .sf-bookings-archive-page .sf-btn-secondary,
    .sf-bookings-archive-page .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-bookings-archive-page .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
    }

    .sf-bookings-archive-page .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-bookings-archive-page .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-bookings-archive-page .sf-badge-blue,
    .sf-bookings-archive-page .sf-badge-orange,
    .sf-bookings-archive-page .sf-badge-yellow,
    .sf-bookings-archive-page .sf-badge-green,
    .sf-bookings-archive-page .sf-badge-red,
    .sf-bookings-archive-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .sf-bookings-archive-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-bookings-archive-page .sf-badge-orange { background: #ffedd5; color: #7c2d12; }
    .sf-bookings-archive-page .sf-badge-yellow { background: #fef3c7; color: #713f12; }
    .sf-bookings-archive-page .sf-badge-green { background: #dcfce7; color: #14532d; }
    .sf-bookings-archive-page .sf-badge-red { background: #fee2e2; color: #7f1d1d; }
    .sf-bookings-archive-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    html[data-theme="light"] .sf-bookings-archive-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-panel,
    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-soft-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-soft-panel {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-title,
    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-table td,
    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-muted,
    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-table th,
    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-faint {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-link {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .text-orange-300 {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-table tbody tr {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-booking-table tbody tr:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .sf-btn-primary {
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .text-yellow-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-yellow-500\/10 .text-yellow-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-yellow-500\/10 .text-yellow-100\/80,
    html[data-theme="light"] .sf-bookings-archive-page .bg-yellow-500\/10 .text-yellow-100\/70 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .text-green-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-bookings-archive-page .bg-green-500\/10 .text-green-100\/70 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-bookings-archive-page .text-red-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-bookings-archive-page .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-bookings-archive-page .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-bookings-archive-page .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }
</style>

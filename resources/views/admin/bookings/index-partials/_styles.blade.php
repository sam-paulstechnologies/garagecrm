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

    .sf-bookings-page .sf-index-sticky-panel {
        position: sticky;
        top: var(--sf-nav-offset, 4.5rem);
        z-index: 35;
        margin-inline: -0.25rem;
        padding: 0.25rem;
        border-radius: 1.25rem;
        background: rgba(2, 6, 23, 0.88);
        backdrop-filter: blur(16px);
        box-shadow: 0 18px 34px rgba(2, 6, 23, 0.28);
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

    .sf-booking-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-booking-filter-pill:hover,
    .sf-booking-filter-pill:focus {
        border-color: rgba(251, 146, 60, 0.50);
        background: rgba(249, 115, 22, 0.16);
        color: #fed7aa;
    }

    .sf-bookings-page .sf-btn-primary,
    .sf-bookings-page .sf-btn-secondary,
    .sf-bookings-page .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding-left: 1rem;
        padding-right: 1rem;
        font-size: 0.875rem;
        font-weight: 800;
        transition: all 0.2s ease;
    }

    .sf-bookings-page .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
        border: 1px solid #ff7a1a;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-bookings-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-bookings-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-bookings-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-bookings-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-bookings-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-bookings-page .sf-badge-blue,
    .sf-bookings-page .sf-badge-orange,
    .sf-bookings-page .sf-badge-yellow,
    .sf-bookings-page .sf-badge-green,
    .sf-bookings-page .sf-badge-red,
    .sf-bookings-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .sf-bookings-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-bookings-page .sf-badge-orange { background: #ffedd5; color: #7c2d12; }
    .sf-bookings-page .sf-badge-yellow { background: #fef3c7; color: #713f12; }
    .sf-bookings-page .sf-badge-green { background: #dcfce7; color: #14532d; }
    .sf-bookings-page .sf-badge-red { background: #fee2e2; color: #7f1d1d; }
    .sf-bookings-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    .sf-booking-name-link {
        display: inline-flex;
        max-width: 100%;
        color: #f8fafc;
        font-weight: 900;
        text-decoration: none;
    }

    .sf-booking-name-link:hover {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-bookings-action-group {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
    }

    .sf-bookings-action-pill {
        display: inline-flex;
        min-height: 2rem;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        padding: 0.38rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 900;
        line-height: 1;
        transition: all 0.16s ease;
    }

    .sf-bookings-action-view {
        border-color: rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
    }

    .sf-bookings-action-edit {
        border-color: rgba(148, 163, 184, 0.32);
        background: rgba(15, 23, 42, 0.84);
        color: #e2e8f0;
    }

    .sf-bookings-action-archive {
        border-color: rgba(248, 113, 113, 0.42);
        background: rgba(239, 68, 68, 0.10);
        color: #fecaca;
    }

    .sf-bookings-action-pill:hover,
    .sf-bookings-action-pill:focus {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
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

    .sf-booking-table tbody tr:hover {
        background: rgba(30, 41, 59, 0.30);
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-bookings-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-booking-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-index-sticky-panel {
        background: rgba(241, 245, 249, 0.94) !important;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12) !important;
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

    html[data-theme="light"] .sf-booking-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-booking-filter-pill:hover,
    html[data-theme="light"] .sf-booking-filter-pill:focus {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #c2410c !important;
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
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #111827 !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-link {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-bookings-page .sf-link:hover {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-booking-name-link {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-name-link:hover {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-bookings-action-view {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-bookings-action-edit {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-bookings-action-archive {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-orange-300 {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-green-300,
    html[data-theme="light"] .sf-bookings-page .text-emerald-300 {
        color: #14532d !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-yellow-300 {
        color: #713f12 !important;
    }

    html[data-theme="light"] .sf-bookings-page .text-red-300 {
        color: #7f1d1d !important;
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

    html[data-theme="light"] .sf-bookings-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-indigo-500\/10 {
        background: #eef2ff !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-green-500\/10,
    html[data-theme="light"] .sf-bookings-page .bg-emerald-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-yellow-500\/10 {
        background: #fefce8 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-red-500\/10 {
        background: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-orange-500\/10 .text-orange-300,
    html[data-theme="light"] .sf-bookings-page .bg-orange-500\/10 .text-orange-100\/80,
    html[data-theme="light"] .sf-bookings-page .bg-orange-500\/10 .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-yellow-500\/10 .text-yellow-300,
    html[data-theme="light"] .sf-bookings-page .bg-yellow-500\/10 .text-yellow-100\/80,
    html[data-theme="light"] .sf-bookings-page .bg-yellow-500\/10 .text-yellow-100\/70 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-bookings-page .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-bookings-page .bg-green-500\/10 .text-green-100\/70,
    html[data-theme="light"] .sf-bookings-page .bg-emerald-500\/10 .text-emerald-300 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-bookings-page .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-bookings-page .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-bookings-page .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-bookings-page .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }

    @media (max-width: 1023px) {
        .sf-bookings-page .sf-index-sticky-panel {
            position: static;
            margin-inline: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }
    }

    @media (max-width: 767px) {
        .sf-booking-table {
            min-width: 58rem;
        }

        .sf-bookings-action-group {
            justify-content: flex-start;
        }
    }
</style>

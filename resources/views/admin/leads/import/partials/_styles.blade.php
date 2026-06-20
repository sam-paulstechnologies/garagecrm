{{-- resources/views/admin/leads/import/partials/_styles.blade.php --}}

<style>
    .sf-import-page {
        color: #e2e8f0;
    }

    .sf-import-page .sf-card,
    .sf-import-panel,
    .sf-import-info-card {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
        box-shadow: 0 14px 36px rgba(0, 0, 0, 0.20);
    }

    .sf-import-page .sf-page-title,
    .sf-import-page .sf-section-title,
    .sf-import-title,
    .sf-import-table td,
    .sf-import-table .sf-import-column {
        color: #f8fafc;
    }

    .sf-import-page .sf-page-subtitle,
    .sf-import-page .sf-section-subtitle,
    .sf-import-page .sf-label,
    .sf-import-page .sf-help,
    .sf-import-muted,
    .sf-import-table th {
        color: #94a3b8;
    }

    .sf-import-field,
    .sf-import-select {
        width: 100%;
        border-radius: 0.875rem;
        border: 1px solid #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-import-select {
        padding: 0.65rem 0.85rem;
        font-size: 0.875rem;
        font-weight: 800;
    }

    .sf-import-field {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .sf-import-field:focus,
    .sf-import-select:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-import-info-card {
        border-radius: 1.5rem;
        border-width: 1px;
        padding: 1.25rem;
    }

    .sf-import-page .sf-table-scroll {
        border-top: 1px solid rgba(30, 41, 59, 0.9);
    }

    .sf-import-table,
    .sf-import-table thead,
    .sf-import-table tbody,
    .sf-import-table tfoot {
        background: transparent !important;
    }

    .sf-import-table thead tr,
    .sf-import-table th {
        border-color: rgba(30, 41, 59, 0.95) !important;
        background: rgba(8, 17, 31, 0.92) !important;
        color: #94a3b8 !important;
    }

    .sf-import-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9) !important;
        background: rgba(11, 18, 32, 0.62) !important;
    }

    .sf-import-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07) !important;
    }

    .sf-import-table td {
        border-color: rgba(30, 41, 59, 0.9) !important;
        background: transparent !important;
        color: #f8fafc !important;
    }

    .sf-import-page .sf-btn-primary,
    .sf-import-page .sf-btn-secondary,
    .sf-import-page .sf-btn-soft-blue {
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

    .sf-import-page .sf-btn-primary {
        background: #ff7a1a;
        border: 1px solid #ff7a1a;
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-import-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-import-page .sf-btn-secondary,
    .sf-import-page .sf-btn-soft-blue {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-import-page .sf-btn-secondary:hover,
    .sf-import-page .sf-btn-soft-blue:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-import-page .sf-alert-success,
    .sf-import-page .sf-alert-warning,
    .sf-import-page .sf-alert-danger,
    .sf-import-page .sf-alert-info {
        border-radius: 1rem;
        border-width: 1px;
        padding: 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1.6;
    }

    .sf-import-page .sf-alert-success {
        border-color: #86efac;
        background: #dcfce7;
        color: #166534;
    }

    .sf-import-page .sf-alert-warning {
        border-color: #fdba74;
        background: #ffedd5;
        color: #9a3412;
    }

    .sf-import-page .sf-alert-danger {
        border-color: #fca5a5;
        background: #fee2e2;
        color: #991b1b;
    }

    .sf-import-page .sf-alert-info {
        border-color: #93c5fd;
        background: #dbeafe;
        color: #1e3a8a;
    }

    .sf-import-page .sf-badge-blue,
    .sf-import-page .sf-badge-orange,
    .sf-import-page .sf-badge-yellow,
    .sf-import-page .sf-badge-green,
    .sf-import-page .sf-badge-red,
    .sf-import-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1;
    }

    .sf-import-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-import-page .sf-badge-orange { background: #ffedd5; color: #9a3412; }
    .sf-import-page .sf-badge-yellow { background: #fef3c7; color: #92400e; }
    .sf-import-page .sf-badge-green { background: #dcfce7; color: #166534; }
    .sf-import-page .sf-badge-red { background: #fee2e2; color: #991b1b; }
    .sf-import-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-import-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-import-page .sf-card,
    html[data-theme="light"] .sf-import-panel,
    html[data-theme="light"] .sf-import-info-card {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-import-page .sf-card-header {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-import-page .sf-page-title,
    html[data-theme="light"] .sf-import-page .sf-section-title,
    html[data-theme="light"] .sf-import-title,
    html[data-theme="light"] .sf-import-table td,
    html[data-theme="light"] .sf-import-table .sf-import-column {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-page .sf-page-subtitle,
    html[data-theme="light"] .sf-import-page .sf-section-subtitle,
    html[data-theme="light"] .sf-import-page .sf-label,
    html[data-theme="light"] .sf-import-page .sf-help,
    html[data-theme="light"] .sf-import-muted,
    html[data-theme="light"] .sf-import-table th {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-import-field,
    html[data-theme="light"] .sf-import-select {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-page .sf-table-scroll {
        border-top-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-import-table,
    html[data-theme="light"] .sf-import-table thead,
    html[data-theme="light"] .sf-import-table tbody,
    html[data-theme="light"] .sf-import-table tfoot {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-import-table thead tr,
    html[data-theme="light"] .sf-import-table th {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-import-table tbody tr {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-import-table tbody tr:hover {
        background: #f8fbff !important;
    }

    html[data-theme="light"] .sf-import-table td {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-page .text-white,
    html[data-theme="light"] .sf-import-page .text-slate-100,
    html[data-theme="light"] .sf-import-page .text-slate-200,
    html[data-theme="light"] .sf-import-page .text-slate-300 {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-page .text-slate-400,
    html[data-theme="light"] .sf-import-page .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-import-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-import-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-import-page .text-green-300,
    html[data-theme="light"] .sf-import-page .text-emerald-300 {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-import-page .text-blue-100\/80 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-import-page .text-orange-100\/80 {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-import-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-import-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-import-page .bg-green-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-import-page .bg-white\/5,
    html[data-theme="light"] .sf-import-page .bg-slate-950,
    html[data-theme="light"] .sf-import-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-import-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-import-page .bg-slate-900,
    html[data-theme="light"] .sf-import-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-import-page .bg-slate-900\/70,
    html[data-theme="light"] .sf-import-page .bg-slate-800,
    html[data-theme="light"] .sf-import-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }

    html[data-theme="light"] .sf-import-page .border-white\/10,
    html[data-theme="light"] .sf-import-page .border-slate-800 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-import-page .sf-btn-secondary,
    html[data-theme="light"] .sf-import-page .sf-btn-soft-blue {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-import-page .sf-btn-secondary:hover,
    html[data-theme="light"] .sf-import-page .sf-btn-soft-blue:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-import-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-import-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }
</style>

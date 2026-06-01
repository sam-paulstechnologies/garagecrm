<style>
    .sf-invoices-page { color: #e2e8f0; }
    .sf-invoices-page .sf-card,
    .sf-invoices-page .sf-stat-card,
    .sf-invoices-page .sf-page-header,
    .sf-invoices-page .sf-hero-panel,
    .sf-invoices-page .sf-table-wrap,
    .sf-invoices-page .sf-empty,
    .sf-invoices-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }
    .sf-invoices-page .sf-input,
    .sf-invoices-page .sf-select,
    .sf-invoices-page .sf-textarea,
    .sf-invoices-page .sf-file-input {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }
    .sf-invoices-page .sf-input::placeholder,
    .sf-invoices-page .sf-textarea::placeholder { color: #64748b; }
    .sf-invoices-page .sf-input:focus,
    .sf-invoices-page .sf-select:focus,
    .sf-invoices-page .sf-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }
    .sf-invoices-page .sf-table { background: transparent; border-collapse: separate; border-spacing: 0; }
    .sf-invoices-page .sf-table thead,
    .sf-invoices-page .sf-table thead tr,
    .sf-invoices-page .sf-table th { background: rgba(8, 17, 31, 0.92); color: #94a3b8; }
    .sf-invoices-page .sf-table tbody tr { background: rgba(11, 18, 32, 0.62); }
    .sf-invoices-page .sf-table tbody tr:hover { background: rgba(255, 122, 26, 0.07); }
    .sf-invoices-page .sf-table td { background: transparent; color: #f8fafc; }
    .sf-invoices-page .sf-link { color: #fdba74; font-weight: 800; }
    .sf-invoices-page .sf-link:hover { color: #ff7a1a; }
    .sf-invoices-page .sf-btn-primary { background: #ff7a1a; color: #fff; }
    .sf-invoices-page .sf-btn-primary:hover { background: #ea6508; }
    .sf-invoices-page .sf-btn-secondary { border-color: #334155; background: #0f172a; color: #e2e8f0; }

    html[data-theme="light"] .sf-invoices-page { color: #0f172a; }
    html[data-theme="light"] .sf-invoices-page .sf-card,
    html[data-theme="light"] .sf-invoices-page .sf-stat-card,
    html[data-theme="light"] .sf-invoices-page .sf-page-header,
    html[data-theme="light"] .sf-invoices-page .sf-hero-panel,
    html[data-theme="light"] .sf-invoices-page .sf-table-wrap,
    html[data-theme="light"] .sf-invoices-page .sf-empty,
    html[data-theme="light"] .sf-invoices-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-input,
    html[data-theme="light"] .sf-invoices-page .sf-select,
    html[data-theme="light"] .sf-invoices-page .sf-textarea,
    html[data-theme="light"] .sf-invoices-page .sf-file-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-input::placeholder,
    html[data-theme="light"] .sf-invoices-page .sf-textarea::placeholder { color: #94a3b8 !important; }
    html[data-theme="light"] .sf-invoices-page .sf-table,
    html[data-theme="light"] .sf-invoices-page .sf-table thead,
    html[data-theme="light"] .sf-invoices-page .sf-table tbody,
    html[data-theme="light"] .sf-invoices-page .sf-table tfoot { background: #ffffff !important; }
    html[data-theme="light"] .sf-invoices-page .sf-table thead tr,
    html[data-theme="light"] .sf-invoices-page .sf-table th {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-table tbody tr {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-table tbody tr:hover { background: #f8fbff !important; }
    html[data-theme="light"] .sf-invoices-page .sf-table td {
        border-color: #dbe3ef !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-stat-label,
    html[data-theme="light"] .sf-invoices-page .sf-label,
    html[data-theme="light"] .sf-invoices-page .sf-section-subtitle,
    html[data-theme="light"] .sf-invoices-page .sf-page-subtitle,
    html[data-theme="light"] .sf-invoices-page .sf-help,
    html[data-theme="light"] .sf-invoices-page .sf-stat-note {
        color: #64748b !important;
    }
    html[data-theme="light"] .sf-invoices-page .sf-stat-value,
    html[data-theme="light"] .sf-invoices-page .sf-section-title,
    html[data-theme="light"] .sf-invoices-page .sf-page-title {
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-invoices-page .text-white,
    html[data-theme="light"] .sf-invoices-page .text-slate-100,
    html[data-theme="light"] .sf-invoices-page .text-slate-200 { color: #0f172a !important; }
    html[data-theme="light"] .sf-invoices-page .text-slate-300,
    html[data-theme="light"] .sf-invoices-page .text-slate-400,
    html[data-theme="light"] .sf-invoices-page .text-slate-500 { color: #64748b !important; }
    html[data-theme="light"] .sf-invoices-page .text-orange-300,
    html[data-theme="light"] .sf-invoices-page .sf-link { color: #c2410c !important; }
    html[data-theme="light"] .sf-invoices-page .text-blue-300 { color: #1d4ed8 !important; }
    html[data-theme="light"] .sf-invoices-page .text-green-300 { color: #15803d !important; }
    html[data-theme="light"] .sf-invoices-page .text-red-300 { color: #b91c1c !important; }
    html[data-theme="light"] .sf-invoices-page .text-yellow-300 { color: #a16207 !important; }
    html[data-theme="light"] .sf-invoices-page .bg-slate-950,
    html[data-theme="light"] .sf-invoices-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-invoices-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-invoices-page .bg-slate-900,
    html[data-theme="light"] .sf-invoices-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-invoices-page .bg-slate-800,
    html[data-theme="light"] .sf-invoices-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }
    html[data-theme="light"] .sf-invoices-page .bg-orange-500\/10 { background-color: #fff7ed !important; }
    html[data-theme="light"] .sf-invoices-page .bg-blue-500\/10 { background-color: #eff6ff !important; }
    html[data-theme="light"] .sf-invoices-page .bg-green-500\/10 { background-color: #ecfdf5 !important; }
    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 { background-color: #fef2f2 !important; }
    html[data-theme="light"] .sf-invoices-page .bg-yellow-500\/10 { background-color: #fefce8 !important; }
    html[data-theme="light"] .sf-invoices-page .text-orange-100\/80,
    html[data-theme="light"] .sf-invoices-page .text-orange-100\/70 { color: #9a3412 !important; }
    html[data-theme="light"] .sf-invoices-page .text-blue-100\/80 { color: #1e40af !important; }
    html[data-theme="light"] .sf-invoices-page .text-green-100\/80 { color: #166534 !important; }
    html[data-theme="light"] .sf-invoices-page .text-yellow-100\/80 { color: #854d0e !important; }
    html[data-theme="light"] .sf-invoices-page .border-white\/10,
    html[data-theme="light"] .sf-invoices-page .border-slate-800 { border-color: #dbe3ef !important; }
    html[data-theme="light"] .sf-invoices-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
</style>

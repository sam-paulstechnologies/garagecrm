<style>
    .sf-jobs-page { color: #e2e8f0; }
    .sf-jobs-page .sf-card,
    .sf-jobs-page .sf-stat-card,
    .sf-jobs-page .sf-page-header,
    .sf-jobs-page .sf-hero-panel,
    .sf-jobs-page .sf-table-wrap,
    .sf-jobs-page .sf-empty,
    .sf-jobs-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }
    .sf-jobs-page .sf-input,
    .sf-jobs-page .sf-select,
    .sf-jobs-page .sf-textarea {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }
    .sf-jobs-page .sf-input::placeholder,
    .sf-jobs-page .sf-textarea::placeholder { color: #64748b; }
    .sf-jobs-page .sf-input:focus,
    .sf-jobs-page .sf-select:focus,
    .sf-jobs-page .sf-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }
    .sf-jobs-page .sf-table { background: transparent; border-collapse: separate; border-spacing: 0; }
    .sf-jobs-page .sf-table thead,
    .sf-jobs-page .sf-table thead tr,
    .sf-jobs-page .sf-table th { background: rgba(8, 17, 31, 0.92); color: #94a3b8; }
    .sf-jobs-page .sf-table tbody tr { background: rgba(11, 18, 32, 0.62); }
    .sf-jobs-page .sf-table tbody tr:hover { background: rgba(255, 122, 26, 0.07); }
    .sf-jobs-page .sf-table td { background: transparent; color: #f8fafc; }
    .sf-jobs-page .sf-link { color: #fdba74; font-weight: 800; }
    .sf-jobs-page .sf-link:hover { color: #ff7a1a; }
    .sf-jobs-page .sf-btn-primary { background: #ff7a1a; color: #fff; }
    .sf-jobs-page .sf-btn-primary:hover { background: #ea6508; }
    .sf-jobs-page .sf-btn-secondary { border-color: #334155; background: #0f172a; color: #e2e8f0; }
    .sf-jobs-page .sf-file-input {
        border-color: rgba(255, 255, 255, 0.1);
        background: #08111f;
        color: #cbd5e1;
    }

    html[data-theme="light"] .sf-jobs-page { color: #0f172a; }
    html[data-theme="light"] .sf-jobs-page .sf-card,
    html[data-theme="light"] .sf-jobs-page .sf-stat-card,
    html[data-theme="light"] .sf-jobs-page .sf-page-header,
    html[data-theme="light"] .sf-jobs-page .sf-hero-panel,
    html[data-theme="light"] .sf-jobs-page .sf-table-wrap,
    html[data-theme="light"] .sf-jobs-page .sf-empty,
    html[data-theme="light"] .sf-jobs-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-input,
    html[data-theme="light"] .sf-jobs-page .sf-select,
    html[data-theme="light"] .sf-jobs-page .sf-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-input::placeholder,
    html[data-theme="light"] .sf-jobs-page .sf-textarea::placeholder { color: #94a3b8 !important; }
    html[data-theme="light"] .sf-jobs-page .sf-table,
    html[data-theme="light"] .sf-jobs-page .sf-table thead,
    html[data-theme="light"] .sf-jobs-page .sf-table tbody,
    html[data-theme="light"] .sf-jobs-page .sf-table tfoot { background: #ffffff !important; }
    html[data-theme="light"] .sf-jobs-page .sf-table thead tr,
    html[data-theme="light"] .sf-jobs-page .sf-table th {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-table tbody tr {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-table tbody tr:hover { background: #f8fbff !important; }
    html[data-theme="light"] .sf-jobs-page .sf-table td {
        border-color: #dbe3ef !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-stat-label,
    html[data-theme="light"] .sf-jobs-page .sf-label,
    html[data-theme="light"] .sf-jobs-page .sf-section-subtitle,
    html[data-theme="light"] .sf-jobs-page .sf-page-subtitle,
    html[data-theme="light"] .sf-jobs-page .sf-help,
    html[data-theme="light"] .sf-jobs-page .sf-stat-note {
        color: #64748b !important;
    }
    html[data-theme="light"] .sf-jobs-page .sf-stat-value,
    html[data-theme="light"] .sf-jobs-page .sf-section-title,
    html[data-theme="light"] .sf-jobs-page .sf-page-title {
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-jobs-page .text-white,
    html[data-theme="light"] .sf-jobs-page .text-slate-100,
    html[data-theme="light"] .sf-jobs-page .text-slate-200 { color: #0f172a !important; }
    html[data-theme="light"] .sf-jobs-page .text-slate-300,
    html[data-theme="light"] .sf-jobs-page .text-slate-400,
    html[data-theme="light"] .sf-jobs-page .text-slate-500 { color: #64748b !important; }
    html[data-theme="light"] .sf-jobs-page .text-orange-300,
    html[data-theme="light"] .sf-jobs-page .sf-link { color: #c2410c !important; }
    html[data-theme="light"] .sf-jobs-page .text-blue-300 { color: #1d4ed8 !important; }
    html[data-theme="light"] .sf-jobs-page .text-green-300 { color: #15803d !important; }
    html[data-theme="light"] .sf-jobs-page .text-red-300 { color: #b91c1c !important; }
    html[data-theme="light"] .sf-jobs-page .text-yellow-300 { color: #a16207 !important; }
    html[data-theme="light"] .sf-jobs-page .bg-slate-950,
    html[data-theme="light"] .sf-jobs-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-jobs-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900\/70,
    html[data-theme="light"] .sf-jobs-page .bg-slate-800,
    html[data-theme="light"] .sf-jobs-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }
    html[data-theme="light"] .sf-jobs-page .bg-orange-500\/10 { background-color: #fff7ed !important; }
    html[data-theme="light"] .sf-jobs-page .bg-blue-500\/10 { background-color: #eff6ff !important; }
    html[data-theme="light"] .sf-jobs-page .bg-green-500\/10 { background-color: #ecfdf5 !important; }
    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 { background-color: #fef2f2 !important; }
    html[data-theme="light"] .sf-jobs-page .bg-yellow-500\/10 { background-color: #fefce8 !important; }
    html[data-theme="light"] .sf-jobs-page .text-orange-100\/70 { color: #9a3412 !important; }
    html[data-theme="light"] .sf-jobs-page .text-blue-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-blue-100\/80,
    html[data-theme="light"] .sf-jobs-page .text-blue-200 { color: #1e40af !important; }
    html[data-theme="light"] .sf-jobs-page .text-green-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-green-100\/80 { color: #166534 !important; }
    html[data-theme="light"] .sf-jobs-page .text-red-100\/70 { color: #991b1b !important; }
    html[data-theme="light"] .sf-jobs-page .text-orange-100\/80 { color: #9a3412 !important; }
    html[data-theme="light"] .sf-jobs-page .border-white\/10,
    html[data-theme="light"] .sf-jobs-page .border-slate-800 { border-color: #dbe3ef !important; }
    html[data-theme="light"] .sf-jobs-page .sf-btn-secondary { border-color: #cbd5e1 !important; background: #ffffff !important; color: #0f172a !important; }
    html[data-theme="light"] .sf-jobs-page .sf-file-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
</style>

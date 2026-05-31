<style>
    .sf-opportunities-page { color: #e2e8f0; }
    .sf-opportunity-panel { border-color: #1e293b; background: rgba(11, 18, 32, 0.88); color: #e2e8f0; }
    .sf-opportunity-soft-panel { border-color: rgba(30, 41, 59, 0.95); background: rgba(8, 17, 31, 0.74); }
    .sf-opportunity-title, .sf-opportunity-value, .sf-opportunity-table td { color: #f8fafc; }
    .sf-opportunity-muted, .sf-opportunity-table th { color: #94a3b8; }
    .sf-opportunity-input, .sf-opportunity-select { border-color: #334155; background: #08111f; color: #f8fafc; }
    .sf-opportunity-input::placeholder { color: #64748b; }
    .sf-opportunity-input:focus, .sf-opportunity-select:focus { border-color: #ff7a1a; outline: none; box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18); }
    .sf-opportunity-table { background: transparent; border-collapse: separate; border-spacing: 0; }
    .sf-opportunity-table thead, .sf-opportunity-table thead tr, .sf-opportunity-table th { background: rgba(8, 17, 31, 0.92); }
    .sf-opportunity-table tbody tr { border-color: rgba(30, 41, 59, 0.9); background: rgba(11, 18, 32, 0.62); }
    .sf-opportunity-table tbody tr:hover { background: rgba(255, 122, 26, 0.07); }
    .sf-opportunity-table td { background: transparent; }
    .sf-opportunities-page .sf-btn-primary, .sf-opportunities-page .sf-btn-secondary, .sf-opportunities-page .sf-btn-danger { min-height: 2.5rem; white-space: nowrap; }
    .sf-opportunities-page .sf-btn-primary { background: #ff7a1a; color: #fff; }
    .sf-opportunities-page .sf-btn-primary:hover { background: #ea6508; }
    .sf-opportunities-page .sf-btn-secondary { border-color: #334155; background: #0f172a; color: #e2e8f0; }
    .sf-opportunities-page .sf-link { color: #fdba74; font-weight: 800; }
    .sf-opportunities-page .sf-link:hover { color: #ff7a1a; }
    .sf-opportunity-bucket-active { border-color: rgba(251, 146, 60, 0.45); background: rgba(249, 115, 22, 0.12); box-shadow: 0 0 0 1px rgba(251, 146, 60, 0.2); }
    .sf-opportunity-bucket-idle { border-color: rgba(30, 41, 59, 0.95); background: rgba(8, 17, 31, 0.74); }
    .sf-opportunity-bucket-idle:hover { border-color: rgba(251, 146, 60, 0.35); background: rgba(15, 23, 42, 0.92); }
    html[data-theme="light"] .sf-opportunities-page { color: #0f172a; }
    html[data-theme="light"] .sf-opportunity-panel { border-color: #dbe3ef !important; background: #fff !important; color: #0f172a !important; box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important; }
    html[data-theme="light"] .sf-opportunity-soft-panel { border-color: #dbe3ef !important; background: #f8fafc !important; }
    html[data-theme="light"] .sf-opportunity-title, html[data-theme="light"] .sf-opportunity-value, html[data-theme="light"] .sf-opportunity-table td { color: #0f172a !important; }
    html[data-theme="light"] .sf-opportunity-muted, html[data-theme="light"] .sf-opportunity-table th { color: #64748b !important; }
    html[data-theme="light"] .sf-opportunity-input, html[data-theme="light"] .sf-opportunity-select { border-color: #cbd5e1 !important; background: #fff !important; color: #0f172a !important; }
    html[data-theme="light"] .sf-opportunity-input::placeholder { color: #94a3b8 !important; }
    html[data-theme="light"] .sf-opportunity-table,
    html[data-theme="light"] .sf-opportunity-table thead,
    html[data-theme="light"] .sf-opportunity-table tbody,
    html[data-theme="light"] .sf-opportunity-table tfoot {
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-opportunity-table thead tr,
    html[data-theme="light"] .sf-opportunity-table th {
        background: #f8fafc !important;
        border-color: #dbe3ef !important;
        color: #475569 !important;
    }
    html[data-theme="light"] .sf-opportunity-table tbody tr {
        border-color: #e2e8f0 !important;
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-opportunity-table tbody tr:nth-child(even) {
        background: #fbfdff !important;
    }
    html[data-theme="light"] .sf-opportunity-table tbody tr:hover {
        background: #f3f6fb !important;
    }
    html[data-theme="light"] .sf-opportunity-table td {
        border-color: #e2e8f0 !important;
        background: transparent !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-opportunity-table .sf-opportunity-muted {
        color: #64748b !important;
    }
    html[data-theme="light"] .sf-opportunities-page .sf-btn-secondary { border-color: #cbd5e1 !important; background: #fff !important; color: #0f172a !important; }
    html[data-theme="light"] .sf-opportunities-page .sf-link, html[data-theme="light"] .sf-opportunities-page .text-orange-300 { color: #c2410c !important; }
    html[data-theme="light"] .sf-opportunity-table .text-orange-300 { color: #c2410c !important; }
    html[data-theme="light"] .sf-opportunities-page .text-blue-300 { color: #1d4ed8 !important; }
    html[data-theme="light"] .sf-opportunities-page .text-red-300 { color: #b91c1c !important; }
    html[data-theme="light"] .sf-opportunities-page .text-green-300 { color: #15803d !important; }
    html[data-theme="light"] .sf-opportunity-bucket-active { border-color: rgba(234, 88, 12, 0.35) !important; background: #fff7ed !important; }
    html[data-theme="light"] .sf-opportunity-bucket-idle { border-color: #dbe3ef !important; background: #fff !important; }
</style>

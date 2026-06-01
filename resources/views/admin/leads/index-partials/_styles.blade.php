{{-- resources/views/admin/leads/index-partials/_styles.blade.php --}}

<style>
    .sf-leads-page {
        color: #e2e8f0;
    }

    .sf-leads-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-leads-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.72);
    }

    .sf-leads-title,
    .sf-leads-value,
    .sf-leads-table td {
        color: #f8fafc;
    }

    .sf-leads-muted,
    .sf-leads-table th {
        color: #94a3b8;
    }

    .sf-leads-input,
    .sf-leads-select {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-leads-input::placeholder {
        color: #64748b;
    }

    .sf-leads-input:focus,
    .sf-leads-select:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-leads-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9);
    }

    .sf-leads-page .sf-btn-primary,
    .sf-leads-page .sf-btn-secondary,
    .sf-leads-page .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-leads-page .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
    }

    .sf-leads-page .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-leads-page .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-leads-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-leads-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-leads-accent-title,
    .sf-leads-accent-value {
        color: #f8fafc;
    }

    .sf-leads-accent-muted {
        color: rgba(226, 232, 240, 0.78);
    }

    html[data-theme="light"] .sf-leads-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-leads-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-leads-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-title,
    html[data-theme="light"] .sf-leads-value,
    html[data-theme="light"] .sf-leads-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-muted,
    html[data-theme="light"] .sf-leads-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-leads-input,
    html[data-theme="light"] .sf-leads-select {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-input::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-leads-table tbody tr {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-page .border-slate-800 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-table tbody tr:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-link {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-leads-page [class*="text-slate-300"] {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-leads-accent-title {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-leads-accent-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-accent-muted {
        color: #64748b !important;
    }
</style>

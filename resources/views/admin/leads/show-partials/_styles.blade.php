{{-- resources/views/admin/leads/show-partials/_styles.blade.php --}}

<style>
    .sf-leads-show-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-leads-show-soft {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.72);
    }

    .sf-leads-show-title,
    .sf-leads-show-value,
    .sf-leads-show-table td {
        color: #f8fafc;
    }

    .sf-leads-show-muted,
    .sf-leads-show-table th {
        color: #94a3b8;
    }

    .sf-leads-show .sf-btn-primary,
    .sf-leads-show .sf-btn-secondary,
    .sf-leads-show .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-leads-show .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
    }

    .sf-leads-show .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-leads-show .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-leads-show .sf-btn-danger {
        color: #ffffff;
    }

    html[data-theme="light"] .sf-leads-show-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-leads-show-soft {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-show-title,
    html[data-theme="light"] .sf-leads-show-value,
    html[data-theme="light"] .sf-leads-show-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-show-muted,
    html[data-theme="light"] .sf-leads-show-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-leads-show .border-slate-800 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-show .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-show .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-show-table tbody tr {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-show [class*="text-slate-300"] {
        color: #334155 !important;
    }
</style>

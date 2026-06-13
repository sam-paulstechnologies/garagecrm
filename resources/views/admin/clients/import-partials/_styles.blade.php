{{-- resources/views/admin/clients/import-partials/_styles.blade.php --}}

<style>
    .sf-client-import-page {
        color: #e2e8f0;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
    }

    .sf-client-import-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-client-import-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.72);
    }

    .sf-client-import-title,
    .sf-client-import-value,
    .sf-client-import-table td {
        color: #f8fafc;
    }

    .sf-client-import-muted,
    .sf-client-import-table th {
        color: #94a3b8;
    }

    .sf-client-import-input {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-client-import-input:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-client-import-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-client-import-page .sf-btn-primary,
    .sf-client-import-page .sf-btn-secondary {
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

    .sf-client-import-page .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
        border: 1px solid #ff7a1a;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-client-import-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-client-import-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-client-import-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-client-import-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-client-import-link:hover {
        color: #ff7a1a;
    }

    html[data-theme="light"] .sf-client-import-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-client-import-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-client-import-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-client-import-title,
    html[data-theme="light"] .sf-client-import-value,
    html[data-theme="light"] .sf-client-import-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-client-import-muted,
    html[data-theme="light"] .sf-client-import-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-client-import-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-client-import-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-client-import-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-client-import-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-client-import-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-client-import-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }
</style>

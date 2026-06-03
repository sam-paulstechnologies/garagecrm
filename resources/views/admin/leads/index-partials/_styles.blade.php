{{-- resources/views/admin/leads/index-partials/_styles.blade.php --}}

<style>
    .sf-leads-page {
        color: #e2e8f0;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
    }

    .sf-leads-page .sf-leads-panel {
        width: 100%;
        max-width: none;
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

    .sf-leads-table tbody tr:hover {
        background: rgba(30, 41, 59, 0.30);
    }

    .sf-leads-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-leads-page .sf-btn-primary,
    .sf-leads-page .sf-btn-secondary,
    .sf-leads-page .sf-btn-danger {
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

    .sf-leads-page .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
        border: 1px solid #ff7a1a;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-leads-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-leads-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-leads-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
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

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

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

    html[data-theme="light"] .sf-leads-table tbody tr:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-page .border-slate-800 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-link {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-leads-page .sf-link:hover {
        color: #9a3412 !important;
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

    /*
    |--------------------------------------------------------------------------
    | Light Mode Badge Fixes
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-leads-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-leads-page .text-green-300,
    html[data-theme="light"] .sf-leads-page .text-emerald-300 {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-leads-page .text-yellow-300 {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-leads-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-leads-page .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-leads-page .text-purple-300 {
        color: #7e22ce !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-green-500\/10,
    html[data-theme="light"] .sf-leads-page .bg-emerald-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-yellow-500\/10 {
        background: #fefce8 !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-red-500\/10 {
        background: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-purple-500\/10 {
        background: #faf5ff !important;
    }

    html[data-theme="light"] .sf-leads-page .bg-slate-500\/10 {
        background: #f1f5f9 !important;
    }
</style>

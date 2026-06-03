{{-- resources/views/admin/leads/create-partials/_styles.blade.php --}}

<style>
    .sf-lead-create-page {
        color: #e2e8f0;
    }

    .sf-lead-create-page .sf-card,
    .sf-lead-create-panel,
    .sf-lead-create-info-card {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
        box-shadow: 0 14px 36px rgba(0, 0, 0, 0.20);
    }

    .sf-lead-create-page .sf-page-title,
    .sf-lead-create-page .sf-section-title,
    .sf-lead-create-title {
        color: #f8fafc;
    }

    .sf-lead-create-page .sf-page-subtitle,
    .sf-lead-create-page .sf-section-subtitle,
    .sf-lead-create-page .sf-label,
    .sf-lead-create-page .sf-help,
    .sf-lead-create-muted {
        color: #94a3b8;
    }

    .sf-lead-create-page .sf-input,
    .sf-lead-create-page .sf-select,
    .sf-lead-create-page .sf-textarea {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-lead-create-page .sf-input::placeholder,
    .sf-lead-create-page .sf-textarea::placeholder {
        color: #64748b;
    }

    .sf-lead-create-page .sf-input:focus,
    .sf-lead-create-page .sf-select:focus,
    .sf-lead-create-page .sf-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-lead-create-page .sf-divider {
        border-color: rgba(30, 41, 59, 0.9);
    }

    .sf-lead-create-info-card {
        border-radius: 1.5rem;
        border-width: 1px;
        padding: 1.25rem;
    }

    .sf-lead-create-page .sf-btn-primary,
    .sf-lead-create-page .sf-btn-secondary {
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

    .sf-lead-create-page .sf-btn-primary {
        background: #ff7a1a;
        border: 1px solid #ff7a1a;
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-lead-create-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-lead-create-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-lead-create-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-lead-create-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-card,
    html[data-theme="light"] .sf-lead-create-panel,
    html[data-theme="light"] .sf-lead-create-info-card {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-card-header,
    html[data-theme="light"] .sf-lead-create-page .sf-card-footer {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-page-title,
    html[data-theme="light"] .sf-lead-create-page .sf-section-title,
    html[data-theme="light"] .sf-lead-create-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-page-subtitle,
    html[data-theme="light"] .sf-lead-create-page .sf-section-subtitle,
    html[data-theme="light"] .sf-lead-create-page .sf-label,
    html[data-theme="light"] .sf-lead-create-page .sf-help,
    html[data-theme="light"] .sf-lead-create-muted {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-input,
    html[data-theme="light"] .sf-lead-create-page .sf-select,
    html[data-theme="light"] .sf-lead-create-page .sf-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-input::placeholder,
    html[data-theme="light"] .sf-lead-create-page .sf-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-divider {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-white,
    html[data-theme="light"] .sf-lead-create-page .text-slate-100,
    html[data-theme="light"] .sf-lead-create-page .text-slate-200,
    html[data-theme="light"] .sf-lead-create-page .text-slate-300 {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-slate-400,
    html[data-theme="light"] .sf-lead-create-page .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-green-300 {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-green-100\/80 {
        color: #166534 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-blue-100\/80 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-lead-create-page .text-orange-100\/80 {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .bg-white\/5,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-950,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-900,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-900\/70,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-800,
    html[data-theme="light"] .sf-lead-create-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }

    html[data-theme="light"] .sf-lead-create-page .bg-green-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-lead-create-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-lead-create-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-lead-create-page .border-white\/10,
    html[data-theme="light"] .sf-lead-create-page .border-slate-800 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-lead-create-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }
</style>

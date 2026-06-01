{{-- resources/views/admin/leads/edit-partials/_styles.blade.php --}}

<style>
    .sf-leads-edit-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-leads-edit-soft {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.72);
    }

    .sf-leads-edit-title,
    .sf-leads-edit-value {
        color: #f8fafc;
    }

    .sf-leads-edit-muted {
        color: #94a3b8;
    }

    .sf-leads-edit .sf-btn-primary,
    .sf-leads-edit .sf-btn-secondary {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-leads-edit .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
    }

    .sf-leads-edit .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-leads-edit .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-leads-edit-note-title {
        color: #fdba74;
    }

    .sf-leads-edit-note-text {
        color: #fed7aa;
    }

    html[data-theme="light"] .sf-leads-edit-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-leads-edit-soft {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-edit-title,
    html[data-theme="light"] .sf-leads-edit-value,
    html[data-theme="light"] .sf-leads-edit .sf-section-title,
    html[data-theme="light"] .sf-leads-edit .sf-label {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-edit-muted,
    html[data-theme="light"] .sf-leads-edit .sf-section-subtitle,
    html[data-theme="light"] .sf-leads-edit .sf-help {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-input,
    html[data-theme="light"] .sf-leads-edit .sf-select,
    html[data-theme="light"] .sf-leads-edit .sf-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-input::placeholder,
    html[data-theme="light"] .sf-leads-edit .sf-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-divider {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-edit .border-slate-800 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-edit [class*="border-white"] {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-leads-edit [class*="bg-slate-950"] {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-edit [class*="text-white"] {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-edit [class*="text-slate-300"],
    html[data-theme="light"] .sf-leads-edit [class*="text-slate-400"] {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-leads-edit-note-title {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-leads-edit-note-text {
        color: #431407 !important;
    }
</style>

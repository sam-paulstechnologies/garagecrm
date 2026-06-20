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

    .sf-crm-edit-card {
        overflow: clip;
    }

    .sf-crm-card-header {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.64));
    }

    .sf-crm-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sf-crm-section {
        padding-top: 0.2rem;
    }

    .sf-crm-section + .sf-crm-section {
        border-top: 1px solid rgba(51, 65, 85, 0.82);
        padding-top: 1rem;
    }

    .sf-crm-section-head {
        margin-bottom: 0.7rem;
    }

    .sf-crm-section-head h3 {
        color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-crm-section-head p {
        margin-top: 0.18rem;
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .sf-crm-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1rem;
        row-gap: 0.72rem;
    }

    .sf-crm-field {
        min-width: 0;
    }

    .sf-leads-edit .sf-label {
        margin-bottom: 0.28rem;
        display: block;
        color: #cbd5e1;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-leads-edit .sf-input,
    .sf-leads-edit .sf-select,
    .sf-leads-edit .sf-textarea {
        width: 100%;
        border: 1px solid #334155;
        border-radius: 0.65rem;
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        font-weight: 650;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .sf-leads-edit .sf-input,
    .sf-leads-edit .sf-select {
        min-height: 2.38rem;
        padding: 0.44rem 0.66rem;
        font-size: 0.86rem;
    }

    .sf-leads-edit .sf-textarea {
        min-height: 4.9rem;
        padding: 0.58rem 0.66rem;
        font-size: 0.86rem;
        line-height: 1.45;
    }

    .sf-leads-edit .sf-input:focus,
    .sf-leads-edit .sf-select:focus,
    .sf-leads-edit .sf-textarea:focus {
        border-color: #fb923c;
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.16);
    }

    .sf-leads-edit .sf-error {
        margin-top: 0.25rem;
        color: #fecaca;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .sf-crm-action-bar {
        background: rgba(8, 13, 24, 0.88);
    }

    .sf-crm-link {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-crm-link:hover {
        color: #fed7aa;
    }

    .sf-crm-snapshot-row {
        background: transparent;
    }

    .sf-leads-edit .sf-crm-status-hint {
        color: #fdba74;
    }

    .sf-leads-edit .sf-btn-primary,
    .sf-leads-edit .sf-btn-secondary {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-leads-edit .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
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

    @media (max-width: 767px) {
        .sf-crm-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .sf-crm-section + .sf-crm-section {
            padding-top: 0.85rem;
        }

        .sf-leads-edit .sf-input,
        .sf-leads-edit .sf-select {
            min-height: 2.5rem;
        }
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
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-crm-card-header,
    html[data-theme="light"] .sf-crm-action-bar {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-crm-section + .sf-crm-section {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-crm-section-head h3 {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-crm-section-head p {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-crm-link {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-leads-edit .sf-crm-status-hint {
        color: #9a3412 !important;
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

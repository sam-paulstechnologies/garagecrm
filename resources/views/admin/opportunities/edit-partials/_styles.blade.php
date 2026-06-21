@include('admin.opportunities.create-partials._styles')

<style>
    .sf-opportunity-form-page {
        color: #e2e8f0;
    }

    .sf-opportunity-edit-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-opportunity-edit-title,
    .sf-opportunity-edit-value {
        color: #f8fafc;
    }

    .sf-opportunity-edit-muted {
        color: #94a3b8;
    }

    .sf-opportunity-edit-card {
        overflow: clip;
    }

    .sf-opportunity-edit-card .sf-card {
        margin: 0;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .sf-opportunity-edit-card .sf-card + .sf-card {
        border-top: 1px solid rgba(51, 65, 85, 0.82) !important;
        padding-top: 1rem;
    }

    .sf-opportunity-edit-card .sf-card-header {
        padding: 0 0 0.7rem;
        border: 0;
        background: transparent;
    }

    .sf-opportunity-edit-card .sf-card-body {
        padding: 0;
    }

    .sf-opportunity-edit-card .sf-card-body.space-y-5,
    .sf-opportunity-edit-card .sf-card-body.space-y-6 {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .sf-opportunity-edit-sections {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sf-crm-card-header {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.64));
    }

    .sf-crm-action-bar {
        background: rgba(8, 13, 24, 0.88);
    }

    .sf-opportunity-form-page .sf-section-title {
        color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-opportunity-form-page .sf-section-subtitle,
    .sf-opportunity-form-page .sf-help {
        margin-top: 0.18rem;
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .sf-opportunity-form-page .sf-label {
        margin-bottom: 0.28rem;
        display: block;
        color: #cbd5e1;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-opportunity-form-page .sf-input,
    .sf-opportunity-form-page .sf-select,
    .sf-opportunity-form-page .sf-textarea {
        width: 100%;
        border: 1px solid #334155;
        border-radius: 0.65rem;
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        font-weight: 650;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .sf-opportunity-form-page .sf-input,
    .sf-opportunity-form-page .sf-select {
        min-height: 2.38rem;
        padding: 0.44rem 0.66rem;
        font-size: 0.86rem;
    }

    .sf-opportunity-form-page .sf-textarea {
        min-height: 4.9rem;
        padding: 0.58rem 0.66rem;
        font-size: 0.86rem;
        line-height: 1.45;
    }

    .sf-opportunity-form-page .sf-input:focus,
    .sf-opportunity-form-page .sf-select:focus,
    .sf-opportunity-form-page .sf-textarea:focus {
        border-color: #fb923c;
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.16);
    }

    .sf-opportunity-form-page .sf-input::placeholder,
    .sf-opportunity-form-page .sf-textarea::placeholder {
        color: #64748b;
    }

    .sf-opportunity-form-page .sf-error {
        margin-top: 0.25rem;
        color: #fecaca;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .sf-opportunity-edit-form .grid.gap-5,
    .sf-opportunity-edit-form .grid.gap-3 {
        column-gap: 1rem;
        row-gap: 0.72rem;
    }

    .sf-opportunity-edit-form .sf-divider {
        border-top: 1px solid rgba(51, 65, 85, 0.82);
    }

    .sf-opportunity-edit-form label.rounded-2xl {
        border-radius: 0.65rem;
        border-color: rgba(51, 65, 85, 0.82);
        background: rgba(15, 23, 42, 0.58);
        padding: 0.72rem 0.78rem;
    }

    .sf-opportunity-edit-form #booking_confirmation_wrap {
        border: 0 !important;
        border-top: 1px solid rgba(51, 65, 85, 0.82) !important;
        padding-top: 1rem;
    }

    .sf-opportunity-edit-form #booking_confirmation_wrap .rounded-3xl {
        border-radius: 0.65rem;
        padding: 0.75rem;
    }

    .sf-crm-link {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-crm-link:hover {
        color: #fed7aa;
    }

    .sf-opportunity-form-page .sf-btn-primary,
    .sf-opportunity-form-page .sf-btn-secondary {
        min-height: 2.5rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding: 0.55rem 1rem;
        font-size: 0.875rem;
        font-weight: 800;
        transition: all 0.2s ease;
    }

    .sf-opportunity-form-page .sf-btn-primary {
        border: 1px solid #ff7a1a;
        background: #ff7a1a;
        color: #111827;
    }

    .sf-opportunity-form-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
    }

    .sf-opportunity-form-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-opportunity-form-page .sf-btn-secondary:hover {
        background: #1e293b;
    }

    .sf-opportunity-edit-note-title {
        color: #fdba74;
    }

    .sf-opportunity-edit-note-text {
        color: #fed7aa;
    }

    @media (max-width: 767px) {
        .sf-opportunity-form-page .sf-input,
        .sf-opportunity-form-page .sf-select {
            min-height: 2.5rem;
        }

        .sf-opportunity-edit-card .sf-card + .sf-card {
            padding-top: 0.85rem;
        }
    }

    html[data-theme="light"] .sf-opportunity-edit-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-title,
    html[data-theme="light"] .sf-opportunity-edit-value,
    html[data-theme="light"] .sf-opportunity-form-page .sf-section-title,
    html[data-theme="light"] .sf-opportunity-form-page .sf-label {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-muted,
    html[data-theme="light"] .sf-opportunity-form-page .sf-section-subtitle,
    html[data-theme="light"] .sf-opportunity-form-page .sf-help {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-crm-card-header,
    html[data-theme="light"] .sf-crm-action-bar {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-card .sf-card + .sf-card,
    html[data-theme="light"] .sf-opportunity-edit-form #booking_confirmation_wrap,
    html[data-theme="light"] .sf-opportunity-edit-form .sf-divider,
    html[data-theme="light"] .sf-opportunity-form-page .border-slate-800 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-opportunity-form-page .sf-input,
    html[data-theme="light"] .sf-opportunity-form-page .sf-select,
    html[data-theme="light"] .sf-opportunity-form-page .sf-textarea {
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunity-form-page .sf-input::placeholder,
    html[data-theme="light"] .sf-opportunity-form-page .sf-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-form label.rounded-2xl {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opportunity-form-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunity-form-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opportunity-form-page .sf-btn-primary {
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-crm-link {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-note-title {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-opportunity-edit-note-text {
        color: #431407 !important;
    }
</style>

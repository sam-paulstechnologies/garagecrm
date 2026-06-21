@include('admin.invoices.index-partials._styles')

<style>
    .sf-invoices-edit .sf-page-header {
        border: 1px solid #1e293b;
        border-radius: 1rem;
        background: rgba(11, 18, 32, 0.88);
        padding: 1.5rem;
        box-shadow: 0 14px 36px rgba(2, 6, 23, 0.18);
    }

    .sf-invoices-edit .sf-card {
        overflow: clip;
    }

    .sf-invoices-edit .sf-crm-edit-card > .sf-card-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        padding: 1.25rem;
    }

    .sf-invoices-edit .sf-crm-edit-card > .sf-card-body {
        padding: 1.25rem;
    }

    .sf-invoices-edit form.space-y-6 {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sf-invoices-edit form.space-y-6 > * + * {
        margin-top: 0 !important;
    }

    .sf-invoices-edit .sf-crm-section + .sf-crm-section {
        border-top: 1px solid rgba(51, 65, 85, 0.82);
        padding-top: 1rem;
    }

    .sf-invoices-edit .sf-crm-section-head {
        margin-bottom: 0.7rem;
    }

    .sf-invoices-edit .sf-crm-section-head h3 {
        color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-invoices-edit .sf-label {
        margin-bottom: 0.28rem;
        display: block;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-invoices-edit .sf-input,
    .sf-invoices-edit .sf-select,
    .sf-invoices-edit .sf-textarea {
        width: 100%;
        border-radius: 0.65rem;
        font-size: 0.86rem;
        font-weight: 650;
    }

    .sf-invoices-edit .sf-input,
    .sf-invoices-edit .sf-select {
        min-height: 2.38rem;
        padding: 0.44rem 0.66rem;
    }

    .sf-invoices-edit .grid.gap-5 {
        column-gap: 1rem;
        row-gap: 0.72rem;
    }

    .sf-invoices-edit .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
    }

    .sf-invoices-edit .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    @media (max-width: 767px) {
        .sf-invoices-edit .sf-page-header,
        .sf-invoices-edit .sf-crm-edit-card > .sf-card-header,
        .sf-invoices-edit .sf-crm-edit-card > .sf-card-body {
            padding: 1rem;
        }
    }

    html[data-theme="light"] .sf-invoices-edit .sf-page-header {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-invoices-edit .sf-crm-edit-card > .sf-card-header,
    html[data-theme="light"] .sf-invoices-edit .sf-crm-action-bar {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-invoices-edit .sf-crm-section + .sf-crm-section {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-invoices-edit .sf-crm-section-head h3 {
        color: #0f172a !important;
    }
</style>

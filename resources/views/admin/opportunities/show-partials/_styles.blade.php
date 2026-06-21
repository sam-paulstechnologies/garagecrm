{{-- resources/views/admin/opportunities/show-partials/_styles.blade.php --}}

<style>
    .sf-opportunity-show {
        background: #f4f7fb;
        color: #0f172a;
    }

    .sf-opportunity-show-panel {
        border-color: #dbe3ef;
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
    }

    .sf-opportunity-show-title,
    .sf-opportunity-section-title,
    .sf-opportunity-value {
        color: #0f172a;
    }

    .sf-opportunity-muted {
        color: #64748b;
    }

    .sf-opportunity-link,
    .sf-opportunity-back-link {
        color: #c2410c;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-opportunity-link:hover,
    .sf-opportunity-back-link:hover {
        color: #9a3412;
    }

    .sf-opportunity-chip {
        border-color: #dbe3ef;
        background: #f8fafc;
        color: #334155;
    }

    .sf-has-explainer {
        cursor: default;
    }

    .sf-wa-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        border: 2px solid transparent;
        background: #ffffff;
        color: #16a34a;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        flex: 0 0 auto;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .sf-wa-tag:hover,
    .sf-wa-tag:focus {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
        outline: none;
    }

    .sf-wa-tag-icon {
        width: 1.2rem;
        height: 1.2rem;
    }

    .sf-wa-verified {
        border-color: #22c55e;
        color: #16a34a;
    }

    .sf-wa-unverified {
        border-color: #f97316;
        color: #16a34a;
    }

    .sf-wa-failed {
        border-color: #dc2626;
        color: #16a34a;
        background: #fff7f7;
    }

    .sf-opportunity-badge-neutral,
    .sf-opportunity-badge-warning,
    .sf-opportunity-badge-success,
    .sf-opportunity-badge-danger {
        border-color: transparent;
    }

    .sf-opportunity-badge-neutral {
        background: #f1f5f9;
        color: #334155;
        --tw-ring-color: #cbd5e1;
    }

    .sf-opportunity-badge-warning {
        background: #fff7ed;
        color: #9a3412;
        --tw-ring-color: #fed7aa;
    }

    .sf-opportunity-badge-success {
        background: #ecfdf5;
        color: #047857;
        --tw-ring-color: #a7f3d0;
    }

    .sf-opportunity-badge-danger {
        background: #fef2f2;
        color: #b91c1c;
        --tw-ring-color: #fecaca;
    }

    .sf-btn-primary,
    .sf-btn-secondary,
    .sf-btn-danger {
        display: inline-flex;
        min-height: 2.25rem;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sf-btn-primary {
        background: #f97316;
        border-color: #f97316;
        color: #111827;
    }

    .sf-btn-primary:hover {
        background: #ea580c;
        border-color: #ea580c;
    }

    .sf-btn-secondary {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    .sf-btn-secondary:hover,
    .sf-btn-secondary:active {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }

    .sf-btn-secondary:focus,
    .sf-btn-secondary:focus-visible {
        background: #ffffff !important;
        border-color: #f97316 !important;
        color: #0f172a !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.24);
    }

    .sf-btn-danger {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    .sf-btn-danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .sf-opportunity-next-action {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #9a3412;
    }

    .sf-opportunity-field-grid {
        background: #ffffff;
    }

    .sf-opportunity-field-card {
        min-height: 5.5rem;
        border-color: #dde6f2;
        background: #f8fbff;
        color: #0f172a;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    }

    .sf-opportunity-field-card:hover {
        border-color: #c8d7ea;
        background: #ffffff;
    }

    .sf-opportunity-field-label {
        color: #64748b;
    }

    .sf-opportunity-field-value {
        color: #111827;
    }

    .sf-opportunity-not-set {
        color: #94a3b8;
        font-weight: 700;
    }

    .sf-contact-row {
        border-color: #dde6f2;
        background: #f8fbff;
        color: #0f172a;
    }

    .sf-contact-label,
    .sf-contact-muted {
        color: #64748b;
    }

    .sf-contact-value {
        color: #111827;
    }

    .sf-contact-link {
        color: #c2410c;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-contact-link:hover,
    .sf-contact-link:focus {
        color: #9a3412;
    }

    .sf-related-record-row {
        border-color: #dde6f2;
        background: #f8fbff;
        color: #0f172a;
        text-decoration: none;
        transition: border-color 0.18s ease, background 0.18s ease, transform 0.18s ease;
    }

    .sf-related-record-row:hover,
    .sf-related-record-row:focus {
        border-color: #fdba74;
        background: #fff7ed;
        transform: translateY(-1px);
        outline: none;
    }

    .sf-related-record-label {
        color: #64748b;
    }

    .sf-related-record-value {
        color: #111827;
    }

    .sf-related-record-meta {
        color: #475569;
    }

    .sf-related-record-chip {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        border-radius: 999px;
        border: 1px solid #dbe6f3;
        background: #ffffff;
        color: #334155;
        padding: 0.22rem 0.5rem;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.2;
        white-space: normal;
    }

    .sf-related-record-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #9a3412;
        padding: 0.3rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 900;
    }

    .sf-opportunity-edit-link {
        color: #9a3412;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        padding: 0.18rem 0.55rem;
    }

    .sf-opportunity-edit-link:hover {
        color: #7c2d12;
        background: #ffedd5;
    }

    .sf-opportunity-row-edit summary {
        list-style: none;
    }

    .sf-opportunity-row-edit summary::-webkit-details-marker {
        display: none;
    }

    .sf-opportunity-stage-grid {
        width: 100%;
    }

    .sf-opportunity-stage-step {
        width: 100%;
        display: flex;
        min-height: 2.6rem;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        border: 1px solid #334155;
        padding: 0.55rem 0.85rem;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1.2;
        text-transform: uppercase;
        color: #f8fafc;
        background: #1e293b;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .sf-opportunity-stage-step:hover {
        border-color: #0f172a;
        background: #0f172a;
        color: #ffffff;
    }

    .sf-opportunity-stage-step.is-current {
        border-color: #f97316;
        background: #f97316;
        color: #111827;
        box-shadow: 0 10px 22px rgba(249, 115, 22, 0.22);
    }

    .sf-opportunity-stage-step.is-complete {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    .sf-opportunity-stage-details summary {
        list-style: none;
    }

    .sf-opportunity-stage-details summary::-webkit-details-marker {
        display: none;
    }

    .sf-opportunity-stage-details[open] {
        min-width: 0;
    }

    #opportunity-activity-timeline {
        scroll-margin-top: 8rem;
    }

    .sf-opportunity-section-header {
        border-color: #e2e8f0;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .sf-opportunity-activity-item {
        border-color: #dde6f2;
        background: #f8fbff;
        color: #1e293b;
    }

    .sf-opportunity-activity-summary + div,
    .sf-opportunity-activity-summary + div + div {
        display: none;
    }

    .sf-opportunity-activity-summary + div + div + div {
        margin-top: 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
    }

    html[data-theme="dark"] .sf-opportunity-show {
        background: #020617;
        color: #e2e8f0;
    }

    html[data-theme="dark"] .sf-opportunity-show-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.92);
        color: #e2e8f0;
        box-shadow: 0 18px 44px rgba(0, 0, 0, 0.26);
    }

    html[data-theme="dark"] .sf-opportunity-show-title,
    html[data-theme="dark"] .sf-opportunity-section-title,
    html[data-theme="dark"] .sf-opportunity-value {
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-opportunity-muted {
        color: #94a3b8;
    }

    html[data-theme="dark"] .sf-opportunity-chip,
    html[data-theme="dark"] .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    html[data-theme="dark"] .sf-opportunity-stage-step {
        border-color: #334155;
        background: #0f172a;
        color: #cbd5e1;
    }

    html[data-theme="dark"] .sf-opportunity-stage-step.is-current {
        border-color: #fb923c;
        background: #fb923c;
        color: #111827;
    }

    html[data-theme="dark"] .sf-opportunity-stage-step.is-complete {
        border-color: rgba(74, 222, 128, 0.32);
        background: rgba(34, 197, 94, 0.14);
        color: #bbf7d0;
    }

    html[data-theme="dark"] .sf-opportunity-field-card {
        border-color: #1e293b;
        background: #0f172a;
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-opportunity-field-label {
        color: #94a3b8;
    }

    html[data-theme="dark"] .sf-opportunity-field-value {
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-contact-row,
    html[data-theme="dark"] .sf-opportunity-activity-item {
        border-color: #1e293b;
        background: #0f172a;
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-contact-label,
    html[data-theme="dark"] .sf-contact-muted {
        color: #94a3b8;
    }

    html[data-theme="dark"] .sf-contact-value {
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-contact-link {
        color: #fdba74;
    }

    html[data-theme="dark"] .sf-related-record-row {
        border-color: #1e293b;
        background: #0f172a;
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-related-record-row:hover,
    html[data-theme="dark"] .sf-related-record-row:focus {
        border-color: rgba(251, 146, 60, 0.38);
        background: rgba(251, 146, 60, 0.12);
    }

    html[data-theme="dark"] .sf-related-record-label {
        color: #94a3b8;
    }

    html[data-theme="dark"] .sf-related-record-value {
        color: #f8fafc;
    }

    html[data-theme="dark"] .sf-related-record-meta {
        color: #cbd5e1;
    }

    html[data-theme="dark"] .sf-related-record-chip {
        border-color: #334155;
        background: #111827;
        color: #e2e8f0;
    }

    html[data-theme="dark"] .sf-related-record-arrow {
        border-color: rgba(251, 146, 60, 0.38);
        background: rgba(251, 146, 60, 0.14);
        color: #fed7aa;
    }

    html[data-theme="dark"] .sf-opportunity-edit-link {
        border-color: rgba(251, 146, 60, 0.38);
        background: rgba(251, 146, 60, 0.14);
        color: #fed7aa;
    }

    @media (max-width: 767px) {
        .sf-opportunity-show {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .sf-opportunity-stage-step {
            min-height: 2.75rem;
        }

        .sf-opportunity-show .sf-btn-primary,
        .sf-opportunity-show .sf-btn-secondary,
        .sf-opportunity-show .sf-btn-danger {
            width: 100%;
        }
    }
</style>

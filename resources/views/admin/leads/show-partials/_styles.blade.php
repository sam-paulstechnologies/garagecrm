{{-- resources/views/admin/leads/show-partials/_styles.blade.php --}}

<style>
    :root {
        --sf-nav-height: 64px;
    }

    .sf-nav {
        position: sticky !important;
        top: 0 !important;
        z-index: 1000 !important;
        border-bottom-width: 1px !important;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16) !important;
        backdrop-filter: blur(18px);
    }

    .sf-leads-show {
        background: #f3f6fb !important;
        color: #0f172a !important;
    }

    .sf-leads-show-panel {
        border-color: #dbe3ef;
        background: #ffffff;
        color: #0f172a;
    }

    .sf-leads-show-soft {
        border-color: #e2e8f0;
        background: #f8fafc;
    }

    .sf-leads-show-title,
    .sf-leads-show-value,
    .sf-leads-show-table td {
        color: #0f172a;
    }

    .sf-leads-show-muted,
    .sf-leads-show-table th {
        color: #64748b;
    }

    .sf-leads-show .sf-btn-primary,
    .sf-leads-show .sf-btn-secondary,
    .sf-leads-show .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
        border: 1px solid transparent;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
    }

    .sf-leads-show .sf-btn-primary {
        background: #ff8a1f;
        color: #111827 !important;
        border-color: #f97316;
    }

    .sf-leads-show .sf-btn-primary:hover {
        background: #fb923c;
        color: #111827 !important;
    }

    .sf-leads-show .sf-btn-secondary {
        border-color: #cbd5e1;
        background: #ffffff;
        color: #0f172a !important;
    }

    .sf-leads-show .sf-btn-secondary:hover {
        background: #f8fafc;
        color: #0f172a !important;
    }

    .sf-leads-show .sf-btn-danger {
        background: #dc2626 !important;
        border-color: #b91c1c !important;
        color: #ffffff !important;
    }

    .sf-lead-hero-sticky {
        position: sticky;
        top: calc(var(--sf-nav-height) + 12px);
        z-index: 60;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12);
    }

    .sf-lead-hero-sticky::after {
        content: "";
        position: absolute;
        inset: -12px -2px auto;
        height: 12px;
        background: #f3f6fb;
        pointer-events: none;
    }

    .sf-lead-chip {
        border-color: #d1d9e6 !important;
        background: #f8fafc !important;
        color: #1e293b !important;
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

    .sf-lead-back-link {
        color: #c2410c !important;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-lead-back-link:hover,
    .sf-lead-back-link:focus {
        color: #9a3412 !important;
    }

    .sf-lead-badge {
        background: #eaf2ff !important;
        color: #0f3f75 !important;
        border-color: #bfdbfe !important;
    }

    .sf-lead-badge-hot {
        background: #fee2e2 !important;
        color: #991b1b !important;
        border-color: #fecaca !important;
    }

    .sf-lead-status-step {
        color: #f8fafc !important;
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    .sf-lead-status-step:hover {
        background: #0f172a !important;
        color: #ffffff !important;
    }

    .sf-lead-status-step.is-current {
        background: #ff8a1f !important;
        border-color: #f97316 !important;
        color: #111827 !important;
    }

    .sf-lead-status-step.is-complete {
        background: #dcfce7 !important;
        border-color: #86efac !important;
        color: #14532d !important;
    }

    .sf-status-context summary {
        list-style: none;
    }

    .sf-status-context summary::-webkit-details-marker {
        display: none;
    }

    #lead-activity-timeline {
        scroll-margin-top: calc(var(--sf-nav-height) + 220px);
    }

    .sf-lead-section-header {
        border-color: #e2e8f0 !important;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .sf-lead-detail-list {
        background: #ffffff;
    }

    .sf-lead-cube-grid {
        background: #ffffff;
    }

    .sf-lead-field-cube {
        min-height: 5.5rem;
        border-color: #dde6f2 !important;
        background: #f8fbff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    }

    .sf-lead-field-cube:hover {
        border-color: #c8d7ea !important;
        background: #ffffff !important;
    }

    .sf-lead-detail-row {
        min-height: 3rem;
        background: #ffffff;
        color: #0f172a;
    }

    .sf-lead-detail-row:hover {
        background: #f8fafc;
    }

    .sf-lead-not-set {
        color: #94a3b8;
        font-weight: 700;
    }

    .sf-row-edit summary {
        list-style: none;
    }

    .sf-row-edit summary::-webkit-details-marker {
        display: none;
    }

    .sf-lead-field-label {
        color: #64748b !important;
    }

    .sf-lead-field-value {
        color: #111827 !important;
    }

    .sf-lead-edit-link {
        color: #9a3412 !important;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        padding: 0.18rem 0.55rem;
    }

    .sf-lead-edit-link:hover {
        color: #7c2d12 !important;
        background: #ffedd5;
    }

    .sf-lead-score-reason,
    .sf-lead-activity-item {
        border-color: #dde6f2 !important;
        background: #f8fbff !important;
        color: #1e293b !important;
    }

    .sf-lead-next-action {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
        color: #7c2d12 !important;
    }

    .sf-contact-row {
        border-color: #dde6f2 !important;
        background: #f8fbff !important;
        color: #0f172a !important;
    }

    .sf-contact-label,
    .sf-contact-muted {
        color: #64748b !important;
    }

    .sf-contact-value {
        color: #111827 !important;
    }

    .sf-contact-link {
        color: #c2410c !important;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-contact-link:hover,
    .sf-contact-link:focus {
        color: #9a3412 !important;
    }

    .sf-leads-show .text-slate-50,
    .sf-leads-show .text-slate-100,
    .sf-leads-show .text-slate-200 {
        color: #0f172a !important;
    }

    .sf-leads-show .text-slate-300,
    .sf-leads-show .text-slate-400 {
        color: #475569 !important;
    }

    .sf-leads-show .text-slate-500 {
        color: #64748b !important;
    }

    .sf-leads-show .border-slate-800,
    .sf-leads-show .border-slate-700 {
        border-color: #e2e8f0 !important;
    }

    @media (max-width: 767px) {
        .sf-lead-hero-sticky {
            position: static;
            top: auto;
            z-index: auto;
        }

        .sf-lead-hero-sticky::after {
            display: none;
        }
    }

</style>

{{-- resources/views/admin/clients/edit-partials/_styles.blade.php --}}

<style>
    .sf-edit-panel {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-edit-title {
        color: #ffffff;
    }

    .sf-edit-muted {
        color: #cbd5e1;
    }

    .sf-edit-section-title {
        color: #ffffff;
    }

    .sf-edit-label {
        color: #cbd5e1;
    }

    .sf-edit-input,
    .sf-edit-select,
    .sf-edit-textarea {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.65);
        color: #ffffff;
    }

    .sf-edit-input::placeholder,
    .sf-edit-textarea::placeholder {
        color: #64748b;
    }

    .sf-edit-input:focus,
    .sf-edit-select:focus,
    .sf-edit-textarea:focus {
        border-color: rgba(249, 115, 22, 0.80);
        outline: none;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.18);
    }

    .sf-edit-divider {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .sf-edit-side-box {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.36);
    }

    .sf-edit-value {
        color: #ffffff;
    }

    .sf-edit-target {
        scroll-margin-top: 120px;
    }

    .sf-edit-target:target {
        border-radius: 18px;
        animation: sfEditPulse 1.4s ease-out 1;
    }

    @keyframes sfEditPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.60);
        }

        100% {
            box-shadow: 0 0 0 14px rgba(249, 115, 22, 0);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VIP / Orange Info Cards
    |--------------------------------------------------------------------------
    */
    .sf-edit-vip-card {
        border-color: rgba(251, 146, 60, 0.24);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-edit-vip-title {
        color: #fdba74;
    }

    .sf-edit-vip-text {
        color: #fed7aa;
    }

    .sf-edit-checkbox {
        appearance: auto !important;
        -webkit-appearance: checkbox !important;
        accent-color: #f97316;
        border-color: #fb923c;
        background: #ffffff;
    }

    .sf-edit-vip-badge {
        border-color: rgba(251, 146, 60, 0.28);
        background: rgba(249, 115, 22, 0.12);
        color: #fdba74;
    }

    .sf-edit-next-card {
        border-color: rgba(251, 146, 60, 0.24);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-edit-next-title {
        color: #fdba74;
    }

    .sf-edit-next-text {
        color: #fed7aa;
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */
    html[data-theme="light"] .sf-edit-panel {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-edit-title,
    html[data-theme="light"] .sf-edit-section-title,
    html[data-theme="light"] .sf-edit-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-edit-muted,
    html[data-theme="light"] .sf-edit-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-edit-input,
    html[data-theme="light"] .sf-edit-select,
    html[data-theme="light"] .sf-edit-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-edit-input::placeholder,
    html[data-theme="light"] .sf-edit-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-edit-divider {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-edit-side-box {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode - VIP / Orange Info Cards
    |--------------------------------------------------------------------------
    */
    html[data-theme="light"] .sf-edit-vip-card {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-edit-vip-title {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-edit-vip-text {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-edit-checkbox {
        appearance: auto !important;
        -webkit-appearance: checkbox !important;
        accent-color: #ea580c;
        border-color: #fb923c !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-edit-vip-badge {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-edit-next-card {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-edit-next-title {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-edit-next-text {
        color: #431407 !important;
    }
</style>
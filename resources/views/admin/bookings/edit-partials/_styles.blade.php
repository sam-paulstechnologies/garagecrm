@include('admin.bookings.create-partials._styles')

<style>
    .sf-bookings-edit .sf-page-header {
        border: 1px solid #1e293b;
        border-radius: 1rem;
        background: rgba(11, 18, 32, 0.88);
        padding: 1.5rem;
        box-shadow: 0 14px 36px rgba(2, 6, 23, 0.18);
    }

    .sf-bookings-edit .sf-card {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .sf-bookings-edit .sf-card + .sf-card {
        border-top: 1px solid rgba(51, 65, 85, 0.82);
        padding-top: 1rem;
    }

    .sf-bookings-edit .sf-card-header {
        border: 0;
        padding: 0 0 0.7rem;
    }

    .sf-bookings-edit .sf-card-body {
        padding: 0;
    }

    .sf-bookings-edit form.space-y-6 {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sf-bookings-edit form.space-y-6 > * + * {
        margin-top: 0 !important;
    }

    .sf-bookings-edit .sf-section-title {
        font-size: 0.92rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-bookings-edit .sf-section-subtitle,
    .sf-bookings-edit .sf-help {
        font-size: 0.78rem;
        font-weight: 650;
        line-height: 1.45;
    }

    .sf-bookings-edit .sf-label {
        margin-bottom: 0.28rem;
        display: block;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sf-bookings-edit .sf-input,
    .sf-bookings-edit .sf-select,
    .sf-bookings-edit .sf-textarea {
        width: 100%;
        border-radius: 0.65rem;
        font-size: 0.86rem;
        font-weight: 650;
    }

    .sf-bookings-edit .sf-input,
    .sf-bookings-edit .sf-select {
        min-height: 2.38rem;
        padding: 0.44rem 0.66rem;
    }

    .sf-bookings-edit .sf-textarea {
        min-height: 5rem;
        padding: 0.58rem 0.66rem;
    }

    .sf-bookings-edit .grid.gap-5 {
        column-gap: 1rem;
        row-gap: 0.72rem;
    }

    .sf-bookings-edit .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
    }

    .sf-bookings-edit .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-bookings-edit .sf-crm-link {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-bookings-edit .sf-booking-edit-note {
        border-color: rgba(251, 146, 60, 0.32);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-bookings-edit .sf-booking-edit-note-title {
        color: #fdba74;
    }

    .sf-bookings-edit .sf-booking-edit-note-text {
        color: #fed7aa;
    }

    @media (max-width: 767px) {
        .sf-bookings-edit .sf-page-header {
            padding: 1rem;
        }

        .sf-bookings-edit .sf-input,
        .sf-bookings-edit .sf-select {
            min-height: 2.5rem;
        }
    }

    html[data-theme="light"] .sf-bookings-edit .sf-page-header {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-card {
        background: transparent !important;
        box-shadow: none !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-card + .sf-card {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-crm-link {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-booking-edit-note {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-booking-edit-note-title {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-booking-edit-note-text {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .sf-btn-primary {
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .text-orange-300,
    html[data-theme="light"] .sf-bookings-edit .bg-orange-500\/10 .text-orange-300,
    html[data-theme="light"] .sf-bookings-edit .bg-orange-500\/10 .text-orange-100\/80,
    html[data-theme="light"] .sf-bookings-edit .bg-orange-500\/10 .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .text-green-300,
    html[data-theme="light"] .sf-bookings-edit .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-bookings-edit .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-bookings-edit .bg-green-500\/10 .text-green-100\/70 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-bookings-edit .text-red-300,
    html[data-theme="light"] .sf-bookings-edit .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-bookings-edit .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-bookings-edit .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-bookings-edit .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }
</style>

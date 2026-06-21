<style>
    .sf-booking-show-page {
        color: #e2e8f0;
    }

    .sf-booking-show-page .sf-booking-panel,
    .sf-booking-show-page .sf-booking-soft-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-booking-show-page .sf-booking-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-booking-show-page .sf-booking-panel {
        overflow: clip;
    }

    #booking-activity-timeline {
        scroll-margin-top: 7rem;
    }

    .sf-booking-show-page .sf-booking-title,
    .sf-booking-show-page .sf-booking-value,
    .sf-booking-show-page .sf-section-title {
        color: #f8fafc;
    }

    .sf-booking-show-page .sf-booking-muted {
        color: #94a3b8;
    }

    .sf-booking-show-page .sf-booking-faint {
        color: #94a3b8;
    }

    .sf-booking-show-page .sf-hero-panel {
        border: 1px solid #1e293b;
        border-radius: 1.5rem;
        background: rgba(11, 18, 32, 0.88);
        box-shadow: 0 18px 42px rgba(2, 6, 23, 0.22);
    }

    .sf-booking-show-page .sf-kicker {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: rgba(249, 115, 22, 0.14);
        padding: 0.28rem 0.62rem;
        color: #fdba74;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-booking-show-page .sf-booking-step-active {
        border-color: rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
        box-shadow: 0 0 0 1px rgba(251, 146, 60, 0.16);
    }

    .sf-booking-show-page .sf-booking-step-done {
        border-color: rgba(74, 222, 128, 0.28);
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
    }

    .sf-booking-show-page .sf-booking-step-idle {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
        color: #94a3b8;
    }

    .sf-booking-show-page .sf-booking-next-action {
        border-color: rgba(251, 146, 60, 0.28);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
    }

    .sf-booking-show-page .sf-btn-primary,
    .sf-booking-show-page .sf-btn-secondary,
    .sf-booking-show-page .sf-btn-danger {
        min-height: 2.5rem;
        white-space: nowrap;
    }

    .sf-booking-show-page .sf-btn-primary {
        background: #ff7a1a;
        color: #111827;
    }

    .sf-booking-show-page .sf-btn-primary:hover {
        background: #ea6508;
    }

    .sf-booking-show-page .sf-btn-secondary {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-booking-hero-chip,
    .sf-booking-contact-value {
        color: #fdba74;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-booking-show-page .sf-badge-blue,
    .sf-booking-show-page .sf-badge-orange,
    .sf-booking-show-page .sf-badge-yellow,
    .sf-booking-show-page .sf-badge-green,
    .sf-booking-show-page .sf-badge-red,
    .sf-booking-show-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .sf-booking-show-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-booking-show-page .sf-badge-orange { background: #ffedd5; color: #7c2d12; }
    .sf-booking-show-page .sf-badge-yellow { background: #fef3c7; color: #713f12; }
    .sf-booking-show-page .sf-badge-green { background: #dcfce7; color: #14532d; }
    .sf-booking-show-page .sf-badge-red { background: #fee2e2; color: #7f1d1d; }
    .sf-booking-show-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    .sf-booking-stage-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .sf-booking-stage-button {
        display: flex;
        width: 100%;
        min-height: 2.8rem;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(51, 65, 85, 0.95);
        background: rgba(15, 23, 42, 0.86);
        padding: 0.65rem 0.9rem;
        color: #e2e8f0;
        font-size: 0.82rem;
        font-weight: 900;
        text-align: center;
        transition: all 0.16s ease;
    }

    .sf-booking-stage-button:hover,
    .sf-booking-stage-button:focus {
        border-color: rgba(251, 146, 60, 0.55);
        color: #fed7aa;
        transform: translateY(-1px);
    }

    .sf-booking-stage-button.is-active {
        border-color: rgba(251, 146, 60, 0.58);
        background: #ff7a1a;
        color: #111827;
        box-shadow: 0 10px 22px rgba(249, 115, 22, 0.20);
    }

    .sf-booking-stage-button.is-danger,
    .sf-booking-stage-button.is-danger-active {
        cursor: pointer;
        list-style: none;
    }

    .sf-booking-stage-button.is-danger::-webkit-details-marker,
    .sf-booking-stage-button.is-danger-active::-webkit-details-marker {
        display: none;
    }

    .sf-booking-stage-button.is-danger {
        border-color: rgba(248, 113, 113, 0.35);
        background: rgba(239, 68, 68, 0.10);
        color: #fecaca;
    }

    .sf-booking-stage-button.is-danger-active {
        border-color: rgba(248, 113, 113, 0.55);
        background: #b91c1c;
        color: #fff7f7;
    }

    .sf-booking-mini-label {
        display: block;
        margin-bottom: 0.3rem;
        color: #fecaca;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-booking-mini-select {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(248, 113, 113, 0.35);
        background: rgba(15, 23, 42, 0.92);
        color: #f8fafc;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .sf-booking-stage-submit {
        margin-top: 0.65rem;
        width: 100%;
        border-radius: 999px;
        background: #ef4444;
        padding: 0.6rem 0.8rem;
        color: #fff7f7;
        font-size: 0.78rem;
        font-weight: 900;
    }

    .sf-booking-contact-name {
        color: #f8fafc;
        font-size: 1rem;
        font-weight: 900;
    }

    .sf-booking-contact-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        border-radius: 0.9rem;
        border: 1px solid rgba(51, 65, 85, 0.88);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.72rem 0.85rem;
    }

    .sf-booking-contact-label {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-booking-contact-empty {
        color: #94a3b8;
        font-weight: 800;
    }

    .sf-booking-wa-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(34, 197, 94, 0.42);
        background: rgba(22, 163, 74, 0.10);
        padding: 0.38rem 0.65rem;
        color: #bbf7d0;
        font-size: 0.75rem;
        font-weight: 900;
    }

    .sf-booking-related-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.9);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.9rem;
        text-decoration: none;
    }

    .sf-booking-related-type {
        display: block;
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-booking-related-title {
        display: block;
        margin-top: 0.18rem;
        color: #f8fafc;
        font-weight: 900;
    }

    .sf-booking-related-meta {
        display: block;
        margin-top: 0.18rem;
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .sf-booking-related-action {
        flex: 0 0 auto;
        border-radius: 999px;
        border: 1px solid rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.10);
        padding: 0.42rem 0.7rem;
        color: #fed7aa;
        font-size: 0.75rem;
        font-weight: 900;
    }

    .sf-booking-activity-card {
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.9);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.95rem;
    }

    .sf-booking-activity-title {
        color: #f8fafc;
        font-weight: 900;
    }

    .sf-booking-activity-meta,
    .sf-booking-activity-detail {
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.55;
    }

    .sf-booking-system-grid {
        display: grid;
        gap: 0.85rem;
    }

    .sf-booking-system-card {
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.9);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.9rem;
    }

    .sf-booking-system-label {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-booking-system-value {
        margin-top: 0.25rem;
        color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 900;
        line-height: 1.5;
    }

    html[data-theme="light"] .sf-booking-show-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-panel,
    html[data-theme="light"] .sf-booking-show-page .sf-booking-soft-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-soft-panel {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-title,
    html[data-theme="light"] .sf-booking-show-page .sf-booking-value,
    html[data-theme="light"] .sf-booking-show-page .sf-section-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-muted {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-faint {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-hero-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-kicker {
        background: #fff7ed !important;
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .border-white\/10 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-step-idle {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-next-action {
        border-color: rgba(234, 88, 12, 0.25);
        background: #fff7ed;
        color: #9a3412;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-booking-hero-chip,
    html[data-theme="light"] .sf-booking-contact-value {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-booking-stage-button {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-stage-button.is-active {
        border-color: #f97316 !important;
        background: #f97316 !important;
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-booking-stage-button.is-danger {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-booking-stage-button.is-danger-active {
        border-color: #b91c1c !important;
        background: #b91c1c !important;
        color: #fff7f7 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-btn-primary {
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-step-active,
    html[data-theme="light"] .sf-booking-show-page .bg-orange-500\/10 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .sf-booking-step-done,
    html[data-theme="light"] .sf-booking-show-page .bg-green-500\/10 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .text-orange-300,
    html[data-theme="light"] .sf-booking-show-page .bg-orange-500\/10 .text-orange-300,
    html[data-theme="light"] .sf-booking-show-page .bg-orange-500\/10 .text-orange-100\/80,
    html[data-theme="light"] .sf-booking-show-page .bg-orange-500\/10 .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .text-yellow-300,
    html[data-theme="light"] .sf-booking-show-page .bg-yellow-500\/10 .text-yellow-300,
    html[data-theme="light"] .sf-booking-show-page .bg-yellow-500\/10 .text-yellow-100\/80,
    html[data-theme="light"] .sf-booking-show-page .bg-yellow-500\/10 .text-yellow-100\/70 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .text-green-300,
    html[data-theme="light"] .sf-booking-show-page .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-booking-show-page .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-booking-show-page .bg-green-500\/10 .text-green-100\/70 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-booking-show-page .text-red-300,
    html[data-theme="light"] .sf-booking-show-page .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-booking-show-page .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-booking-show-page .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-booking-show-page .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }

    html[data-theme="light"] .sf-booking-mini-select {
        border-color: #fecaca !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-contact-name,
    html[data-theme="light"] .sf-booking-related-title,
    html[data-theme="light"] .sf-booking-activity-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-contact-row,
    html[data-theme="light"] .sf-booking-related-card,
    html[data-theme="light"] .sf-booking-activity-card,
    html[data-theme="light"] .sf-booking-system-card {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-booking-contact-label,
    html[data-theme="light"] .sf-booking-contact-empty,
    html[data-theme="light"] .sf-booking-related-type,
    html[data-theme="light"] .sf-booking-related-meta,
    html[data-theme="light"] .sf-booking-activity-meta,
    html[data-theme="light"] .sf-booking-activity-detail,
    html[data-theme="light"] .sf-booking-system-label {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-booking-system-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-booking-wa-chip {
        border-color: #86efac !important;
        background: #f0fdf4 !important;
        color: #166534 !important;
    }

    html[data-theme="light"] .sf-booking-related-action {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    @media (max-width: 1023px) {
        .sf-booking-stage-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 639px) {
        .sf-booking-stage-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .sf-booking-contact-row,
        .sf-booking-related-card {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

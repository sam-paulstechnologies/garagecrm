{{-- resources/views/admin/invoices/index-partials/_styles.blade.php --}}

<style>
    .sf-invoices-page {
        color: #e2e8f0;
    }

    .sf-invoices-page .sf-card,
    .sf-invoices-page .sf-stat-card,
    .sf-invoices-page .sf-page-header,
    .sf-invoices-page .sf-hero-panel,
    .sf-invoices-page .sf-table-wrap,
    .sf-invoices-page .sf-empty,
    .sf-invoices-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-invoice-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-invoice-title,
    .sf-invoice-value,
    .sf-invoices-page .sf-stat-value,
    .sf-invoices-page .sf-section-title,
    .sf-invoices-page .sf-page-title,
    .sf-invoices-page .sf-table td {
        color: #f8fafc;
    }

    .sf-invoice-muted,
    .sf-invoices-page .sf-stat-label,
    .sf-invoices-page .sf-label,
    .sf-invoices-page .sf-section-subtitle,
    .sf-invoices-page .sf-page-subtitle,
    .sf-invoices-page .sf-help,
    .sf-invoices-page .sf-stat-note,
    .sf-invoices-page .sf-table th {
        color: #94a3b8;
    }

    .sf-invoice-input,
    .sf-invoice-select,
    .sf-invoices-page .sf-input,
    .sf-invoices-page .sf-select,
    .sf-invoices-page .sf-textarea,
    .sf-invoices-page .sf-file-input {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-invoice-input::placeholder,
    .sf-invoices-page .sf-input::placeholder,
    .sf-invoices-page .sf-textarea::placeholder {
        color: #64748b;
    }

    .sf-invoice-input:focus,
    .sf-invoice-select:focus,
    .sf-invoices-page .sf-input:focus,
    .sf-invoices-page .sf-select:focus,
    .sf-invoices-page .sf-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-invoice-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-invoice-filter-pill:hover,
    .sf-invoice-filter-pill:focus {
        border-color: rgba(251, 146, 60, 0.50);
        background: rgba(249, 115, 22, 0.16);
        color: #fed7aa;
    }

    .sf-invoices-page .sf-table {
        background: transparent;
        border-collapse: separate;
        border-spacing: 0;
    }

    .sf-invoices-page .sf-table thead,
    .sf-invoices-page .sf-table thead tr,
    .sf-invoices-page .sf-table th {
        background: rgba(8, 17, 31, 0.92);
        color: #94a3b8;
    }

    .sf-invoices-page .sf-table tbody tr {
        background: rgba(11, 18, 32, 0.62);
    }

    .sf-invoices-page .sf-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07);
    }

    .sf-invoices-page .sf-table td {
        background: transparent;
        color: #f8fafc;
    }

    .sf-invoices-page .sf-index-sticky-panel {
        position: sticky;
        top: var(--sf-nav-offset, 4.5rem);
        z-index: 35;
        margin-inline: -0.25rem;
        padding: 0.25rem;
        border-radius: 1.25rem;
        background: rgba(2, 6, 23, 0.88);
        backdrop-filter: blur(16px);
        box-shadow: 0 18px 34px rgba(2, 6, 23, 0.28);
    }

    .sf-invoice-name-link {
        display: inline-flex;
        max-width: 100%;
        color: #f8fafc;
        font-weight: 900;
        text-decoration: none;
    }

    .sf-invoice-name-link:hover {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-invoices-action-group {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
    }

    .sf-invoices-action-pill {
        display: inline-flex;
        min-height: 2rem;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        padding: 0.38rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 900;
        line-height: 1;
        transition: all 0.16s ease;
    }

    .sf-invoices-action-view,
    .sf-invoices-action-download {
        border-color: rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
    }

    .sf-invoices-action-edit {
        border-color: rgba(148, 163, 184, 0.32);
        background: rgba(15, 23, 42, 0.84);
        color: #e2e8f0;
    }

    .sf-invoices-action-pill:hover,
    .sf-invoices-action-pill:focus {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
    }

    .sf-invoice-roi-row td {
        padding-top: 0;
        color: #94a3b8;
        font-size: 0.78rem;
    }

    .sf-invoices-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-invoices-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-invoices-page .sf-btn-primary,
    .sf-invoices-page .sf-btn-secondary,
    .sf-invoices-page .sf-btn-danger {
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

    .sf-invoices-page .sf-btn-primary {
        background: #ff7a1a;
        border: 1px solid #ff7a1a;
        color: #111827;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-invoices-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-invoices-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-invoices-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-invoices-page .sf-btn-danger {
        border: 1px solid rgba(248, 113, 113, 0.42);
        background: rgba(239, 68, 68, 0.10);
        color: #fecaca;
    }

    .sf-invoice-note {
        border-color: rgba(251, 146, 60, 0.24);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-invoice-note-title {
        color: #fdba74;
    }

    .sf-invoice-note-text {
        color: #fed7aa;
    }

    .sf-invoices-page .sf-badge-blue,
    .sf-invoices-page .sf-badge-orange,
    .sf-invoices-page .sf-badge-yellow,
    .sf-invoices-page .sf-badge-green,
    .sf-invoices-page .sf-badge-red,
    .sf-invoices-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .sf-invoices-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-invoices-page .sf-badge-orange { background: #ffedd5; color: #7c2d12; }
    .sf-invoices-page .sf-badge-yellow { background: #fef3c7; color: #713f12; }
    .sf-invoices-page .sf-badge-green { background: #dcfce7; color: #14532d; }
    .sf-invoices-page .sf-badge-red { background: #fee2e2; color: #7f1d1d; }
    .sf-invoices-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    .sf-back-link {
        display: inline-flex;
        width: fit-content;
        color: #fdba74;
        font-size: 0.85rem;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-invoice-hero-chip,
    .sf-invoice-contact-value {
        color: #fdba74;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-invoice-status-panel .sf-card-body {
        padding: 1rem;
    }

    .sf-invoice-status-grid,
    .sf-invoice-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sf-invoice-status-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sf-invoice-status-button {
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

    .sf-invoice-status-button:hover,
    .sf-invoice-status-button:focus {
        border-color: rgba(251, 146, 60, 0.55);
        color: #fed7aa;
        transform: translateY(-1px);
    }

    .sf-invoice-status-button.is-active {
        border-color: rgba(251, 146, 60, 0.58);
        background: #ff7a1a;
        color: #111827;
        box-shadow: 0 10px 22px rgba(249, 115, 22, 0.20);
    }

    .sf-invoice-field-card,
    .sf-invoice-contact-row,
    .sf-invoice-related-card,
    .sf-invoice-activity-card {
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.9);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.9rem;
    }

    .sf-invoice-field-label,
    .sf-invoice-contact-label,
    .sf-invoice-related-type {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-invoice-field-value {
        margin-top: 0.25rem;
        color: #f8fafc;
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.55;
    }

    .sf-invoice-contact-name {
        color: #f8fafc;
        font-size: 1rem;
        font-weight: 900;
    }

    .sf-invoice-contact-row,
    .sf-invoice-related-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .sf-invoice-contact-empty,
    .sf-invoice-related-meta,
    .sf-invoice-activity-meta,
    .sf-invoice-activity-detail {
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.55;
    }

    .sf-invoice-wa-chip {
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

    .sf-invoice-related-card {
        text-decoration: none;
    }

    .sf-invoice-related-title,
    .sf-invoice-activity-title {
        display: block;
        margin-top: 0.18rem;
        color: #f8fafc;
        font-weight: 900;
    }

    .sf-invoice-related-action {
        flex: 0 0 auto;
        border-radius: 999px;
        border: 1px solid rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.10);
        padding: 0.42rem 0.7rem;
        color: #fed7aa;
        font-size: 0.75rem;
        font-weight: 900;
    }

    #invoice-activity-timeline {
        scroll-margin-top: 7rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-invoices-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-invoices-page .sf-card,
    html[data-theme="light"] .sf-invoices-page .sf-stat-card,
    html[data-theme="light"] .sf-invoices-page .sf-page-header,
    html[data-theme="light"] .sf-invoices-page .sf-hero-panel,
    html[data-theme="light"] .sf-invoices-page .sf-table-wrap,
    html[data-theme="light"] .sf-invoices-page .sf-empty,
    html[data-theme="light"] .sf-invoices-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-invoice-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-invoice-title,
    html[data-theme="light"] .sf-invoice-value,
    html[data-theme="light"] .sf-invoices-page .sf-stat-value,
    html[data-theme="light"] .sf-invoices-page .sf-section-title,
    html[data-theme="light"] .sf-invoices-page .sf-page-title,
    html[data-theme="light"] .sf-invoices-page .sf-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoice-muted,
    html[data-theme="light"] .sf-invoices-page .sf-stat-label,
    html[data-theme="light"] .sf-invoices-page .sf-label,
    html[data-theme="light"] .sf-invoices-page .sf-section-subtitle,
    html[data-theme="light"] .sf-invoices-page .sf-page-subtitle,
    html[data-theme="light"] .sf-invoices-page .sf-help,
    html[data-theme="light"] .sf-invoices-page .sf-stat-note,
    html[data-theme="light"] .sf-invoices-page .sf-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-invoice-input,
    html[data-theme="light"] .sf-invoice-select,
    html[data-theme="light"] .sf-invoices-page .sf-input,
    html[data-theme="light"] .sf-invoices-page .sf-select,
    html[data-theme="light"] .sf-invoices-page .sf-textarea,
    html[data-theme="light"] .sf-invoices-page .sf-file-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoice-input::placeholder,
    html[data-theme="light"] .sf-invoices-page .sf-input::placeholder,
    html[data-theme="light"] .sf-invoices-page .sf-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-invoice-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-invoice-filter-pill:hover,
    html[data-theme="light"] .sf-invoice-filter-pill:focus {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-table,
    html[data-theme="light"] .sf-invoices-page .sf-table thead,
    html[data-theme="light"] .sf-invoices-page .sf-table tbody,
    html[data-theme="light"] .sf-invoices-page .sf-table tfoot {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-table thead tr,
    html[data-theme="light"] .sf-invoices-page .sf-table th {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-table tbody tr {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-table tbody tr:hover {
        background: #f8fbff !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-table td {
        border-color: #dbe3ef !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-index-sticky-panel {
        background: rgba(241, 245, 249, 0.94) !important;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12) !important;
    }

    html[data-theme="light"] .sf-invoice-name-link {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoice-name-link:hover {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-invoices-action-view,
    html[data-theme="light"] .sf-invoices-action-download {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-invoices-action-edit {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-white,
    html[data-theme="light"] .sf-invoices-page .text-slate-100,
    html[data-theme="light"] .sf-invoices-page .text-slate-200 {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-slate-300,
    html[data-theme="light"] .sf-invoices-page .text-slate-400,
    html[data-theme="light"] .sf-invoices-page .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-orange-300,
    html[data-theme="light"] .sf-invoices-page .sf-link {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-blue-300 {
        color: #1e3a8a !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-green-300 {
        color: #14532d !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-red-300 {
        color: #7f1d1d !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-yellow-300 {
        color: #713f12 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-slate-950,
    html[data-theme="light"] .sf-invoices-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-invoices-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-invoices-page .bg-slate-900,
    html[data-theme="light"] .sf-invoices-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-invoices-page .bg-slate-800,
    html[data-theme="light"] .sf-invoices-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-orange-500\/10 {
        background-color: #fff7ed !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-blue-500\/10 {
        background-color: #eff6ff !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-green-500\/10 {
        background-color: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 {
        background-color: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-yellow-500\/10 {
        background-color: #fefce8 !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-orange-100\/80,
    html[data-theme="light"] .sf-invoices-page .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-blue-100\/80 {
        color: #172554 !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-green-100\/80 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-invoices-page .text-yellow-100\/80 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-invoices-page .border-white\/10,
    html[data-theme="light"] .sf-invoices-page .border-slate-800 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #111827 !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-btn-danger {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-invoices-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-invoice-note {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-invoice-note-title {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-invoice-note-text {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-orange-500\/10 .text-orange-300,
    html[data-theme="light"] .sf-invoices-page .bg-orange-500\/10 .text-orange-100\/80,
    html[data-theme="light"] .sf-invoices-page .bg-orange-500\/10 .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-yellow-500\/10 .text-yellow-300,
    html[data-theme="light"] .sf-invoices-page .bg-yellow-500\/10 .text-yellow-100\/80 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-invoices-page .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-invoices-page .bg-green-500\/10 .text-green-100\/70 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-invoices-page .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }

    html[data-theme="light"] .sf-invoices-page .bg-blue-500\/10 .text-blue-300,
    html[data-theme="light"] .sf-invoices-page .bg-blue-500\/10 .text-blue-100\/80,
    html[data-theme="light"] .sf-invoices-page .bg-blue-500\/10 .text-blue-100\/70 {
        color: #172554 !important;
    }

    html[data-theme="light"] .sf-back-link,
    html[data-theme="light"] .sf-invoice-hero-chip,
    html[data-theme="light"] .sf-invoice-contact-value {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-invoice-status-button {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoice-status-button.is-active {
        border-color: #f97316 !important;
        background: #f97316 !important;
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-invoice-field-card,
    html[data-theme="light"] .sf-invoice-contact-row,
    html[data-theme="light"] .sf-invoice-related-card,
    html[data-theme="light"] .sf-invoice-activity-card {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-invoice-field-value,
    html[data-theme="light"] .sf-invoice-contact-name,
    html[data-theme="light"] .sf-invoice-related-title,
    html[data-theme="light"] .sf-invoice-activity-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoice-field-label,
    html[data-theme="light"] .sf-invoice-contact-label,
    html[data-theme="light"] .sf-invoice-contact-empty,
    html[data-theme="light"] .sf-invoice-related-type,
    html[data-theme="light"] .sf-invoice-related-meta,
    html[data-theme="light"] .sf-invoice-activity-meta,
    html[data-theme="light"] .sf-invoice-activity-detail {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-invoice-wa-chip {
        border-color: #86efac !important;
        background: #f0fdf4 !important;
        color: #166534 !important;
    }

    html[data-theme="light"] .sf-invoice-related-action {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    @media (max-width: 1023px) {
        .sf-invoices-page .sf-index-sticky-panel {
            position: static;
            margin-inline: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }
    }

    @media (max-width: 767px) {
        .sf-invoices-table-wrap .sf-table-scroll {
            overflow: visible;
        }

        .sf-invoice-status-grid,
        .sf-invoice-field-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .sf-invoice-contact-row,
        .sf-invoice-related-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .sf-invoices-table,
        .sf-invoices-table thead,
        .sf-invoices-table tbody,
        .sf-invoices-table tr,
        .sf-invoices-table th,
        .sf-invoices-table td {
            display: block;
            width: 100% !important;
        }

        .sf-invoices-table thead {
            display: none;
        }

        .sf-invoices-table tbody {
            display: grid;
            gap: 0.9rem;
        }

        .sf-invoices-table tbody tr:not(.sf-invoice-roi-row) {
            border: 1px solid rgba(30, 41, 59, 0.95);
            border-radius: 1rem 1rem 0 0;
            padding: 0.75rem;
        }

        .sf-invoice-roi-row {
            margin-top: -0.9rem;
            border: 1px solid rgba(30, 41, 59, 0.95);
            border-top: 0;
            border-radius: 0 0 1rem 1rem;
            padding: 0 0.75rem 0.75rem;
        }

        .sf-invoices-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border: 0 !important;
            padding: 0.45rem 0 !important;
            text-align: right;
        }

        .sf-invoices-table td::before {
            content: attr(data-label);
            flex: 0 0 8.5rem;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
        }

        .sf-invoice-roi-row td::before {
            content: "ROI";
        }
    }

    html[data-theme="light"] .sf-invoices-table tbody tr {
        border-color: #dbe3ef !important;
    }
</style>

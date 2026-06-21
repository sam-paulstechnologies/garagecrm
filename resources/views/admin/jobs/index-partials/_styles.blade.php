{{-- resources/views/admin/jobs/index-partials/_styles.blade.php --}}

<style>
    .sf-jobs-page {
        color: #e2e8f0;
    }

    .sf-jobs-page .sf-card,
    .sf-jobs-page .sf-stat-card,
    .sf-jobs-page .sf-page-header,
    .sf-jobs-page .sf-hero-panel,
    .sf-jobs-page .sf-table-wrap,
    .sf-jobs-page .sf-empty,
    .sf-jobs-panel {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-job-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-job-title,
    .sf-job-value,
    .sf-jobs-page .sf-stat-value,
    .sf-jobs-page .sf-section-title,
    .sf-jobs-page .sf-page-title,
    .sf-jobs-page .sf-table td {
        color: #f8fafc;
    }

    .sf-job-muted,
    .sf-jobs-page .sf-stat-label,
    .sf-jobs-page .sf-label,
    .sf-jobs-page .sf-section-subtitle,
    .sf-jobs-page .sf-page-subtitle,
    .sf-jobs-page .sf-help,
    .sf-jobs-page .sf-stat-note,
    .sf-jobs-page .sf-table th {
        color: #94a3b8;
    }

    .sf-job-input,
    .sf-job-select,
    .sf-jobs-page .sf-input,
    .sf-jobs-page .sf-select,
    .sf-jobs-page .sf-textarea {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-job-input::placeholder,
    .sf-jobs-page .sf-input::placeholder,
    .sf-jobs-page .sf-textarea::placeholder {
        color: #64748b;
    }

    .sf-job-input:focus,
    .sf-job-select:focus,
    .sf-jobs-page .sf-input:focus,
    .sf-jobs-page .sf-select:focus,
    .sf-jobs-page .sf-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-job-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-jobs-page .sf-table {
        background: transparent;
        border-collapse: separate;
        border-spacing: 0;
    }

    .sf-jobs-page .sf-table thead,
    .sf-jobs-page .sf-table thead tr,
    .sf-jobs-page .sf-table th {
        background: rgba(8, 17, 31, 0.92);
        color: #94a3b8;
    }

    .sf-jobs-page .sf-table tbody tr {
        background: rgba(11, 18, 32, 0.62);
    }

    .sf-jobs-page .sf-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07);
    }

    .sf-jobs-page .sf-table td {
        background: transparent;
        color: #f8fafc;
    }

    .sf-jobs-page .sf-index-sticky-panel {
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

    .sf-job-name-link {
        display: inline-flex;
        max-width: 100%;
        color: #f8fafc;
        font-weight: 900;
        text-decoration: none;
    }

    .sf-job-name-link:hover {
        color: #fdba74;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-jobs-action-group {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
    }

    .sf-jobs-action-pill {
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

    .sf-jobs-action-view {
        border-color: rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
    }

    .sf-jobs-action-edit {
        border-color: rgba(148, 163, 184, 0.32);
        background: rgba(15, 23, 42, 0.84);
        color: #e2e8f0;
    }

    .sf-jobs-action-archive {
        border-color: rgba(248, 113, 113, 0.42);
        background: rgba(239, 68, 68, 0.10);
        color: #fecaca;
    }

    .sf-jobs-action-pill:hover,
    .sf-jobs-action-pill:focus {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
    }

    .sf-jobs-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-jobs-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-jobs-page .sf-badge-blue,
    .sf-jobs-page .sf-badge-orange,
    .sf-jobs-page .sf-badge-yellow,
    .sf-jobs-page .sf-badge-green,
    .sf-jobs-page .sf-badge-red,
    .sf-jobs-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 0.28rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .sf-jobs-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-jobs-page .sf-badge-orange { background: #ffedd5; color: #7c2d12; }
    .sf-jobs-page .sf-badge-yellow { background: #fef3c7; color: #713f12; }
    .sf-jobs-page .sf-badge-green { background: #dcfce7; color: #14532d; }
    .sf-jobs-page .sf-badge-red { background: #fee2e2; color: #7f1d1d; }
    .sf-jobs-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    .sf-jobs-page .sf-kicker {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        background: #ffedd5;
        padding: 0.28rem 0.62rem;
        color: #7c2d12;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
        text-transform: uppercase;
    }

    .sf-jobs-page .sf-btn-primary,
    .sf-jobs-page .sf-btn-secondary,
    .sf-jobs-page .sf-btn-danger {
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

    .sf-jobs-page .sf-btn-primary {
        background: #ff7a1a;
        border: 1px solid #ff7a1a;
        color: #111827;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-jobs-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-jobs-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-jobs-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-jobs-page .sf-btn-danger {
        border: 1px solid rgba(248, 113, 113, 0.42);
        background: rgba(239, 68, 68, 0.10);
        color: #fecaca;
    }

    .sf-jobs-page .sf-btn-danger:hover {
        background: rgba(239, 68, 68, 0.18);
        transform: translateY(-1px);
    }

    .sf-jobs-page .sf-file-input {
        border-color: rgba(255, 255, 255, 0.1);
        background: #08111f;
        color: #cbd5e1;
    }

    .sf-job-note {
        border-color: rgba(96, 165, 250, 0.24);
        background: rgba(59, 130, 246, 0.10);
    }

    .sf-job-note-title {
        color: #93c5fd;
    }

    .sf-job-note-text {
        color: #dbeafe;
    }

    .sf-back-link {
        display: inline-flex;
        width: fit-content;
        color: #fdba74;
        font-size: 0.85rem;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-job-hero-chip,
    .sf-job-contact-value {
        color: #fdba74;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sf-job-stage-panel .sf-card-body {
        padding: 1rem;
    }

    .sf-job-stage-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .sf-job-stage-button {
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

    .sf-job-stage-button:hover,
    .sf-job-stage-button:focus {
        border-color: rgba(251, 146, 60, 0.55);
        color: #fed7aa;
        transform: translateY(-1px);
    }

    .sf-job-stage-button.is-active {
        border-color: rgba(251, 146, 60, 0.58);
        background: #ff7a1a;
        color: #111827;
        box-shadow: 0 10px 22px rgba(249, 115, 22, 0.20);
    }

    .sf-job-complete-details summary {
        cursor: pointer;
        list-style: none;
    }

    .sf-job-complete-details summary::-webkit-details-marker {
        display: none;
    }

    .sf-job-complete-form {
        margin-top: 0.75rem;
        border-radius: 1rem;
        border: 1px solid rgba(251, 146, 60, 0.28);
        background: rgba(249, 115, 22, 0.10);
        padding: 0.9rem;
    }

    .sf-job-mini-label {
        display: block;
        margin-bottom: 0.3rem;
        color: #fdba74;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-job-mini-input {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(251, 146, 60, 0.34);
        background: rgba(15, 23, 42, 0.92);
        color: #f8fafc;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .sf-job-stage-submit {
        margin-top: 0.75rem;
        width: 100%;
        border-radius: 999px;
        background: #ff7a1a;
        padding: 0.6rem 0.8rem;
        color: #111827;
        font-size: 0.78rem;
        font-weight: 900;
    }

    .sf-job-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sf-job-field-card {
        min-width: 0;
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.88);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.9rem;
    }

    .sf-job-field-label,
    .sf-job-contact-label,
    .sf-job-related-type {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .sf-job-field-value {
        margin-top: 0.25rem;
        color: #f8fafc;
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.55;
    }

    .sf-job-contact-name {
        color: #f8fafc;
        font-size: 1rem;
        font-weight: 900;
    }

    .sf-job-contact-row,
    .sf-job-related-card,
    .sf-job-activity-card {
        border-radius: 1rem;
        border: 1px solid rgba(51, 65, 85, 0.9);
        background: rgba(8, 17, 31, 0.64);
        padding: 0.9rem;
    }

    .sf-job-contact-row,
    .sf-job-related-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .sf-job-contact-empty,
    .sf-job-related-meta,
    .sf-job-activity-meta,
    .sf-job-activity-detail {
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.55;
    }

    .sf-job-wa-chip {
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

    .sf-job-related-card {
        text-decoration: none;
    }

    .sf-job-related-title,
    .sf-job-activity-title {
        display: block;
        margin-top: 0.18rem;
        color: #f8fafc;
        font-weight: 900;
    }

    .sf-job-related-action {
        flex: 0 0 auto;
        border-radius: 999px;
        border: 1px solid rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.10);
        padding: 0.42rem 0.7rem;
        color: #fed7aa;
        font-size: 0.75rem;
        font-weight: 900;
    }

    #job-activity-timeline {
        scroll-margin-top: 7rem;
    }

    .sf-job-bucket-card {
        color: #f8fafc;
    }

    .sf-job-bucket-card:hover {
        transform: translateY(-1px);
    }

    .sf-job-bucket-active {
        border-color: rgba(251, 146, 60, 0.45) !important;
        box-shadow: 0 0 0 1px rgba(251, 146, 60, 0.22);
    }

    .sf-job-bucket-slate {
        border-color: rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.70);
    }

    .sf-job-bucket-orange {
        border-color: rgba(251, 146, 60, 0.28);
        background: rgba(249, 115, 22, 0.12);
    }

    .sf-job-bucket-blue {
        border-color: rgba(96, 165, 250, 0.28);
        background: rgba(59, 130, 246, 0.12);
    }

    .sf-job-bucket-red {
        border-color: rgba(248, 113, 113, 0.28);
        background: rgba(239, 68, 68, 0.12);
    }

    .sf-job-bucket-green {
        border-color: rgba(74, 222, 128, 0.28);
        background: rgba(34, 197, 94, 0.12);
    }

    /*
    |--------------------------------------------------------------------------
    | Light Mode
    |--------------------------------------------------------------------------
    */

    html[data-theme="light"] .sf-jobs-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-jobs-page .sf-card,
    html[data-theme="light"] .sf-jobs-page .sf-stat-card,
    html[data-theme="light"] .sf-jobs-page .sf-page-header,
    html[data-theme="light"] .sf-jobs-page .sf-hero-panel,
    html[data-theme="light"] .sf-jobs-page .sf-table-wrap,
    html[data-theme="light"] .sf-jobs-page .sf-empty,
    html[data-theme="light"] .sf-jobs-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-job-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-job-title,
    html[data-theme="light"] .sf-job-value,
    html[data-theme="light"] .sf-jobs-page .sf-stat-value,
    html[data-theme="light"] .sf-jobs-page .sf-section-title,
    html[data-theme="light"] .sf-jobs-page .sf-page-title,
    html[data-theme="light"] .sf-jobs-page .sf-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-muted,
    html[data-theme="light"] .sf-jobs-page .sf-stat-label,
    html[data-theme="light"] .sf-jobs-page .sf-label,
    html[data-theme="light"] .sf-jobs-page .sf-section-subtitle,
    html[data-theme="light"] .sf-jobs-page .sf-page-subtitle,
    html[data-theme="light"] .sf-jobs-page .sf-help,
    html[data-theme="light"] .sf-jobs-page .sf-stat-note,
    html[data-theme="light"] .sf-jobs-page .sf-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-job-input,
    html[data-theme="light"] .sf-job-select,
    html[data-theme="light"] .sf-jobs-page .sf-input,
    html[data-theme="light"] .sf-jobs-page .sf-select,
    html[data-theme="light"] .sf-jobs-page .sf-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-input::placeholder,
    html[data-theme="light"] .sf-jobs-page .sf-input::placeholder,
    html[data-theme="light"] .sf-jobs-page .sf-textarea::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-job-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-table,
    html[data-theme="light"] .sf-jobs-page .sf-table thead,
    html[data-theme="light"] .sf-jobs-page .sf-table tbody,
    html[data-theme="light"] .sf-jobs-page .sf-table tfoot {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-table thead tr,
    html[data-theme="light"] .sf-jobs-page .sf-table th {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-table tbody tr {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-table tbody tr:hover {
        background: #f8fbff !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-table td {
        border-color: #dbe3ef !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-index-sticky-panel {
        background: rgba(241, 245, 249, 0.94) !important;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12) !important;
    }

    html[data-theme="light"] .sf-job-name-link {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-name-link:hover {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-jobs-action-view {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-jobs-action-edit {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-jobs-action-archive {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-white,
    html[data-theme="light"] .sf-jobs-page .text-slate-100,
    html[data-theme="light"] .sf-jobs-page .text-slate-200 {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-slate-300,
    html[data-theme="light"] .sf-jobs-page .text-slate-400,
    html[data-theme="light"] .sf-jobs-page .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-orange-300,
    html[data-theme="light"] .sf-jobs-page .sf-link {
        color: #7c2d12 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-green-300 {
        color: #14532d !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-red-300 {
        color: #7f1d1d !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-yellow-300 {
        color: #713f12 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-slate-950,
    html[data-theme="light"] .sf-jobs-page .bg-slate-950\/60,
    html[data-theme="light"] .sf-jobs-page .bg-slate-950\/70,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900\/60,
    html[data-theme="light"] .sf-jobs-page .bg-slate-900\/70,
    html[data-theme="light"] .sf-jobs-page .bg-slate-800,
    html[data-theme="light"] .sf-jobs-page .bg-slate-800\/60 {
        background-color: #ffffff !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-orange-500\/10 {
        background-color: #fff7ed !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-blue-500\/10 {
        background-color: #eff6ff !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-green-500\/10 {
        background-color: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 {
        background-color: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-yellow-500\/10 {
        background-color: #fefce8 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-blue-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-blue-100\/80,
    html[data-theme="light"] .sf-jobs-page .text-blue-200 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-green-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-green-100\/80 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-red-100\/70 {
        color: #450a0a !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-orange-100\/80 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-jobs-page .border-white\/10,
    html[data-theme="light"] .sf-jobs-page .border-slate-800 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #111827 !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-orange-500\/10 .text-orange-300,
    html[data-theme="light"] .sf-jobs-page .bg-orange-500\/10 .text-orange-100\/80,
    html[data-theme="light"] .sf-jobs-page .bg-orange-500\/10 .text-orange-100\/70 {
        color: #431407 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-yellow-500\/10 .text-yellow-300,
    html[data-theme="light"] .sf-jobs-page .bg-yellow-500\/10 .text-yellow-100\/80,
    html[data-theme="light"] .sf-jobs-page .bg-yellow-500\/10 .text-yellow-100\/70 {
        color: #422006 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-green-500\/10 .text-green-300,
    html[data-theme="light"] .sf-jobs-page .bg-green-500\/10 .text-green-100\/80,
    html[data-theme="light"] .sf-jobs-page .bg-green-500\/10 .text-green-100\/70 {
        color: #052e16 !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 .text-red-300,
    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 .text-red-100\/90,
    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 .text-red-100\/80,
    html[data-theme="light"] .sf-jobs-page .bg-red-500\/10 .text-red-100\/70 {
        color: #450a0a !important;
    }

    html[data-theme="light"] .sf-jobs-page .bg-blue-500\/10 .text-blue-300,
    html[data-theme="light"] .sf-jobs-page .bg-blue-500\/10 .text-blue-100\/80,
    html[data-theme="light"] .sf-jobs-page .bg-blue-500\/10 .text-blue-100\/70 {
        color: #172554 !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-file-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-back-link,
    html[data-theme="light"] .sf-job-hero-chip,
    html[data-theme="light"] .sf-job-contact-value {
        color: #b45309 !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-danger {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-job-stage-button {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-stage-button.is-active {
        border-color: #f97316 !important;
        background: #f97316 !important;
        color: #111827 !important;
    }

    html[data-theme="light"] .sf-job-complete-form {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-job-mini-input {
        border-color: #fdba74 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-field-card,
    html[data-theme="light"] .sf-job-contact-row,
    html[data-theme="light"] .sf-job-related-card,
    html[data-theme="light"] .sf-job-activity-card {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-job-field-value,
    html[data-theme="light"] .sf-job-contact-name,
    html[data-theme="light"] .sf-job-related-title,
    html[data-theme="light"] .sf-job-activity-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-field-label,
    html[data-theme="light"] .sf-job-contact-label,
    html[data-theme="light"] .sf-job-contact-empty,
    html[data-theme="light"] .sf-job-related-type,
    html[data-theme="light"] .sf-job-related-meta,
    html[data-theme="light"] .sf-job-activity-meta,
    html[data-theme="light"] .sf-job-activity-detail {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-job-wa-chip {
        border-color: #86efac !important;
        background: #f0fdf4 !important;
        color: #166534 !important;
    }

    html[data-theme="light"] .sf-job-related-action {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-job-note {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-job-note-title {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-job-note-text {
        color: #1e3a8a !important;
    }

    html[data-theme="light"] .sf-job-bucket-card {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-job-bucket-slate {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-job-bucket-orange {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-job-bucket-blue {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-job-bucket-red {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-job-bucket-green {
        border-color: #bbf7d0 !important;
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-job-bucket-active {
        border-color: rgba(234, 88, 12, 0.45) !important;
        box-shadow: 0 0 0 1px rgba(234, 88, 12, 0.20) !important;
    }

    @media (max-width: 1023px) {
        .sf-jobs-page .sf-index-sticky-panel {
            position: static;
            margin-inline: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }
    }

    @media (max-width: 767px) {
        .sf-job-stage-grid,
        .sf-job-field-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .sf-job-contact-row,
        .sf-job-related-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .sf-jobs-table-wrap .sf-table-scroll {
            overflow: visible;
        }

        .sf-jobs-table,
        .sf-jobs-table thead,
        .sf-jobs-table tbody,
        .sf-jobs-table tr,
        .sf-jobs-table th,
        .sf-jobs-table td {
            display: block;
            width: 100% !important;
        }

        .sf-jobs-table thead {
            display: none;
        }

        .sf-jobs-table tbody {
            display: grid;
            gap: 0.9rem;
        }

        .sf-jobs-table tbody tr {
            border: 1px solid rgba(30, 41, 59, 0.95);
            border-radius: 1rem;
            padding: 0.75rem;
        }

        .sf-jobs-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border: 0 !important;
            padding: 0.45rem 0 !important;
            text-align: right;
        }

        .sf-jobs-table td::before {
            content: attr(data-label);
            flex: 0 0 8.5rem;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
        }

        .sf-jobs-action-group {
            justify-content: flex-end;
        }
    }

    html[data-theme="light"] .sf-jobs-table tbody tr {
        border-color: #dbe3ef !important;
    }
</style>

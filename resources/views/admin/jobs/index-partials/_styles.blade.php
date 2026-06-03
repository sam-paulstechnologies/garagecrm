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

    .sf-jobs-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-jobs-page .sf-link:hover {
        color: #ff7a1a;
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
        color: #ffffff;
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
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-yellow-300 {
        color: #a16207 !important;
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
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-blue-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-blue-100\/80,
    html[data-theme="light"] .sf-jobs-page .text-blue-200 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-green-100\/70,
    html[data-theme="light"] .sf-jobs-page .text-green-100\/80 {
        color: #166534 !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-red-100\/70 {
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-jobs-page .text-orange-100\/80 {
        color: #9a3412 !important;
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
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-jobs-page .sf-file-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
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
</style>
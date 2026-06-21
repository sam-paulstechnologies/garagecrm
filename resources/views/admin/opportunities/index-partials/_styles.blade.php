{{-- resources/views/admin/opportunities/index-partials/_styles.blade.php --}}

<style>
    .sf-opportunities-page {
        width: 100% !important;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        color: #e2e8f0;
    }

    .sf-opportunities-page .sf-index-sticky-panel {
        position: sticky;
        top: 5rem;
        z-index: 30;
        margin-left: -0.25rem;
        margin-right: -0.25rem;
        padding: 0.25rem;
        border-radius: 1.5rem;
        background: rgba(2, 6, 23, 0.88);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
        backdrop-filter: blur(16px);
    }

    .sf-opportunity-panel {
        width: 100%;
        max-width: none;
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }

    .sf-opportunity-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-opportunity-title,
    .sf-opportunity-value,
    .sf-opportunity-table td {
        color: #f8fafc;
    }

    .sf-opportunity-muted,
    .sf-opportunity-table th {
        color: #94a3b8;
    }

    .sf-opportunity-input,
    .sf-opportunity-select {
        border-color: #334155;
        background: #08111f;
        color: #f8fafc;
    }

    .sf-opportunity-input::placeholder {
        color: #64748b;
    }

    .sf-opportunity-input:focus,
    .sf-opportunity-select:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-opportunity-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-opportunity-filter-pill:hover,
    .sf-opportunity-filter-pill:focus {
        border-color: rgba(251, 146, 60, 0.50);
        background: rgba(249, 115, 22, 0.16);
        color: #fed7aa;
    }

    .sf-opportunity-table {
        width: 100%;
        background: transparent;
        border-collapse: separate;
        border-spacing: 0;
    }

    .sf-opportunity-table th,
    .sf-opportunity-table td {
        min-width: 0;
    }

    .sf-opportunity-table thead,
    .sf-opportunity-table thead tr,
    .sf-opportunity-table th {
        background: rgba(8, 17, 31, 0.92);
    }

    .sf-opportunity-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9);
        background: rgba(11, 18, 32, 0.62);
    }

    .sf-opportunity-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07);
    }

    .sf-opportunity-table td {
        background: transparent;
    }

    .sf-opportunity-table th {
        color: #94a3b8;
    }

    .sf-opportunity-table td {
        color: #f8fafc;
    }

    .sf-opportunity-table .sf-opportunity-name-link,
    .sf-opportunity-table .sf-opportunity-value,
    .sf-opportunity-table .sf-opportunity-muted,
    .sf-opportunity-table .sf-opportunity-money {
        overflow-wrap: anywhere;
    }

    .sf-opportunities-page .sf-btn-primary,
    .sf-opportunities-page .sf-btn-secondary,
    .sf-opportunities-page .sf-btn-danger {
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

    .sf-opportunities-page .sf-btn-primary {
        background: #ff7a1a;
        color: #ffffff;
        border: 1px solid #ff7a1a;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-opportunities-page .sf-btn-primary:hover {
        background: #ea6508;
        border-color: #ea6508;
        transform: translateY(-1px);
    }

    .sf-opportunities-page .sf-btn-secondary {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-opportunities-page .sf-btn-secondary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .sf-opportunities-page .sf-link {
        color: #fdba74;
        font-weight: 800;
    }

    .sf-opportunities-page .sf-link:hover {
        color: #ff7a1a;
    }

    .sf-opportunities-page .sf-opportunity-name-link {
        color: #f8fafc;
        text-decoration: none;
    }

    .sf-opportunities-page .sf-opportunity-name-link:hover {
        color: #fdba74;
    }

    .sf-opportunities-page .sf-opportunity-money {
        color: #fdba74;
    }

    .sf-opportunity-action-group {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.375rem;
        max-width: 100%;
    }

    .sf-opportunity-action-pill {
        min-height: 1.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        padding: 0.375rem 0.625rem;
        background: rgba(15, 23, 42, 0.82);
        color: #e2e8f0;
        font-size: 0.75rem;
        font-weight: 900;
        line-height: 1;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sf-opportunity-action-pill:hover {
        border-color: rgba(251, 146, 60, 0.55);
        background: rgba(249, 115, 22, 0.16);
        color: #fed7aa;
    }

    .sf-opportunity-action-pill-danger {
        color: #fecaca;
    }

    .sf-opportunity-action-pill-danger:hover {
        border-color: rgba(248, 113, 113, 0.52);
        background: rgba(239, 68, 68, 0.14);
        color: #fee2e2;
    }

    .sf-opportunities-page .sf-btn-table-view,
    .sf-opportunities-page .sf-btn-table-edit {
        display: inline-flex;
        min-height: 2rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.65rem;
        padding: 0.35rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1;
        transition: all 0.2s ease;
    }

    .sf-opportunities-page .sf-btn-table-view {
        border: 1px solid rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.10);
        color: #fed7aa;
    }

    .sf-opportunities-page .sf-btn-table-edit {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-opportunities-page .sf-btn-table-view:hover,
    .sf-opportunities-page .sf-btn-table-edit:hover {
        transform: translateY(-1px);
    }

    .sf-opportunities-page .sf-badge-blue,
    .sf-opportunities-page .sf-badge-orange,
    .sf-opportunities-page .sf-badge-yellow,
    .sf-opportunities-page .sf-badge-green,
    .sf-opportunities-page .sf-badge-red,
    .sf-opportunities-page .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1.15;
        max-width: 100%;
        white-space: normal;
        text-align: center;
    }

    .sf-opportunities-page .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-opportunities-page .sf-badge-orange { background: #ffedd5; color: #9a3412; }
    .sf-opportunities-page .sf-badge-yellow { background: #fef3c7; color: #92400e; }
    .sf-opportunities-page .sf-badge-green { background: #dcfce7; color: #166534; }
    .sf-opportunities-page .sf-badge-red { background: #fee2e2; color: #991b1b; }
    .sf-opportunities-page .sf-badge-slate { background: #e2e8f0; color: #334155; }

    .sf-opportunity-bucket-active {
        border-color: rgba(251, 146, 60, 0.45);
        background: rgba(249, 115, 22, 0.12);
        box-shadow: 0 0 0 1px rgba(251, 146, 60, 0.2);
    }

    .sf-opportunity-bucket-idle {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.74);
    }

    .sf-opportunity-bucket-idle:hover {
        border-color: rgba(251, 146, 60, 0.35);
        background: rgba(15, 23, 42, 0.92);
    }

    @media (max-width: 1023px) {
        .sf-opportunities-page .sf-index-sticky-panel {
            position: static;
            margin-left: 0;
            margin-right: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }

        .sf-opportunity-table th,
        .sf-opportunity-table td {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .sf-opportunity-action-group {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }

        .sf-opportunity-action-pill {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .sf-table-scroll {
            overflow-x: visible;
        }

        .sf-opportunity-table,
        .sf-opportunity-table thead,
        .sf-opportunity-table tbody,
        .sf-opportunity-table tr,
        .sf-opportunity-table th,
        .sf-opportunity-table td {
            display: block;
            width: 100%;
        }

        .sf-opportunity-table thead {
            display: none;
        }

        .sf-opportunity-table tbody {
            display: grid;
            gap: 0.75rem;
            padding: 0.75rem;
        }

        .sf-opportunity-table tbody tr {
            overflow: hidden;
            border: 1px solid rgba(30, 41, 59, 0.9);
            border-radius: 1rem;
        }

        .sf-opportunity-table tbody td {
            display: grid;
            grid-template-columns: minmax(7rem, 34%) minmax(0, 1fr);
            gap: 0.75rem;
            border-bottom: 1px solid rgba(30, 41, 59, 0.75);
            padding: 0.75rem;
            text-align: left;
        }

        .sf-opportunity-table tbody td::before {
            content: attr(data-label);
            color: #94a3b8;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .sf-opportunity-table tbody td:last-child {
            border-bottom: 0;
        }

        .sf-opportunity-table tbody td[data-label="Opportunity"],
        .sf-opportunity-table tbody td[data-label="Actions"] {
            grid-template-columns: 1fr;
        }

        .sf-opportunity-table tbody td[data-label="Opportunity"]::before,
        .sf-opportunity-table tbody td[data-label="Actions"]::before {
            display: none;
        }

        .sf-opportunity-table tbody td[data-label="Actions"] {
            text-align: left;
        }

        .sf-opportunity-action-group {
            flex-direction: row;
            justify-content: flex-start;
            width: 100%;
        }

        .sf-opportunity-action-pill {
            width: auto;
            min-width: 5rem;
            flex: 1 1 5rem;
        }
    }

    @media (max-width: 420px) {
        .sf-opportunity-table tbody td {
            grid-template-columns: 1fr;
            gap: 0.35rem;
        }

        .sf-opportunity-action-group {
            flex-direction: column;
        }
    }

    html[data-theme="light"] .sf-opportunities-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-index-sticky-panel {
        background: rgba(248, 250, 252, 0.92) !important;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.10) !important;
    }

    html[data-theme="light"] .sf-opportunity-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-opportunity-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opportunity-title,
    html[data-theme="light"] .sf-opportunity-value,
    html[data-theme="light"] .sf-opportunity-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunity-muted,
    html[data-theme="light"] .sf-opportunity-table th {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-opportunity-input,
    html[data-theme="light"] .sf-opportunity-select {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunity-input::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-opportunity-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-opportunity-filter-pill:hover,
    html[data-theme="light"] .sf-opportunity-filter-pill:focus {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-opportunity-table,
    html[data-theme="light"] .sf-opportunity-table thead,
    html[data-theme="light"] .sf-opportunity-table tbody,
    html[data-theme="light"] .sf-opportunity-table tfoot {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-opportunity-table thead tr,
    html[data-theme="light"] .sf-opportunity-table th {
        background: #f8fafc !important;
        border-color: #dbe3ef !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-opportunity-table tbody tr {
        border-color: #e2e8f0 !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-opportunity-table tbody tr:nth-child(even) {
        background: #fbfdff !important;
    }

    html[data-theme="light"] .sf-opportunity-table tbody tr:hover {
        background: #f3f6fb !important;
    }

    html[data-theme="light"] .sf-opportunity-table td {
        border-color: #e2e8f0 !important;
        background: transparent !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-primary {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.20) !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-primary:hover {
        background: #ea580c !important;
        border-color: #ea580c !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-link,
    html[data-theme="light"] .sf-opportunities-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-opportunity-name-link {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-opportunity-name-link:hover,
    html[data-theme="light"] .sf-opportunities-page .sf-opportunity-money {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-table-view {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-table-edit {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .sf-btn-table-view:hover,
    html[data-theme="light"] .sf-opportunities-page .sf-btn-table-edit:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opportunity-action-pill {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-opportunity-action-pill:hover {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-opportunity-action-pill-danger {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-opportunity-action-pill-danger:hover {
        border-color: #fca5a5 !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }

    html[data-theme="light"] .sf-opportunities-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .text-red-300 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-opportunities-page .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-opportunities-page .text-yellow-300 {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .bg-blue-500\/10 {
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-opportunities-page .bg-green-500\/10,
    html[data-theme="light"] .sf-opportunities-page .bg-emerald-500\/10 {
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .bg-yellow-500\/10 {
        background: #fefce8 !important;
    }

    html[data-theme="light"] .sf-opportunities-page .bg-orange-500\/10 {
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-opportunities-page .bg-red-500\/10 {
        background: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-opportunity-bucket-active {
        border-color: rgba(234, 88, 12, 0.35) !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-opportunity-bucket-idle {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-opportunity-bucket-idle:hover {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }
</style>

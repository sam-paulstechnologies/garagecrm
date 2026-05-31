@include('admin.opportunities.index-partials._styles')
<style>
    .sf-opportunities-archive-page { color: #e2e8f0; }
    .sf-opportunities-archive-page .sf-opportunity-panel { border-color: #1e293b; background: rgba(11,18,32,.88); color: #e2e8f0; }
    .sf-opportunities-archive-page .sf-opportunity-muted { color: #94a3b8; }
    .sf-opportunities-archive-page .sf-opportunity-value, .sf-opportunities-archive-page .sf-opportunity-table td { color: #f8fafc; }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-panel { border-color: #dbe3ef !important; background: #fff !important; color: #0f172a !important; box-shadow: 0 14px 36px rgba(15,23,42,.08) !important; }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-muted { color: #64748b !important; }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-value, html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table td { color: #0f172a !important; }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table,
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table thead,
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table tbody,
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table tfoot {
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table thead tr,
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table th {
        background: #f8fafc !important;
        border-color: #dbe3ef !important;
        color: #475569 !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table tbody tr {
        border-color: #e2e8f0 !important;
        background: #ffffff !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table tbody tr:nth-child(even) {
        background: #fbfdff !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table tbody tr:hover {
        background: #f3f6fb !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table td {
        border-color: #e2e8f0 !important;
        background: transparent !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table .sf-opportunity-muted {
        color: #64748b !important;
    }
    html[data-theme="light"] .sf-opportunities-archive-page .sf-opportunity-table .text-orange-300 {
        color: #c2410c !important;
    }
</style>

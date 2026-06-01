{{-- resources/views/admin/clients/show-partials/_styles.blade.php --}}

<style>
    .sf-client-show-panel {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-client-show-link {
        color: #fb923c;
        font-weight: 800;
        font-size: 13px;
    }

    .sf-client-show-link:hover {
        color: #fdba74;
    }

    html[data-theme="light"] .sf-client-show-panel {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-client-show-link {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-client-show-link:hover {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-client-profile-hero {
        border-color: rgba(15, 23, 42, 0.12) !important;
        background: linear-gradient(135deg, #0f172a 0%, #111827 65%, rgba(124, 45, 18, 0.72) 100%) !important;
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-client-profile-hero h1,
    html[data-theme="light"] .sf-client-profile-hero span,
    html[data-theme="light"] .sf-client-profile-hero div,
    html[data-theme="light"] .sf-client-profile-hero .text-white,
    html[data-theme="light"] .sf-client-profile-hero .text-slate-100,
    html[data-theme="light"] .sf-client-profile-hero .text-slate-200 {
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-client-profile-hero .text-slate-300,
    html[data-theme="light"] .sf-client-profile-hero .text-slate-400,
    html[data-theme="light"] .sf-client-profile-hero .text-slate-500 {
        color: #cbd5e1 !important;
    }
</style>
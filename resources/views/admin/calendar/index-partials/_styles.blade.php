<style>
    .sf-calendar-page { color: #e2e8f0; }
    .sf-calendar-page .sf-card,
    .sf-calendar-page .sf-page-header,
    .sf-calendar-page .sf-hero-panel,
    .sf-calendar-page .garage-calendar {
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.88);
        color: #e2e8f0;
    }
    .sf-calendar-page .sf-btn-primary { background: #ff7a1a; color: #fff; }
    .sf-calendar-page .sf-btn-primary:hover { background: #ea6508; }
    .sf-calendar-page .sf-btn-secondary { border-color: #334155; background: #0f172a; color: #e2e8f0; }

    .garage-calendar {
        min-height: 720px;
    }

    .sf-calendar-page .fc {
        color: #e2e8f0;
    }

    .sf-calendar-page .fc .fc-toolbar {
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .sf-calendar-page .fc .fc-toolbar-title {
        color: #ffffff;
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -0.025em;
    }

    .sf-calendar-page .fc .fc-button {
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        background: rgba(15, 23, 42, 0.9) !important;
        color: #e2e8f0 !important;
        border-radius: 0.75rem !important;
        padding: 0.45rem 0.75rem !important;
        font-size: 0.8rem !important;
        font-weight: 800 !important;
        box-shadow: none !important;
        text-transform: capitalize !important;
    }

    .sf-calendar-page .fc .fc-button:hover,
    .sf-calendar-page .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: #ff7a1a !important;
        border-color: rgba(255, 122, 26, 0.55) !important;
        color: #ffffff !important;
    }

    .sf-calendar-page .fc-theme-standard td,
    .sf-calendar-page .fc-theme-standard th,
    .sf-calendar-page .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .sf-calendar-page .fc-col-header-cell {
        background: rgba(15, 23, 42, 0.95);
    }

    .sf-calendar-page .fc-col-header-cell-cushion {
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 900;
        padding: 0.75rem 0;
        text-transform: uppercase;
    }

    .sf-calendar-page .fc-daygrid-day-number {
        color: #cbd5e1;
        font-size: 0.8rem;
        font-weight: 800;
        padding: 0.5rem;
    }

    .sf-calendar-page .fc-day-today {
        background: rgba(249, 115, 22, 0.08) !important;
    }

    .sf-calendar-page .fc-daygrid-day {
        background: rgba(2, 6, 23, 0.18);
    }

    .sf-calendar-page .fc-daygrid-day:hover {
        background: rgba(37, 99, 235, 0.08);
    }

    .sf-calendar-page .fc-event {
        border: 0 !important;
        border-radius: 0.75rem !important;
        padding: 3px 6px !important;
        font-size: 0.75rem !important;
        font-weight: 800 !important;
        cursor: pointer;
    }

    .sf-calendar-page .fc-daygrid-event {
        margin: 2px 4px;
    }

    .sf-calendar-page .fc-daygrid-dot-event,
    .sf-calendar-page .fc-daygrid-block-event {
        align-items: center;
        background: rgba(99, 102, 241, 0.22) !important;
        border: 1px solid rgba(129, 140, 248, 0.35) !important;
        color: #ffffff !important;
        display: flex;
        gap: 0.35rem;
        min-height: 1.5rem;
        white-space: normal;
    }

    .sf-calendar-page .fc-daygrid-dot-event .fc-event-title,
    .sf-calendar-page .fc-daygrid-block-event .fc-event-title,
    .sf-calendar-page .fc-event-main,
    .sf-calendar-page .fc-event-title,
    .sf-calendar-page .fc-event-time {
        color: #ffffff !important;
        display: inline;
        font-weight: 800;
        line-height: 1.2;
    }

    .sf-calendar-page .fc-daygrid-event-dot {
        border-color: #c4b5fd !important;
        border-width: 5px !important;
        flex: 0 0 auto;
    }

    .sf-calendar-page .fc-list {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .sf-calendar-page .fc-list-day-cushion {
        background: rgba(15, 23, 42, 0.95) !important;
        color: #ffffff !important;
    }

    .sf-calendar-page .fc-list-event:hover td {
        background: rgba(37, 99, 235, 0.08) !important;
    }

    .sf-calendar-page .fc-list-event-title,
    .sf-calendar-page .fc-list-event-time {
        color: #e2e8f0;
    }

    .sf-calendar-page .fc-scroller {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
    }

    html[data-theme="light"] .sf-calendar-page { color: #0f172a; }
    html[data-theme="light"] .sf-calendar-page .sf-card,
    html[data-theme="light"] .sf-calendar-page .sf-page-header,
    html[data-theme="light"] .sf-calendar-page .sf-hero-panel,
    html[data-theme="light"] .sf-calendar-page .garage-calendar {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }
    html[data-theme="light"] .sf-calendar-page .sf-page-title,
    html[data-theme="light"] .sf-calendar-page .sf-section-title,
    html[data-theme="light"] .sf-calendar-page .fc .fc-toolbar-title {
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-calendar-page .sf-page-subtitle,
    html[data-theme="light"] .sf-calendar-page .sf-section-subtitle,
    html[data-theme="light"] .sf-calendar-page .text-slate-300,
    html[data-theme="light"] .sf-calendar-page .text-slate-400,
    html[data-theme="light"] .sf-calendar-page .text-slate-500 {
        color: #64748b !important;
    }
    html[data-theme="light"] .sf-calendar-page .text-blue-300 { color: #1d4ed8 !important; }
    html[data-theme="light"] .sf-calendar-page .text-blue-100\/80 { color: #1e40af !important; }
    html[data-theme="light"] .sf-calendar-page .bg-blue-500\/10 { background-color: #eff6ff !important; }
    html[data-theme="light"] .sf-calendar-page .sf-btn-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-calendar-page .border-white\/10 {
        border-color: #dbe3ef !important;
    }
    html[data-theme="light"] .sf-calendar-page .bg-slate-950\/60 {
        background-color: #ffffff !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc {
        color: #0f172a;
    }
    html[data-theme="light"] .sf-calendar-page .fc .fc-button {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc .fc-button:hover,
    html[data-theme="light"] .sf-calendar-page .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: #ff7a1a !important;
        border-color: #ff7a1a !important;
        color: #ffffff !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-theme-standard td,
    html[data-theme="light"] .sf-calendar-page .fc-theme-standard th,
    html[data-theme="light"] .sf-calendar-page .fc-theme-standard .fc-scrollgrid {
        border-color: #dbe3ef;
    }
    html[data-theme="light"] .sf-calendar-page .fc-col-header-cell {
        background: #f8fafc;
    }
    html[data-theme="light"] .sf-calendar-page .fc-col-header-cell-cushion {
        color: #475569;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-day {
        background: #ffffff;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-day:hover {
        background: #f8fbff;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-day-number {
        color: #334155;
    }
    html[data-theme="light"] .sf-calendar-page .fc-day-today {
        background: #fff7ed !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-dot-event,
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-block-event {
        background: #eef2ff !important;
        border-color: #c7d2fe !important;
        color: #1e293b !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-dot-event .fc-event-title,
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-block-event .fc-event-title,
    html[data-theme="light"] .sf-calendar-page .fc-event-main,
    html[data-theme="light"] .sf-calendar-page .fc-event-title,
    html[data-theme="light"] .sf-calendar-page .fc-event-time {
        color: #1e293b !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-daygrid-event-dot {
        border-color: #4f46e5 !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-list {
        border-color: #dbe3ef;
    }
    html[data-theme="light"] .sf-calendar-page .fc-list-day-cushion {
        background: #f8fafc !important;
        color: #0f172a !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-list-event:hover td {
        background: #f8fbff !important;
    }
    html[data-theme="light"] .sf-calendar-page .fc-list-event-title,
    html[data-theme="light"] .sf-calendar-page .fc-list-event-time {
        color: #0f172a;
    }

    @media (max-width: 640px) {
        .garage-calendar {
            min-height: 560px;
        }

        .sf-calendar-page .fc .fc-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .sf-calendar-page .fc .fc-toolbar-title {
            font-size: 1rem;
        }

        .sf-calendar-page .fc .fc-button {
            padding: 0.35rem 0.55rem !important;
            font-size: 0.72rem !important;
        }
    }
</style>

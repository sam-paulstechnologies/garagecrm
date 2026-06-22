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

    .sf-calendar-page .sf-calendar-hero {
        align-items: center;
        border-radius: 1.25rem;
        border-width: 1px;
        display: flex;
        gap: 1.25rem;
        justify-content: space-between;
        padding: 1.5rem 1.75rem;
    }

    .sf-calendar-page .sf-calendar-hero .sf-page-title {
        font-size: 2.25rem;
        font-weight: 950;
        letter-spacing: -0.035em;
        line-height: 1.05;
    }

    .sf-calendar-page .sf-calendar-hero .sf-page-subtitle {
        font-size: 0.96rem;
        font-weight: 700;
        margin-top: 0.6rem;
        max-width: 58rem;
    }

    .sf-calendar-filter-summary {
        align-items: center;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
    }

    .sf-calendar-filters-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sf-calendar-panel-summary {
        cursor: pointer;
    }

    .sf-calendar-filter-pill {
        align-items: center;
        border: 1px solid rgba(249, 115, 22, 0.28);
        background: rgba(249, 115, 22, 0.12);
        border-radius: 999px;
        color: #fed7aa;
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 900;
        min-height: 1.8rem;
        padding: 0.35rem 0.7rem;
    }

    .sf-calendar-bucket-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sf-calendar-bucket-card {
        appearance: none;
        border: 1px solid #334155;
        border-radius: 1.1rem;
        cursor: pointer;
        min-height: 8.35rem;
        padding: 1.25rem;
        text-align: left;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        width: 100%;
    }

    .sf-calendar-bucket-card:hover {
        border-color: rgba(249, 115, 22, 0.45);
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.24);
        transform: translateY(-1px);
    }

    .sf-calendar-bucket-label,
    .sf-calendar-bucket-note {
        display: block;
    }

    .sf-calendar-bucket-label {
        color: #cbd5e1;
        font-size: 0.92rem;
        font-weight: 950;
    }

    .sf-calendar-bucket-count {
        color: #ffffff;
        display: block;
        font-size: 2.45rem;
        font-weight: 950;
        line-height: 1;
        margin-top: 1rem;
    }

    .sf-calendar-bucket-note {
        color: #94a3b8;
        font-size: 0.86rem;
        font-weight: 800;
        margin-top: 0.65rem;
    }

    .sf-calendar-bucket-amber { background: rgba(245, 158, 11, 0.14); }
    .sf-calendar-bucket-green { background: rgba(22, 163, 74, 0.14); }
    .sf-calendar-bucket-red { background: rgba(220, 38, 38, 0.14); }

    .sf-calendar-bucket-amber .sf-calendar-bucket-label { color: #fde68a; }
    .sf-calendar-bucket-green .sf-calendar-bucket-label { color: #bbf7d0; }
    .sf-calendar-bucket-red .sf-calendar-bucket-label { color: #fecaca; }

    .sf-calendar-bucket-amber .sf-calendar-bucket-count { color: #fde68a; }
    .sf-calendar-bucket-green .sf-calendar-bucket-count { color: #bbf7d0; }
    .sf-calendar-bucket-red .sf-calendar-bucket-count { color: #fecaca; }

    .sf-calendar-filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .sf-calendar-filter-field span {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .sf-calendar-filter-field select {
        min-height: 2.65rem;
        width: 100%;
        border: 1px solid #334155;
        border-radius: 0.9rem;
        background: rgba(15, 23, 42, 0.92);
        color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 800;
        padding: 0.5rem 0.85rem;
    }

    .sf-calendar-stat-chip,
    .sf-calendar-legend-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        min-height: 1.8rem;
        padding: 0.35rem 0.7rem;
    }

    .sf-calendar-stat-chip {
        border: 1px solid rgba(249, 115, 22, 0.28);
        background: rgba(249, 115, 22, 0.12);
        color: #fed7aa;
    }

    .sf-calendar-legend-pending { background: rgba(245, 158, 11, 0.18); color: #fde68a; }
    .sf-calendar-legend-confirmed { background: rgba(22, 163, 74, 0.18); color: #bbf7d0; }
    .sf-calendar-legend-reschedule { background: rgba(220, 38, 38, 0.18); color: #fecaca; }

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
        background: rgba(99, 102, 241, 0.22);
        border: 1px solid rgba(129, 140, 248, 0.35);
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

    .sf-calendar-page .fc-event.sf-calendar-event-pending,
    .sf-calendar-page .fc-daygrid-dot-event.sf-calendar-event-pending,
    .sf-calendar-page .fc-daygrid-block-event.sf-calendar-event-pending {
        background: #fef3c7 !important;
        border: 1px solid #f59e0b !important;
        color: #78350f !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-scheduled,
    .sf-calendar-page .fc-daygrid-dot-event.sf-calendar-event-scheduled,
    .sf-calendar-page .fc-daygrid-block-event.sf-calendar-event-scheduled {
        background: #dcfce7 !important;
        border: 1px solid #16a34a !important;
        color: #14532d !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-reschedule_required,
    .sf-calendar-page .fc-daygrid-dot-event.sf-calendar-event-reschedule_required,
    .sf-calendar-page .fc-daygrid-block-event.sf-calendar-event-reschedule_required {
        background: #fee2e2 !important;
        border: 1px solid #dc2626 !important;
        color: #7f1d1d !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-pending .fc-event-main,
    .sf-calendar-page .fc-event.sf-calendar-event-pending .fc-event-title,
    .sf-calendar-page .fc-event.sf-calendar-event-pending .fc-event-time {
        color: #78350f !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-scheduled .fc-event-main,
    .sf-calendar-page .fc-event.sf-calendar-event-scheduled .fc-event-title,
    .sf-calendar-page .fc-event.sf-calendar-event-scheduled .fc-event-time {
        color: #14532d !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-reschedule_required .fc-event-main,
    .sf-calendar-page .fc-event.sf-calendar-event-reschedule_required .fc-event-title,
    .sf-calendar-page .fc-event.sf-calendar-event-reschedule_required .fc-event-time {
        color: #7f1d1d !important;
    }

    .sf-calendar-page .fc-event.sf-calendar-event-pending .fc-daygrid-event-dot { border-color: #b45309 !important; }
    .sf-calendar-page .fc-event.sf-calendar-event-scheduled .fc-daygrid-event-dot { border-color: #15803d !important; }
    .sf-calendar-page .fc-event.sf-calendar-event-reschedule_required .fc-daygrid-event-dot { border-color: #b91c1c !important; }

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

    html[data-theme="light"] .sf-calendar-page .sf-calendar-hero {
        background: #ffffff !important;
        border-color: #dbe3ef !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
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

    html[data-theme="light"] .sf-calendar-filter-field span {
        color: #64748b;
    }

    html[data-theme="light"] .sf-calendar-filter-field select {
        border-color: #cbd5e1;
        background: #ffffff;
        color: #0f172a;
    }

    html[data-theme="light"] .sf-calendar-stat-chip {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #c2410c;
    }

    html[data-theme="light"] .sf-calendar-filter-pill {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #c2410c;
    }

    html[data-theme="light"] .sf-calendar-bucket-card {
        border-color: #dbe3ef;
        background: #ffffff;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
    }

    html[data-theme="light"] .sf-calendar-bucket-card:hover {
        border-color: #fdba74;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    html[data-theme="light"] .sf-calendar-bucket-count { color: #0f172a; }
    html[data-theme="light"] .sf-calendar-bucket-note { color: #64748b; }
    html[data-theme="light"] .sf-calendar-bucket-amber,
    html[data-theme="light"] .sf-calendar-bucket-green,
    html[data-theme="light"] .sf-calendar-bucket-red {
        background: #ffffff;
    }
    html[data-theme="light"] .sf-calendar-bucket-amber .sf-calendar-bucket-label { color: #92400e; }
    html[data-theme="light"] .sf-calendar-bucket-green .sf-calendar-bucket-label { color: #15803d; }
    html[data-theme="light"] .sf-calendar-bucket-red .sf-calendar-bucket-label { color: #b91c1c; }
    html[data-theme="light"] .sf-calendar-bucket-amber .sf-calendar-bucket-count { color: #7c2d12; }
    html[data-theme="light"] .sf-calendar-bucket-green .sf-calendar-bucket-count { color: #166534; }
    html[data-theme="light"] .sf-calendar-bucket-red .sf-calendar-bucket-count { color: #7f1d1d; }

    html[data-theme="light"] .sf-calendar-legend-pending { background: #fffbeb; color: #92400e; }
    html[data-theme="light"] .sf-calendar-legend-confirmed { background: #f0fdf4; color: #15803d; }
    html[data-theme="light"] .sf-calendar-legend-reschedule { background: #fef2f2; color: #b91c1c; }
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

        .sf-calendar-page .sf-calendar-hero,
        .sf-calendar-filter-summary {
            align-items: flex-start;
            flex-direction: column;
        }

        .sf-calendar-page .sf-calendar-hero .sf-page-title {
            font-size: 1.9rem;
        }

        .sf-calendar-filters-grid {
            grid-template-columns: 1fr;
        }

        .sf-calendar-bucket-grid {
            grid-template-columns: 1fr;
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

    @media (min-width: 641px) and (max-width: 1024px) {
        .sf-calendar-filters-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sf-calendar-bucket-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

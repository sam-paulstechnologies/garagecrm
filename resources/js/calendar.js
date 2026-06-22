import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const eventsUrl = calendarEl.dataset.events;
    const filters = document.querySelectorAll('[data-calendar-filter]');
    const bucketButtons = document.querySelectorAll('[data-calendar-bucket]');
    const resetButtons = document.querySelectorAll('[data-calendar-reset]');
    const filterPanelToggle = document.querySelector('[data-calendar-panel-toggle]');
    const filterPanelBody = document.querySelector('[data-calendar-panel-body]');

    const setSelectValue = (name, value) => {
        const filter = document.querySelector(`[data-calendar-filter="${name}"]`);
        if (filter) {
            filter.value = value;
        }
    };

    const currentFilters = () => {
        const values = {
            assigned_user: calendarEl.dataset.initialAssignedUser || 'all',
            status: calendarEl.dataset.initialStatus || 'all',
            slot: calendarEl.dataset.initialSlot || 'all',
        };

        filters.forEach((filter) => {
            values[filter.dataset.calendarFilter] = filter.value || 'all';
        });

        return values;
    };

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin],
        initialView: 'dayGridMonth',
        height: 700,
        editable: false,
        eventStartEditable: false,
        eventDurationEditable: false,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },

        eventClassNames(info) {
            const status = info.event.extendedProps?.status || info.event.extendedProps?.status_label || 'default';
            return [`sf-calendar-event-${String(status).replace(/[^a-z0-9_-]/gi, '_').toLowerCase()}`];
        },

        events(fetchInfo, successCallback, failureCallback) {
            const params = new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
                ...currentFilters(),
            });

            fetch(`${eventsUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Unable to load calendar events');
                    }

                    return response.json();
                })
                .then(successCallback)
                .catch(failureCallback);
        },

        eventClick(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        },
    });

    calendar.render();

    filters.forEach((filter) => {
        filter.addEventListener('change', () => calendar.refetchEvents());
    });

    bucketButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const bucket = button.dataset.calendarBucket || 'all';

            setSelectValue('status', bucket);
            setSelectValue('assigned_user', 'all');
            setSelectValue('slot', 'all');
            calendar.refetchEvents();
        });
    });

    resetButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setSelectValue('status', 'all');
            setSelectValue('assigned_user', 'all');
            setSelectValue('slot', 'all');
            calendar.refetchEvents();
        });
    });

    filterPanelToggle?.addEventListener('click', () => {
        const isHidden = filterPanelBody?.classList.toggle('hidden');
        filterPanelToggle.textContent = isHidden ? 'Show Filters' : 'Hide Filters';
        filterPanelToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
    });
});

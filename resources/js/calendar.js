import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const eventsUrl = calendarEl.dataset.events;
    const filters = document.querySelectorAll('[data-calendar-filter]');
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
});

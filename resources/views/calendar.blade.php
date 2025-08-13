@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Bookings Calendar</h1>
    <div id="calendar"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: @json($bookings),
            eventColor: function(event) {
                switch (event.extendedProps.status) {
                    case 'pending':
                        return '#f6ad55'; // orange
                    case 'confirmed':
                        return '#48bb78'; // green
                    case 'completed':
                        return '#4299e1'; // blue
                    case 'cancelled':
                        return '#f56565'; // red
                    default:
                        return '#a0aec0'; // gray
                }
            },
            eventRender: function(info) {
                var tooltip = new Tooltip(info.el, {
                    title: info.event.title,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            }
        });
        calendar.render();
    });
</script>
@endsection
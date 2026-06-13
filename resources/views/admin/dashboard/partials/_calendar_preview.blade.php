{{-- resources/views/admin/dashboard/partials/_calendar_preview.blade.php --}}

@php
    use Carbon\Carbon;

    $calendarView = request('calendar_view', 'week');

    $calendarEvents = collect($calendarEvents ?? $upcomingBookings ?? $upcomingEvents ?? []);

    $today = Carbon::today();

    $calendarRoute = \Illuminate\Support\Facades\Route::has('admin.calendar.index')
        ? route('admin.calendar.index')
        : (
            \Illuminate\Support\Facades\Route::has('admin.calendar')
                ? route('admin.calendar')
                : null
        );

    /*
    |--------------------------------------------------------------------------
    | Event Date Resolver
    |--------------------------------------------------------------------------
    | Handles both array events from the dashboard controller and model objects.
    */
    $eventDate = function ($event) {
        $date = data_get($event, 'start')
            ?? data_get($event, 'start_time')
            ?? data_get($event, 'scheduled_at')
            ?? data_get($event, 'booking_date')
            ?? data_get($event, 'date')
            ?? null;

        return $date ? Carbon::parse($date) : null;
    };

    $eventsByDate = $calendarEvents
        ->filter(fn ($event) => $eventDate($event))
        ->groupBy(fn ($event) => $eventDate($event)->format('Y-m-d'));

    $weekStart = $today->copy()->startOfWeek(Carbon::SUNDAY);
    $weekEnd = $today->copy()->endOfWeek(Carbon::SATURDAY);

    $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

    $monthStart = $today->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
    $monthDays = collect(range(0, 41))->map(fn ($i) => $monthStart->copy()->addDays($i));

    $weekUrl = request()->fullUrlWithQuery(['calendar_view' => 'week']);
    $monthUrl = request()->fullUrlWithQuery(['calendar_view' => 'month']);

    $weekTotal = $weekDays->sum(function ($day) use ($eventsByDate) {
        return collect($eventsByDate->get($day->format('Y-m-d'), []))->count();
    });

    $monthTotal = $monthDays
        ->filter(fn ($day) => $day->isSameMonth(now()))
        ->sum(function ($day) use ($eventsByDate) {
            return collect($eventsByDate->get($day->format('Y-m-d'), []))->count();
        });
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
    <div class="mb-3 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-white">
                Calendar
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Booking and garage schedule count only.
            </p>
        </div>

        @if ($calendarRoute)
            <a href="{{ $calendarRoute }}" class="text-xs font-black text-orange-400 transition hover:text-orange-300">
                Full Calendar View
            </a>
        @endif
    </div>

    <div class="mb-3 flex items-center justify-between">
        <div>
            <p class="text-lg font-extrabold text-white">
                {{ $calendarView === 'month' ? $today->format('F Y') : 'This Week' }}
            </p>

            <p class="text-xs text-slate-400">
                @if ($calendarView === 'month')
                    {{ $monthTotal }} {{ \Illuminate\Support\Str::plural('booking', $monthTotal) }} this month
                @else
                    {{ $weekStart->format('d M') }} - {{ $weekEnd->format('d M Y') }}
                    <span class="mx-1 text-slate-600">•</span>
                    {{ $weekTotal }} {{ \Illuminate\Support\Str::plural('booking', $weekTotal) }} this week
                @endif
            </p>
        </div>

        <div class="inline-flex rounded-xl border border-slate-800 bg-slate-950/60 p-1">
            <a
                href="{{ $weekUrl }}"
                class="rounded-lg px-3 py-1 text-xs font-bold {{ $calendarView === 'week' ? 'bg-orange-500 text-white' : 'text-slate-500 hover:text-slate-200' }}"
            >
                week
            </a>

            <a
                href="{{ $monthUrl }}"
                class="rounded-lg px-3 py-1 text-xs font-bold {{ $calendarView === 'month' ? 'bg-orange-500 text-white' : 'text-slate-500 hover:text-slate-200' }}"
            >
                month
            </a>
        </div>
    </div>

    @if ($calendarView === 'month')
        <div class="overflow-hidden rounded-2xl border border-slate-800">
            <div class="grid grid-cols-7">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                    <div class="border-b border-r border-slate-800 bg-slate-950/70 px-3 py-2 text-center text-xs font-bold text-slate-400 last:border-r-0">
                        {{ $dayName }}
                    </div>
                @endforeach

                @foreach ($monthDays as $day)
                    @php
                        $dateKey = $day->format('Y-m-d');
                        $dayCount = collect($eventsByDate->get($dateKey, []))->count();
                        $isToday = $day->isSameDay($today);
                        $isCurrentMonth = $day->month === $today->month;
                    @endphp

                    <div
                        class="min-h-[92px] border-b border-r border-slate-800 p-2
                            {{ $isToday ? 'bg-orange-500/10' : 'bg-slate-950/40' }}
                            {{ !$isCurrentMonth ? 'opacity-45' : '' }}"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs font-bold {{ $isToday ? 'text-orange-400' : ($isCurrentMonth ? 'text-slate-300' : 'text-slate-700') }}">
                                {{ $day->format('j') }}
                            </span>

                            @if ($isToday)
                                <span class="rounded-full bg-orange-500 px-2 py-0.5 text-[9px] font-black text-white">
                                    Today
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-center">
                            @if ($dayCount > 0)
                                <div class="flex h-10 min-w-10 items-center justify-center rounded-full bg-orange-500 px-3 text-sm font-black text-white shadow-lg shadow-orange-950/30">
                                    {{ $dayCount }}
                                </div>
                            @else
                                <div class="text-xs font-semibold text-slate-700">
                                    —
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-2.5 md:grid-cols-7">
            @foreach ($weekDays as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $dayCount = collect($eventsByDate->get($dateKey, []))->count();
                    $isToday = $day->isSameDay($today);
                @endphp

                <div class="rounded-xl border {{ $isToday ? 'border-orange-400/50 bg-orange-500/10' : 'border-slate-800 bg-slate-950/50' }} p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold {{ $isToday ? 'text-orange-400' : 'text-slate-300' }}">
                                {{ $day->format('D') }}
                            </p>

                            <p class="mt-1 text-lg font-extrabold {{ $isToday ? 'text-white' : 'text-slate-100' }}">
                                {{ $day->format('d') }}
                            </p>
                        </div>

                        @if ($isToday)
                            <span class="rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                Today
                            </span>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-3 text-center">
                        @if ($dayCount > 0)
                            <p class="text-3xl font-black text-white">
                                {{ $dayCount }}
                            </p>

                            <p class="mt-1 text-[11px] font-semibold text-slate-500">
                                {{ \Illuminate\Support\Str::plural('booking', $dayCount) }}
                            </p>
                        @else
                            <p class="text-3xl font-black text-slate-600">
                                —
                            </p>

                            <p class="mt-1 text-[11px] font-semibold text-slate-600">
                                no bookings
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

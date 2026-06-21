<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Models\User;
use App\Services\Calendar\CalendarEventBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) Auth::user()->company_id;

        return view('admin.calendar.index', [
            'calendarFilters' => $this->filterState($request),
            'calendarAssignedUsers' => User::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'calendarStatuses' => $this->statusOptions(),
            'calendarSlots' => [
                'all' => 'All slots',
                'morning' => 'Morning',
                'afternoon' => 'Afternoon',
                'evening' => 'Evening',
                'full_day' => 'Full day',
            ],
        ]);
    }

    public function events(Request $request, CalendarEventBuilder $builder)
    {
        [$start, $end] = $this->dateRange($request);

        return response()->json(
            $builder->build(
                companyId: (int) Auth::user()->company_id,
                start: $start,
                end: $end,
                filters: $this->filterState($request),
            )
        );
    }

    private function dateRange(Request $request): array
    {
        try {
            $start = $request->filled('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : now()->startOfMonth()->startOfDay();
        } catch (\Throwable) {
            $start = now()->startOfMonth()->startOfDay();
        }

        try {
            $end = $request->filled('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : $start->copy()->endOfMonth()->endOfDay();
        } catch (\Throwable) {
            $end = $start->copy()->endOfMonth()->endOfDay();
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addMonth()->endOfDay();
        }

        return [$start, $end];
    }

    private function filterState(Request $request): array
    {
        return [
            'assigned_user' => $request->query('assigned_user', 'all'),
            'status' => $request->query('status', 'all'),
            'slot' => $request->query('slot', 'all'),
        ];
    }

    private function statusOptions(): array
    {
        return [
            'all' => 'All booking calendar items',
            Booking::STATUS_PENDING => 'Manager Confirmation',
            Booking::STATUS_SCHEDULED => 'Booking Confirmed',
            Booking::STATUS_RESCHEDULE_REQUIRED => 'Rescheduling Required',
        ];
    }
}

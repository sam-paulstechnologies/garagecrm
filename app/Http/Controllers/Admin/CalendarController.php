<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * 📅 Calendar UI page
     */
    public function index()
    {
        // Just render the calendar view
        return view('admin.calendar.index');
    }

    /**
     * 📡 JSON feed for FullCalendar
     */
    public function events(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $bookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('is_archived')
                  ->orWhere('is_archived', false);
            })
            ->get();

        $events = $bookings->map(function ($b) {
            $start = $this->resolveStart($b);
            if (!$start) {
                return null;
            }

            $end = $this->resolveEnd($b, $start);

            $titleParts = [
                $b->client->name ?? 'Client',
                $b->service_type ?: ($b->name ?? 'Booking'),
            ];

            $url = RouteFacade::has('admin.bookings.show')
                ? route('admin.bookings.show', $b->id)
                : url('/admin/bookings/' . $b->id);

            return [
                'id'              => $b->id,
                'title'           => implode(' - ', array_filter($titleParts)),
                'start'           => $start->toIso8601String(),
                'end'             => $end?->toIso8601String(),
                'url'             => $url,
                'backgroundColor' => $this->statusColor($b->status),
                'borderColor'     => $this->statusColor($b->status),
                'textColor'       => '#ffffff',
            ];
        })->filter()->values();

        return response()->json($events);
    }

    /* ================= Helpers ================= */

    private function resolveStart($b): ?Carbon
    {
        if (!empty($b->scheduled_at)) {
            return Carbon::parse($b->scheduled_at);
        }

        $date = $b->booking_date ?? null;
        if (!$date) {
            return null;
        }

        $start = Carbon::parse($date);

        $slot = strtolower((string) $b->slot);
        if (str_contains($slot, 'morning'))   return $start->setTime(9, 0);
        if (str_contains($slot, 'afternoon')) return $start->setTime(13, 0);
        if (str_contains($slot, 'evening'))   return $start->setTime(16, 0);

        return $start->setTime(10, 0);
    }

    private function resolveEnd($b, Carbon $start): Carbon
    {
        if (!empty($b->scheduled_end_at)) {
            return Carbon::parse($b->scheduled_end_at);
        }

        $hours = is_numeric($b->expected_duration)
            ? max((int) $b->expected_duration, 1)
            : 2;

        return $start->copy()->addHours($hours);
    }

    private function statusColor($status): string
    {
        return match (strtolower((string) $status)) {
            Booking::STATUS_CONFIRMED => '#22c55e',
            Booking::STATUS_PENDING => '#f59e0b',
            Booking::STATUS_SCHEDULED => '#6366f1',
            Booking::STATUS_VEHICLE_RECEIVED => '#8b5cf6',
            Booking::STATUS_COMPLETED => '#0ea5e9',
            Booking::STATUS_CANCELED => '#ef4444',
            default => '#6b7280',
        };
    }
}
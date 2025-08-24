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
     * JSON feed used by dashboard calendar.
     */
    public function events(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $bookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                // show active by default; remove this if you want everything
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->get();

        $events = $bookings->map(function ($b) {
            $start = $this->resolveStart($b);
            if (!$start) return null; // skip items without a determinable start

            $end = $this->resolveEnd($b, $start);

            $titleParts = [
                $b->client->name ?? 'Client',
                $b->service_type ?: ($b->name ?? 'Booking'),
            ];

            // Prefer your named route if it exists
            $url = RouteFacade::has('admin.bookings.show')
                ? route('admin.bookings.show', $b->id)
                : url('/admin/bookings/'.$b->id);

            return [
                'id'    => $b->id,
                'title' => implode(' - ', array_filter($titleParts)),
                'start' => $start->toIso8601String(),
                'end'   => $end?->toIso8601String(),
                'url'   => $url,
                'backgroundColor' => $this->statusColor($b->status),
                'borderColor'     => $this->statusColor($b->status),
                'textColor'       => '#ffffff',
            ];
        })->filter()->values();

        return response()->json($events);
    }

    private function resolveStart($b): ?Carbon
    {
        // 1) exact datetime
        if (!empty($b->scheduled_at)) {
            return Carbon::parse($b->scheduled_at);
        }

        // 2) date-only fields
        $date = $b->booking_date ?? $b->date ?? null;
        if (!$date) return null;

        $start = Carbon::parse($date);

        // 3) use slot hints, else default 10:00
        $slot = strtolower((string)($b->slot ?? ''));
        if (str_contains($slot, 'morning'))   return $start->copy()->setTime(9, 0);
        if (str_contains($slot, 'afternoon')) return $start->copy()->setTime(13, 0);
        if (str_contains($slot, 'evening'))   return $start->copy()->setTime(16, 0);

        return $start->setTime(10, 0);
    }

    private function resolveEnd($b, Carbon $start): ?Carbon
    {
        // use scheduled_end_at if provided, else duration, else +2h
        if (!empty($b->scheduled_end_at)) {
            return Carbon::parse($b->scheduled_end_at);
        }
        $hours = is_numeric($b->expected_duration) ? max((int)$b->expected_duration, 1) : 2;
        return $start->copy()->addHours($hours);
    }

    private function statusColor($status): string
    {
        $status = strtolower((string)$status);
        return match ($status) {
            'confirmed'                          => '#22c55e', // green
            'pending', 'awaiting', 'attempting contact' => '#f59e0b', // amber
            'completed', 'done', 'closed'        => '#0ea5e9', // sky
            'cancelled', 'rejected'              => '#ef4444', // red
            default                              => '#6b7280', // gray
        };
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route as RouteFacade;
use Carbon\Carbon;

class BookingSummaryResource extends JsonResource
{
    public function toArray($request)
    {
        $tz  = 'Asia/Dubai';
        $iso = static function ($v) use ($tz) {
            if (!$v) return null;
            try { return Carbon::parse($v)->setTimezone($tz)->toIso8601String(); }
            catch (\Throwable) { return null; }
        };

        $client   = $this->whenLoaded('client');
        $opp      = $this->whenLoaded('opportunity');
        $lead     = $opp?->lead ?? null;
        $vehicle  = $this->whenLoaded('vehicleData');
        $assignee = $this->whenLoaded('assignedUser');

        $date = $this->booking_date ?: null;

        $mk = $vehicle?->make?->name ?? null;
        $md = $vehicle?->model?->name ?? null;

        $human = trim(implode(' | ', array_filter([
            $this->status ? ucfirst((string) $this->status).' booking' : null,
            $date ? Carbon::parse($date)->format('D, d M Y') : null,
            $this->slot ? ucfirst(str_replace('_',' ', (string) $this->slot)) : null,
            ($mk && $md) ? ($mk.' '.$md) : null,
            $client?->name,
        ])));

        $bookingUrl = null;
        try {
            $bookingUrl = RouteFacade::has('admin.bookings.show')
                ? route('admin.bookings.show', $this->id)
                : url("/admin/bookings/{$this->id}");
        } catch (\Throwable) {
            $bookingUrl = url("/admin/bookings/{$this->id}");
        }

        $lastComms = is_iterable($this->lastComms ?? []) ? $this->lastComms : [];

        return [
            'booking_id' => $this->id,
            'state'      => $this->status,
            'state_timeline' => [
                'confirmed_at'     => $iso($this->confirmed_at ?? null),
                'completed_at'     => $iso($this->completed_at ?? null),
                'cancelled_at'     => $iso($this->cancelled_at ?? $this->canceled_at ?? null),
                'state_changed_at' => $iso($this->state_changed_at ?? null),
            ],
            'window' => [
                'date'       => $date,
                'slot_label' => $this->slot ?? null,
            ],
            'client' => $client ? [
                'id'    => $client->id,
                'name'  => $client->name,
                'phone' => $client->phone,
            ] : null,
            'lead' => $lead ? [
                'id'      => $lead->id,
                'source'  => $lead->source,
                'channel' => $lead->preferred_channel,
                'score'   => $lead->score,
            ] : null,
            'opportunity' => $opp ? [
                'id'    => $opp->id,
                'title' => $opp->title,
                'stage' => $opp->stage,
                'value' => $opp->value,
            ] : null,
            'vehicle' => $vehicle ? [
                'id'    => $vehicle->id,
                'make'  => $mk,
                'model' => $md,
                'plate' => $vehicle->plate_number ?? null,
                'vin'   => $vehicle->vin ?? null,
            ] : null,
            'assigned' => $assignee ? [
                'user_id' => $assignee->id,
                'name'    => $assignee->name,
            ] : null,
            'notes'         => $this->notes ?? null,
            'human_summary' => $human ?: null,
            'links' => [
                'booking_url'    => $bookingUrl,
                'reschedule_api' => url("/api/v1/bookings/{$this->id}/transition"),
            ],
            'last_comms' => collect($lastComms)->map(function ($r) use ($tz) {
                $arr = (array) $r;
                $at  = $arr['at'] ?? $arr['communication_date'] ?? $arr['created_at'] ?? null;
                return [
                    'channel'   => $arr['channel'] ?? $arr['type'] ?? null,
                    'direction' => $arr['direction'] ?? null,
                    'template'  => $arr['template'] ?? null,
                    'body'      => $arr['body'] ?? $arr['content'] ?? null,
                    'at'        => $at ? Carbon::parse($at)->setTimezone($tz)->toIso8601String() : null,
                ];
            })->values(),
        ];
    }
}

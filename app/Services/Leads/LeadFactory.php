<?php

namespace App\Services\Leads;

use App\Models\Client\Lead;
use App\Models\LeadDuplicate;
use Illuminate\Support\Facades\DB;

class LeadFactory
{
    public function createOrDetectDuplicate(array $data): Lead|LeadDuplicate
    {
        $companyId = $data['company_id'];

        $emailNorm = Lead::normalizeEmail($data['email'] ?? null);
        $phoneNorm = Lead::normalizePhone($data['phone'] ?? null);

        $windowDays = (int) ($data['window_days'] ?? 30);
        $sinceDate  = now()->subDays($windowDays);

        $match = Lead::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $sinceDate)
            ->where(function ($q) use ($emailNorm, $phoneNorm) {
                if ($emailNorm) $q->orWhere('email_norm', $emailNorm);
                if ($phoneNorm) $q->orWhere('phone_norm', $phoneNorm);
            })
            ->orderBy('created_at')
            ->first();

        if ($match) {
            return LeadDuplicate::create([
                'company_id'       => $companyId,
                'primary_lead_id'  => $match->id,
                'external_source'  => $data['external_source'] ?? null,
                'external_id'      => $data['external_id'] ?? null,
                'external_form_id' => $data['external_form_id'] ?? null,
                'name'             => $data['name'] ?? null,
                'email'            => $data['email'] ?? null,
                'email_norm'       => $emailNorm,
                'phone'            => $data['phone'] ?? null,
                'phone_norm'       => $phoneNorm,
                'matched_on'       => ($emailNorm && $phoneNorm) ? 'both' : ($emailNorm ? 'email' : 'phone'),
                'window_days'      => $windowDays,
                'reason'           => "duplicate within {$windowDays} days of lead #{$match->id}",
                'payload'          => $data['external_payload'] ?? null,
                'detected_at'      => now(),
            ]);
        }

        return DB::transaction(fn () => Lead::create([
            ...$data,
            'email_norm' => $emailNorm,
            'phone_norm' => $phoneNorm,
            'status'     => $data['status'] ?? 'new',
        ]));
    }
}

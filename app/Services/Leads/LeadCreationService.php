<?php

namespace App\Services\Leads;

use App\Models\Client\Lead;
use App\Models\LeadDuplicate;
use Illuminate\Support\Facades\DB;

class LeadCreationService
{
    public function create(array $data, int $companyId): Lead
    {
        return DB::transaction(function () use ($data, $companyId) {

            $resolver = app(LeadClientResolver::class);
            $client   = $resolver->resolve($data, $companyId);

            $emailNorm = Lead::normalizeEmail($data['email'] ?? null);
            $phoneNorm = Lead::normalizePhone($data['phone'] ?? null);

            /*
            |--------------------------------------------------------------------------
            | 🔴 HARD DUPLICATE PREVENTION (NEW)
            |--------------------------------------------------------------------------
            */
            $existingLead = Lead::where('company_id', $companyId)
                ->where(function ($q) use ($emailNorm, $phoneNorm) {
                    if ($emailNorm) $q->orWhere('email_norm', $emailNorm);
                    if ($phoneNorm) $q->orWhere('phone_norm', $phoneNorm);
                })
                ->first();

            if ($existingLead) {

                // Optional logging
                LeadDuplicate::create([
                    'company_id'      => $companyId,
                    'primary_lead_id' => $existingLead->id,
                    'name'            => $data['name'] ?? null,
                    'email'           => $data['email'] ?? null,
                    'email_norm'      => $emailNorm,
                    'phone'           => $data['phone'] ?? null,
                    'phone_norm'      => $phoneNorm,
                    'matched_on'      => $emailNorm && $phoneNorm
                        ? 'both'
                        : ($emailNorm ? 'email' : 'phone'),
                    'reason'          => 'Duplicate lead blocked',
                ]);

                return $existingLead; // 🔥 STOP DUPLICATE
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE NEW LEAD
            |--------------------------------------------------------------------------
            */
            return Lead::create([
                'company_id'        => $companyId,
                'client_id'         => $client->id,
                'name'              => $data['name'],
                'email'             => $data['email'] ?? null,
                'phone'             => $data['phone'] ?? null,
                'email_norm'        => $emailNorm,
                'phone_norm'        => $phoneNorm,
                'status'            => $data['status'] ?? 'new',
                'source'            => $data['source'] ?? 'manual',
                'notes'             => $data['notes'] ?? null,
                'assigned_to'       => $data['assigned_to'] ?? null,
                'preferred_channel' => $data['preferred_channel'] ?? 'phone',
                'is_hot'            => $data['is_hot'] ?? false,
            ]);
        });
    }
}
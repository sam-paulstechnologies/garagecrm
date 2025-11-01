<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    /**
     * Ensure there's a Client for the Lead and one Opportunity created.
     */
    public function ensureClientAndOpportunity(int $leadId): void
    {
        $lead = Lead::find($leadId);
        if (!$lead) return;

        DB::transaction(function () use ($lead) {
            // 1) Ensure Client
            if (!$lead->client_id) {
                $client = Client::query()
                    ->where('company_id', $lead->company_id)
                    ->when($lead->email_norm, fn ($q) => $q->orWhere('email_norm', $lead->email_norm))
                    ->when($lead->phone_norm, fn ($q) => $q->orWhere('phone_norm', $lead->phone_norm))
                    ->first();

                if (!$client) {
                    $client = Client::create([
                        'company_id' => $lead->company_id,
                        'name'       => $lead->name,
                        'phone'      => $lead->phone,
                        'phone_norm' => $lead->phone_norm,
                        'email'      => $lead->email,
                        'email_norm' => $lead->email_norm,
                    ]);
                }

                $lead->client_id = $client->id;
                $lead->status    = 'converted';
                $lead->save();
            }

            // 2) Ensure a single Opportunity exists
            if (!$lead->opportunity()->exists()) {
                Opportunity::create([
                    'client_id'        => $lead->client_id,
                    'lead_id'          => $lead->id,
                    'company_id'       => $lead->company_id,
                    'title'            => ($lead->name ?: 'Lead') . ' Opportunity',
                    'stage'            => 'new',
                    'assigned_to'      => $lead->assigned_to,
                    'source'           => $lead->source,
                    'notes'            => $lead->notes,
                    'vehicle_make_id'  => $lead->vehicle_make_id,
                    'vehicle_model_id' => $lead->vehicle_model_id,
                    'other_make'       => $lead->other_make,
                    'other_model'      => $lead->other_model,
                ]);
            }
        });
    }
}

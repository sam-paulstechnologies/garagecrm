<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadConversionService
{
    public function ensureClientAndOpportunity(int $leadId): void
    {
        $lead = Lead::find($leadId);

        if (!$lead) {
            return;
        }

        DB::transaction(function () use ($lead) {

            $lead->refresh();

            /*
            |--------------------------------------------------------------------------
            | 1. Ensure Client
            |--------------------------------------------------------------------------
            */

            if (!$lead->client_id) {

                $emailNorm = Lead::normalizeEmail($lead->email);
                $phoneNorm = Lead::normalizePhone($lead->phone);

                $client = Client::query()
                    ->where('company_id', $lead->company_id)
                    ->where(function ($q) use ($emailNorm, $phoneNorm) {
                        if ($phoneNorm) {
                            $q->orWhere('phone_norm', $phoneNorm)
                              ->orWhere('phone', $phoneNorm)
                              ->orWhere('whatsapp', $phoneNorm);
                        }

                        if ($emailNorm) {
                            $q->orWhere('email_norm', $emailNorm)
                              ->orWhere('email', $emailNorm);
                        }
                    })
                    ->first();

                if (!$client) {
                    $client = Client::create([
                        'company_id'        => $lead->company_id,
                        'name'              => $lead->name ?: 'Customer',
                        'phone'             => $phoneNorm,
                        'phone_norm'        => $phoneNorm,
                        'whatsapp'          => $phoneNorm,
                        'email'             => $emailNorm,
                        'email_norm'        => $emailNorm,
                        'source'            => $lead->source,
                        'preferred_channel' => $lead->preferred_channel ?: 'whatsapp',
                        'status'            => 'active',
                    ]);

                    Log::info('[LeadConversionService] Client created from lead', [
                        'lead_id'   => $lead->id,
                        'client_id' => $client->id,
                    ]);
                } else {
                    $updates = [];

                    if (!$client->phone && $phoneNorm) {
                        $updates['phone'] = $phoneNorm;
                    }

                    if (!$client->phone_norm && $phoneNorm) {
                        $updates['phone_norm'] = $phoneNorm;
                    }

                    if (!$client->whatsapp && $phoneNorm) {
                        $updates['whatsapp'] = $phoneNorm;
                    }

                    if (!$client->email && $emailNorm) {
                        $updates['email'] = $emailNorm;
                    }

                    if (!$client->email_norm && $emailNorm) {
                        $updates['email_norm'] = $emailNorm;
                    }

                    if (!$client->source && $lead->source) {
                        $updates['source'] = $lead->source;
                    }

                    if (!$client->preferred_channel) {
                        $updates['preferred_channel'] = $lead->preferred_channel ?: 'whatsapp';
                    }

                    if (!empty($updates)) {
                        $client->update($updates);
                    }

                    Log::info('[LeadConversionService] Existing client reused for lead', [
                        'lead_id'   => $lead->id,
                        'client_id' => $client->id,
                    ]);
                }

                $lead->client_id = $client->id;

                /*
                |--------------------------------------------------------------------------
                | Keep lead active
                |--------------------------------------------------------------------------
                | Do NOT mark this as converted here.
                | converted is treated as a closed lead by LeadResolver.
                */

                if (in_array($lead->status, [null, '', Lead::STATUS_NEW], true)) {
                    $lead->status = Lead::STATUS_ATTEMPTING;
                }

                if (!$lead->preferred_channel) {
                    $lead->preferred_channel = 'whatsapp';
                }

                $lead->save();
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Ensure Single Opportunity
            |--------------------------------------------------------------------------
            */

            $existingOpportunity = Opportunity::query()
                ->where('company_id', $lead->company_id)
                ->where('lead_id', $lead->id)
                ->first();

            $memory = $lead->conversation_data ?? [];

            if (!is_array($memory)) {
                $memory = [];
            }

            $serviceType = $memory['service_type'] ?? null;

            if ($existingOpportunity) {
                $updates = [];

                if (!$existingOpportunity->service_type && $serviceType) {
                    $updates['service_type'] = $serviceType;
                }

                if (!$existingOpportunity->vehicle_make_id && $lead->vehicle_make_id) {
                    $updates['vehicle_make_id'] = $lead->vehicle_make_id;
                }

                if (!$existingOpportunity->vehicle_model_id && $lead->vehicle_model_id) {
                    $updates['vehicle_model_id'] = $lead->vehicle_model_id;
                }

                if (!$existingOpportunity->other_make && $lead->other_make) {
                    $updates['other_make'] = $lead->other_make;
                }

                if (!$existingOpportunity->other_model && $lead->other_model) {
                    $updates['other_model'] = $lead->other_model;
                }

                if (!empty($updates)) {
                    $existingOpportunity->update($updates);
                }

                return;
            }

            Opportunity::create([
                'client_id'        => $lead->client_id,
                'lead_id'          => $lead->id,
                'company_id'       => $lead->company_id,
                'title'            => ($lead->name ?: 'Lead') . ' Opportunity',
                'stage'            => Opportunity::STAGE_NEW,
                'assigned_to'      => $lead->assigned_to,
                'source'           => $lead->source,
                'notes'            => $lead->notes,
                'service_type'     => $serviceType,
                'vehicle_make_id'  => $lead->vehicle_make_id,
                'vehicle_model_id' => $lead->vehicle_model_id,
                'other_make'       => $lead->other_make,
                'other_model'      => $lead->other_model,
            ]);

            Log::info('[LeadConversionService] Opportunity created from lead', [
                'lead_id'   => $lead->id,
                'client_id' => $lead->client_id,
            ]);
        });
    }
}
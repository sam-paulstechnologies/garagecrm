<?php

namespace App\Services\Lead;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function createOrResolve(array $data): Lead
    {
        return DB::transaction(function () use ($data) {

            // 🔹 Normalize using Lead model (single source of truth)
            $phone = Lead::normalizePhone($data['phone'] ?? null);
            $email = Lead::normalizeEmail($data['email'] ?? null);

            if (!$phone && !$email) {
                throw new \Exception("Lead must have phone or email");
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 1: FIND EXISTING CLIENT (STRICT + COMPANY SAFE)
            |--------------------------------------------------------------------------
            */

            $client = Client::query()
                ->where('company_id', $data['company_id'])
                ->when($phone, fn($q) =>
                    $q->orWhere('phone', $phone)
                )
                ->when($email, fn($q) =>
                    $q->orWhere('email', $email)
                )
                ->first();

            /*
            |--------------------------------------------------------------------------
            | STEP 2: CREATE CLIENT IF NOT EXISTS
            |--------------------------------------------------------------------------
            */

            if (!$client) {
                $client = Client::create([
                    'company_id' => $data['company_id'],
                    'name'       => $data['name'] ?? 'Customer',
                    'phone'      => $phone,
                    'email'      => $email,
                ]);

                Log::info('New Client Created', [
                    'client_id' => $client->id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 3: PREVENT DUPLICATE ACTIVE LEAD (🔥 FIXED)
            |--------------------------------------------------------------------------
            */

            $existingLead = Lead::where('client_id', $client->id)
                ->where('company_id', $data['company_id'])
                ->where('is_active', true) // 🔥 USE THIS (VERY IMPORTANT)
                ->latest()
                ->first();

            if ($existingLead) {
                Log::info('Duplicate Lead Prevented', [
                    'lead_id' => $existingLead->id
                ]);

                return $existingLead;
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 4: CREATE NEW LEAD
            |--------------------------------------------------------------------------
            */

            $lead = Lead::create([
                'company_id'        => $data['company_id'],
                'client_id'         => $client->id,
                'name'              => $data['name'] ?? $client->name,
                'phone'             => $phone,
                'email'             => $email,
                'source'            => $data['source'] ?? 'unknown',
                'external_source'   => $data['external_source'] ?? null,
                'conversation_state'=> 'idle',
                'status'            => Lead::STATUS_NEW, // 🔥 CONSTANT
            ]);

            Log::info('New Lead Created', [
                'lead_id' => $lead->id
            ]);

            return $lead;
        });
    }
}
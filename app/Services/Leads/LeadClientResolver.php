<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadClientResolver
{
    public function resolve(array $data, int $companyId): Client
    {
        $emailNorm = Lead::normalizeEmail($data['email'] ?? null);
        $phoneNorm = Lead::normalizePhone(
            $data['phone'] ?? $data['phone_norm'] ?? $data['whatsapp'] ?? null
        );

        return DB::transaction(function () use ($companyId, $emailNorm, $phoneNorm, $data) {

            /*
            |--------------------------------------------------------------------------
            | 1. Find Existing Client Within Company
            |--------------------------------------------------------------------------
            */

            $client = $this->findExistingClient($companyId, $phoneNorm, $emailNorm);

            /*
            |--------------------------------------------------------------------------
            | 2. Existing Client → Patch Missing Details Only
            |--------------------------------------------------------------------------
            */

            if ($client) {
                $updates = [];

                if (!$client->name && !empty($data['name'])) {
                    $updates['name'] = $data['name'];
                }

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

                if (!$client->source && !empty($data['source'])) {
                    $updates['source'] = strtolower(trim((string) $data['source']));
                }

                if (!$client->status) {
                    $updates['status'] = 'active';
                }

                if (!$client->preferred_channel) {
                    $updates['preferred_channel'] = $phoneNorm ? 'whatsapp' : 'phone';
                }

                if (!empty($updates)) {
                    $client->update($updates);

                    Log::info('[LeadClientResolver] Existing client patched', [
                        'client_id' => $client->id,
                        'company_id' => $companyId,
                        'updated_fields' => array_keys($updates),
                    ]);
                }

                return $client;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Create New Client
            |--------------------------------------------------------------------------
            */

            $client = Client::create([
                'company_id'        => $companyId,
                'name'              => $data['name'] ?? 'Unknown',
                'email'             => $emailNorm,
                'email_norm'        => $emailNorm,
                'phone'             => $phoneNorm,
                'phone_norm'        => $phoneNorm,
                'whatsapp'          => $phoneNorm,
                'source'            => strtolower(trim((string) ($data['source'] ?? 'lead'))),
                'status'            => 'active',
                'preferred_channel' => $phoneNorm ? 'whatsapp' : 'phone',
            ]);

            Log::info('[LeadClientResolver] New client created', [
                'client_id' => $client->id,
                'company_id' => $companyId,
                'phone_norm' => $phoneNorm,
                'email_norm' => $emailNorm,
            ]);

            return $client;
        });
    }

    protected function findExistingClient(
        int $companyId,
        ?string $phoneNorm,
        ?string $emailNorm
    ): ?Client {
        if (!$phoneNorm && !$emailNorm) {
            return null;
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($phoneNorm, $emailNorm) {
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
            ->latest()
            ->first();
    }
}
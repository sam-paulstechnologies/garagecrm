<?php

namespace App\Services\Meta;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaLeadService
{
    public function fetchLeads(string $accessToken, string $formId, int $limit = 50, ?string $graphVersion = null): array
    {
        $graphVersion = $graphVersion ?: config('services.meta.graph_version', 'v19.0');

        $resp = Http::withToken($accessToken)->get("https://graph.facebook.com/{$graphVersion}/{$formId}/leads", [
            'limit'  => max(1, min($limit, 200)),
            'fields' => 'id,created_time,field_data',
        ]);

        if ($resp->failed()) {
            throw new \RuntimeException("Meta API error ({$resp->status()}): ".$resp->body());
        }

        $rows = [];
        foreach (($resp->json('data') ?? []) as $lead) {
            $rows[] = $this->mapLead($lead);
        }
        return $rows;
    }

    /** NEW: fetch all pages; stop when created_time < $since (ISO 8601 string) */
    public function fetchLeadsSince(string $accessToken, string $formId, ?string $sinceIso, int $perPage = 100, ?string $graphVersion = null): array
    {
        $graphVersion = $graphVersion ?: config('services.meta.graph_version', 'v19.0');
        $sinceTs = $sinceIso ? strtotime($sinceIso) : null;

        $after = null;
        $out = [];

        do {
            $query = [
                'limit'  => max(1, min($perPage, 200)),
                'fields' => 'id,created_time,field_data',
            ];
            if ($after) $query['after'] = $after;

            $resp = Http::withToken($accessToken)->get("https://graph.facebook.com/{$graphVersion}/{$formId}/leads", $query);
            if ($resp->failed()) {
                throw new \RuntimeException("Meta API error ({$resp->status()}): ".$resp->body());
            }

            $data = $resp->json('data') ?? [];
            foreach ($data as $lead) {
                $mapped = $this->mapLead($lead);
                $createdTs = isset($mapped['created_time']) ? strtotime($mapped['created_time']) : null;

                if ($sinceTs && $createdTs && $createdTs < $sinceTs) {
                    // assuming descending order; stop scanning older pages
                    return $out;
                }
                $out[] = $mapped;
            }

            $after = $resp->json('paging.cursors.after') ?? null;
        } while ($after);

        return $out;
    }

    /** NEW: fetch one lead by id */
    public function fetchLeadById(string $accessToken, string $leadgenId, ?string $graphVersion = null): ?array
    {
        $graphVersion = $graphVersion ?: config('services.meta.graph_version', 'v19.0');

        $resp = Http::withToken($accessToken)->get("https://graph.facebook.com/{$graphVersion}/{$leadgenId}", [
            'fields' => 'id,created_time,field_data',
        ]);

        if ($resp->failed()) return null;

        $lead = $resp->json();
        return $this->mapLead($lead);
    }

    private function mapLead(array $lead): array
    {
        $fieldData = $lead['field_data'] ?? [];
        $flat = $this->flattenFieldData($fieldData);

        $name = $flat['full_name']
            ?? trim(($flat['first_name'] ?? '').' '.($flat['last_name'] ?? ''))
            ?: ($flat['name'] ?? null);

        $email = $flat['email'] ?? $flat['work_email'] ?? null;
        $phone = $flat['phone_number'] ?? $flat['mobile_number'] ?? $flat['phone'] ?? null;

        if (!$name && $email) $name = Str::before($email, '@');

        return [
            'external_id'  => $lead['id'] ?? null,
            'created_time' => $lead['created_time'] ?? null,
            'name'         => $name ?: 'Meta Lead',
            'email'        => $email,
            'phone'        => $phone,
            'raw'          => $lead,
        ];
    }

    /** Convert Meta's field_data array into a flat [name => value] map. */
    private function flattenFieldData(array $fieldData): array
    {
        $out = [];
        foreach ($fieldData as $row) {
            $key = isset($row['name']) ? Str::of($row['name'])->lower()->toString() : null;
            if (!$key) continue;

            $val = null;
            if (isset($row['values']) && is_array($row['values']) && count($row['values'])) {
                $val = $row['values'][0];
            } elseif (isset($row['value'])) {
                $val = $row['value'];
            }
            if ($val !== null && $val !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }
}

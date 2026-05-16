<?php

namespace App\Services\Meta;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaLeadService
{
    private function metaConfig(string $key, mixed $default = null): mixed
    {
        return config("services.meta_leads.{$key}")
            ?? config("services.meta.{$key}")
            ?? $default;
    }

    private function graphVersion(?string $graphVersion = null): string
    {
        return trim((string) ($graphVersion ?: $this->metaConfig('graph_version', 'v20.0')), '/');
    }

    private function graphBase(?string $graphVersion = null): string
    {
        $base = rtrim((string) $this->metaConfig('graph_base', 'https://graph.facebook.com'), '/');

        return "{$base}/{$this->graphVersion($graphVersion)}";
    }

    public function fetchLeads(string $accessToken, string $formId, int $limit = 50, ?string $graphVersion = null): array
    {
        $resp = Http::withToken($accessToken)
            ->timeout(20)
            ->get("{$this->graphBase($graphVersion)}/{$formId}/leads", [
                'limit'  => max(1, min($limit, 200)),
                'fields' => 'id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform',
            ]);

        if ($resp->failed()) {
            throw new \RuntimeException("Meta API error ({$resp->status()}): " . $resp->body());
        }

        $rows = [];

        foreach (($resp->json('data') ?? []) as $lead) {
            $rows[] = $this->mapLead($lead);
        }

        return $rows;
    }

    public function fetchLeadsSince(string $accessToken, string $formId, ?string $sinceIso, int $perPage = 100, ?string $graphVersion = null): array
    {
        $sinceTs = $sinceIso ? strtotime($sinceIso) : null;

        $after = null;
        $out = [];

        do {
            $query = [
                'limit'  => max(1, min($perPage, 200)),
                'fields' => 'id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform',
            ];

            if ($after) {
                $query['after'] = $after;
            }

            $resp = Http::withToken($accessToken)
                ->timeout(20)
                ->get("{$this->graphBase($graphVersion)}/{$formId}/leads", $query);

            if ($resp->failed()) {
                throw new \RuntimeException("Meta API error ({$resp->status()}): " . $resp->body());
            }

            $data = $resp->json('data') ?? [];

            foreach ($data as $lead) {
                $mapped = $this->mapLead($lead);
                $createdTs = isset($mapped['created_time']) ? strtotime($mapped['created_time']) : null;

                if ($sinceTs && $createdTs && $createdTs < $sinceTs) {
                    return $out;
                }

                $out[] = $mapped;
            }

            $after = $resp->json('paging.cursors.after') ?? null;
        } while ($after);

        return $out;
    }

    public function fetchLeadById(string $accessToken, string $leadgenId, ?string $graphVersion = null): ?array
    {
        $resp = Http::withToken($accessToken)
            ->timeout(20)
            ->get("{$this->graphBase($graphVersion)}/{$leadgenId}", [
                'fields' => 'id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform',
            ]);

        if ($resp->failed()) {
            throw new \RuntimeException("Meta API error ({$resp->status()}): " . $resp->body());
        }

        $lead = $resp->json();

        if (! is_array($lead) || empty($lead['id'])) {
            return null;
        }

        return $this->mapLead($lead);
    }

    private function mapLead(array $lead): array
    {
        $fieldData = $lead['field_data'] ?? [];
        $flat = $this->flattenFieldData($fieldData);

        $name = $flat['full_name']
            ?? trim(($flat['first_name'] ?? '') . ' ' . ($flat['last_name'] ?? ''))
            ?: ($flat['name'] ?? null);

        $email = $flat['email'] ?? $flat['work_email'] ?? null;

        $phone = $flat['phone_number']
            ?? $flat['mobile_number']
            ?? $flat['phone']
            ?? $flat['whatsapp_number']
            ?? null;

        if (! $name && $email) {
            $name = Str::before($email, '@');
        }

        return [
            'external_id'   => $lead['id'] ?? null,
            'created_time'  => $lead['created_time'] ?? null,
            'form_id'       => $lead['form_id'] ?? null,

            'name'          => $name ?: 'Meta Lead',
            'email'         => $email,
            'phone'         => $phone,

            'campaign_id'   => $lead['campaign_id'] ?? null,
            'campaign_name' => $lead['campaign_name'] ?? null,
            'adset_id'      => $lead['adset_id'] ?? null,
            'adset_name'    => $lead['adset_name'] ?? null,
            'ad_id'         => $lead['ad_id'] ?? null,
            'ad_name'       => $lead['ad_name'] ?? null,
            'platform'      => $lead['platform'] ?? null,

            'fields'        => $flat,
            'raw'           => $lead,
        ];
    }

    private function flattenFieldData(array $fieldData): array
    {
        $out = [];

        foreach ($fieldData as $row) {
            $key = isset($row['name'])
                ? Str::of($row['name'])->lower()->snake()->toString()
                : null;

            if (! $key) {
                continue;
            }

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
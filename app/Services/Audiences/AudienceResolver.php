<?php
// app/Services/Audiences/AudienceResolver.php

namespace App\Services\Audiences;

use App\Models\Audience;
use App\Models\AudienceMembership;
use App\Models\Client\Client;
use Illuminate\Support\Facades\DB;

class AudienceResolver
{
    public function rebuildForCompany(int $companyId): void
    {
        $audiences = Audience::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->where('entity_type', 'client')
            ->get();

        foreach ($audiences as $audience) {
            $this->rebuildAudience($companyId, $audience);
        }

        $this->syncUnassigned($companyId);
    }

    public function rebuildAudience(int $companyId, Audience $audience): void
    {
        if ($audience->is_system && $audience->name === 'UNASSIGNED') {
            $this->syncUnassigned($companyId);
            return;
        }

        $rules = $audience->rules()->latest()->first()?->rules_json ?? null;
        if (!$rules || !is_array($rules)) {
            return;
        }

        $query = Client::query()->where('company_id', $companyId);

        // rules_json format:
        // {
        //   "operator": "AND",
        //   "conditions": [
        //      {"field":"preferred_channel","op":"=","value":"whatsapp"},
        //      {"field":"created_at","op":">=days_ago","value":7}
        //   ]
        // }
        $operator = strtoupper((string)($rules['operator'] ?? 'AND'));
        $conditions = is_array($rules['conditions'] ?? null) ? $rules['conditions'] : [];

        foreach ($conditions as $cond) {
            $field = (string)($cond['field'] ?? '');
            $op    = (string)($cond['op'] ?? '=');
            $value = $cond['value'] ?? null;

            if ($field === '') continue;

            $apply = function ($q) use ($field, $op, $value) {
                if ($op === '=') {
                    $q->where($field, $value);
                    return;
                }
                if ($op === '!=') {
                    $q->where($field, '!=', $value);
                    return;
                }
                if ($op === 'contains') {
                    $q->where($field, 'like', '%' . $value . '%');
                    return;
                }
                if ($op === 'in' && is_array($value)) {
                    $q->whereIn($field, $value);
                    return;
                }
                if ($op === 'not_in' && is_array($value)) {
                    $q->whereNotIn($field, $value);
                    return;
                }
                if ($op === '>') {
                    $q->where($field, '>', $value);
                    return;
                }
                if ($op === '>=') {
                    $q->where($field, '>=', $value);
                    return;
                }
                if ($op === '<') {
                    $q->where($field, '<', $value);
                    return;
                }
                if ($op === '<=') {
                    $q->where($field, '<=', $value);
                    return;
                }
                if ($op === 'is_null') {
                    $q->whereNull($field);
                    return;
                }
                if ($op === 'not_null') {
                    $q->whereNotNull($field);
                    return;
                }
                if ($op === '>=days_ago') {
                    $days = (int)$value;
                    $q->where($field, '>=', now()->subDays($days));
                    return;
                }
                if ($op === '<=days_ago') {
                    $days = (int)$value;
                    $q->where($field, '<=', now()->subDays($days));
                    return;
                }
            };

            if ($operator === 'OR') {
                $query->orWhere(function ($q) use ($apply) { $apply($q); });
            } else {
                $query->where(function ($q) use ($apply) { $apply($q); });
            }
        }

        $clientIds = $query->pluck('id')->all();

        DB::transaction(function () use ($companyId, $audience, $clientIds) {
            AudienceMembership::query()
                ->where('audience_id', $audience->id)
                ->where('company_id', $companyId)
                ->delete();

            $now = now();

            $rows = [];
            foreach ($clientIds as $cid) {
                $rows[] = [
                    'company_id' => $companyId,
                    'audience_id' => $audience->id,
                    'client_id' => $cid,
                    'added_by' => 'system',
                    'reason_json' => json_encode(['rule' => 'computed'], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($rows)) {
                AudienceMembership::query()->insert($rows);
            }
        });
    }

    public function syncUnassigned(int $companyId): void
    {
        $unassigned = Audience::query()
            ->where('is_system', 1)
            ->where('name', 'UNASSIGNED')
            ->first();

        if (!$unassigned) return;

        $inAny = AudienceMembership::query()
            ->where('company_id', $companyId)
            ->pluck('client_id')
            ->unique()
            ->all();

        $allClients = Client::query()->where('company_id', $companyId)->pluck('id')->all();

        $unassignedIds = array_values(array_diff($allClients, $inAny));

        DB::transaction(function () use ($companyId, $unassigned, $unassignedIds) {
            AudienceMembership::query()
                ->where('audience_id', $unassigned->id)
                ->where('company_id', $companyId)
                ->delete();

            $now = now();
            $rows = [];

            foreach ($unassignedIds as $cid) {
                $rows[] = [
                    'company_id' => $companyId,
                    'audience_id' => $unassigned->id,
                    'client_id' => $cid,
                    'added_by' => 'system',
                    'reason_json' => json_encode(['system' => 'unassigned'], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($rows)) {
                AudienceMembership::query()->insert($rows);
            }
        });
    }
}

<?php

// app/Services/Clients/MergeClientService.php

namespace App\Services\Clients;

use App\Models\Client\Client;
use App\Models\ClientDuplicateCandidate;
use Illuminate\Support\Facades\DB;

class MergeClientService
{
    public function merge(int $companyId, int $keepId, int $mergeId): void
    {
        if ($keepId === $mergeId) return;

        DB::transaction(function () use ($companyId, $keepId, $mergeId) {
            $keep = Client::query()->where('company_id', $companyId)->findOrFail($keepId);
            $merge = Client::query()->where('company_id', $companyId)->findOrFail($mergeId);

            // Move known relations (tables already exist per your schema)
            $this->safeUpdate('leads', 'client_id', $keep->id, $merge->id, $companyId);
            $this->safeUpdate('vehicles', 'client_id', $keep->id, $merge->id, $companyId);

            // Optional tables (if exist in your DB)
            $this->safeUpdateIfColumnExists('opportunities', 'client_id', $keep->id, $merge->id, $companyId);
            $this->safeUpdateIfColumnExists('bookings', 'client_id', $keep->id, $merge->id, $companyId);
            $this->safeUpdateIfColumnExists('communications', 'client_id', $keep->id, $merge->id, $companyId);

            // Merge missing fields from $merge into $keep (keep wins)
            $fill = [
                'email' => $keep->email ?: $merge->email,
                'phone' => $keep->phone ?: $merge->phone,
                'whatsapp' => $keep->whatsapp ?: $merge->whatsapp,
                'address' => $keep->address ?: $merge->address,
                'city' => $keep->city ?: $merge->city,
                'state' => $keep->state ?: $merge->state,
                'postal_code' => $keep->postal_code ?: $merge->postal_code,
                'country' => $keep->country ?: $merge->country,
                'dob' => $keep->dob ?: $merge->dob,
                'gender' => $keep->gender ?: $merge->gender,
                'preferred_channel' => $keep->preferred_channel ?: $merge->preferred_channel,
                'notes' => trim((string)$keep->notes) !== '' ? $keep->notes : $merge->notes,
            ];

            $keep->fill($fill);
            $keep->save();

            // Archive merged client (do not delete)
            $merge->is_archived = 1;
            $merge->status = 'merged';
            $merge->notes = trim(($merge->notes ?? '') . "\n[Merged into Client #{$keep->id}]");
            $merge->save();

            // mark candidates
            ClientDuplicateCandidate::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($keepId, $mergeId) {
                    $q->where(function ($qq) use ($keepId, $mergeId) {
                        $qq->where('client_a_id', $keepId)->where('client_b_id', $mergeId);
                    })->orWhere(function ($qq) use ($keepId, $mergeId) {
                        $qq->where('client_a_id', $mergeId)->where('client_b_id', $keepId);
                    });
                })
                ->update([
                    'status' => 'merged',
                    'merged_into_id' => $keepId,
                    'updated_at' => now(),
                ]);
        });
    }

    private function safeUpdate(string $table, string $col, int $toId, int $fromId, int $companyId): void
    {
        DB::table($table)
            ->where('company_id', $companyId)
            ->where($col, $fromId)
            ->update([$col => $toId]);
    }

    private function safeUpdateIfColumnExists(string $table, string $col, int $toId, int $fromId, int $companyId): void
    {
        if (!$this->tableHasColumn($table, $col)) return;

        DB::table($table)
            ->where('company_id', $companyId)
            ->where($col, $fromId)
            ->update([$col => $toId]);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$db, $table, $column]
        );

        return (int)($row->c ?? 0) > 0;
    }
}

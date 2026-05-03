<?php

// app/Services/Clients/DuplicateScanService.php

namespace App\Services\Clients;

use App\Models\Client\Client;
use App\Models\ClientDuplicateCandidate;

class DuplicateScanService
{
    public function scanCompany(int $companyId, int $limit = 200): int
    {
        $clients = Client::query()
            ->where('company_id', $companyId)
            ->where('is_archived', 0)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $created = 0;

        // naive O(n^2) for MVP within limit
        $count = $clients->count();
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $clients[$i];
                $b = $clients[$j];

                $score = 0;
                $reasons = [];

                // phone match
                $ap = $this->digits($a->phone ?: $a->whatsapp);
                $bp = $this->digits($b->phone ?: $b->whatsapp);
                if ($ap && $bp && $ap === $bp) {
                    $score += 70;
                    $reasons[] = 'phone_match';
                }

                // email match
                $ae = $this->normEmail($a->email);
                $be = $this->normEmail($b->email);
                if ($ae && $be && $ae === $be) {
                    $score += 70;
                    $reasons[] = 'email_match';
                }

                // name fuzzy (basic)
                if ($a->name && $b->name && mb_strtolower(trim($a->name)) === mb_strtolower(trim($b->name))) {
                    $score += 10;
                    $reasons[] = 'name_match';
                }

                if ($score < 70) continue;

                $pair = $this->orderPair($a->id, $b->id);

                $exists = ClientDuplicateCandidate::query()
                    ->where('company_id', $companyId)
                    ->where('client_a_id', $pair[0])
                    ->where('client_b_id', $pair[1])
                    ->exists();

                if ($exists) continue;

                ClientDuplicateCandidate::query()->create([
                    'company_id' => $companyId,
                    'client_a_id' => $pair[0],
                    'client_b_id' => $pair[1],
                    'match_score' => $score,
                    'reasons_json' => $reasons,
                    'status' => 'open',
                ]);

                $created++;
            }
        }

        return $created;
    }

    private function orderPair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    private function digits(?string $v): string
    {
        return preg_replace('/\D+/', '', (string)$v) ?: '';
    }

    private function normEmail(?string $v): string
    {
        $v = trim((string)$v);
        return $v !== '' ? mb_strtolower($v) : '';
    }
}

<?php

namespace App\Services\Leads;

use App\Services\Lead\LeadService;
use Illuminate\Support\Facades\Log;

class UploadLeadService
{
    protected LeadService $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function import(array $rows, int $companyId): array
    {
        $created = 0;
        $duplicates = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {

            // 🔹 Normalize input
            $name  = trim($row['name'] ?? '') ?: 'Uploaded Lead';
            $email = $this->cleanEmail($row['email'] ?? null);
            $phone = $this->cleanPhone($row['phone'] ?? null);

            // 🔹 Skip empty rows
            if (!$email && !$phone) {
                $skipped++;
                continue;
            }

            try {
                // 🔥 CENTRALIZED LEAD CREATION
                $lead = $this->leadService->createOrResolve([
                    'company_id'      => $companyId,
                    'name'            => $name,
                    'email'           => $email,
                    'phone'           => $phone,
                    'source'          => 'upload',
                    'external_source' => 'upload',
                ]);

                // 🔹 Detect if duplicate (based on recent lead logic)
                if ($lead->wasRecentlyCreated) {
                    $created++;
                } else {
                    $duplicates++;
                }

            } catch (\Throwable $e) {
                $skipped++;

                Log::error('Lead Upload Failed', [
                    'row_index' => $index,
                    'row'       => $row,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // 🔹 Final summary log
        Log::info('Lead Upload Summary', [
            'company_id' => $companyId,
            'created'    => $created,
            'duplicates' => $duplicates,
            'skipped'    => $skipped,
            'total'      => count($rows),
        ]);

        return [
            'created'    => $created,
            'duplicates' => $duplicates,
            'skipped'    => $skipped,
            'total'      => count($rows),
        ];
    }

    /**
     * Clean email
     */
    private function cleanEmail(?string $email): ?string
    {
        if (!$email) return null;

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Clean phone
     */
    private function cleanPhone(?string $phone): ?string
    {
        if (!$phone) return null;

        // Remove spaces, dashes, brackets
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // UAE normalization
        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        return $phone ?: null;
    }
}
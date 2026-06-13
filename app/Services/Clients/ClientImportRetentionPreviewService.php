<?php

namespace App\Services\Clients;

use App\Models\Client\Client;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ClientImportRetentionPreviewService
{
    public const DEFAULT_LIMIT = 200;

    private const HEADERS = [
        'name',
        'phone',
        'whatsapp',
        'email',
        'vehicle_make',
        'vehicle_model',
        'plate_number',
        'vehicle_year',
        'last_service_date',
        'last_service_type',
        'last_invoice_amount',
        'last_mileage',
        'insurance_expiry_date',
        'mulkia_expiry_date',
        'source',
        'status',
        'is_vip',
        'preferred_channel',
        'notes',
    ];

    public function buildPreview(UploadedFile $file, int $companyId, int $limit = self::DEFAULT_LIMIT): array
    {
        [$headers, $rawRows] = $this->readRows($file);

        $this->validateRequiredHeaders($headers);

        $rows = [];
        $summary = [
            'rows_uploaded' => 0,
            'rows_previewed' => 0,
            'valid_contact_rows' => 0,
            'valid_rows' => 0,
            'warning_rows' => 0,
            'invalid_rows' => 0,
            'duplicates' => 0,
            'service_history_rows' => 0,
            'suggested_retention_actions' => 0,
            'truncated' => false,
            'limit' => $limit,
        ];

        foreach ($rawRows as $index => $rawRow) {
            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            $summary['rows_uploaded']++;

            if (count($rows) >= $limit) {
                $summary['truncated'] = true;
                continue;
            }

            $mapped = $this->mapRow($headers, $rawRow);
            $row = $this->analyzeRow($mapped, $companyId, $index + 2);

            $rows[] = $row;
        }

        foreach ($rows as $row) {
            $summary['rows_previewed']++;

            if (($row['status'] ?? null) === 'valid' && ! $this->rowHasWarnings($row) && ! $this->rowHasErrors($row)) {
                $summary['valid_rows']++;
            }

            if ($this->rowIsImportableContact($row)) {
                $summary['valid_contact_rows']++;
            }

            if (($row['status'] ?? null) === 'warning' || $this->rowHasWarnings($row)) {
                $summary['warning_rows']++;
            }

            if (($row['status'] ?? null) === 'invalid' || $this->rowHasErrors($row)) {
                $summary['invalid_rows']++;
            }

            if ($row['duplicate']) {
                $summary['duplicates']++;
            }

            if ($this->rowHasServiceHistory($row)) {
                $summary['service_history_rows']++;
            }

            if ($this->rowHasRetentionAction($row)) {
                $summary['suggested_retention_actions']++;
            }
        }

        return [
            'headers' => self::HEADERS,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    private function rowIsImportableContact(array $row): bool
    {
        if (($row['status'] ?? null) === 'invalid' || $this->rowHasErrors($row)) {
            return false;
        }

        $payload = $row['data'] ?? [];

        return filled($payload['name'] ?? null)
            && (filled($payload['phone'] ?? null) || filled($payload['whatsapp'] ?? null));
    }

    private function rowHasWarnings(array $row): bool
    {
        return ! empty($row['warnings'] ?? []);
    }

    private function rowHasErrors(array $row): bool
    {
        return ! empty($row['errors'] ?? []);
    }

    private function rowHasServiceHistory(array $row): bool
    {
        if (! $this->rowIsImportableContact($row)) {
            return false;
        }

        $payload = $row['data'] ?? [];

        return filled($payload['last_service_date'] ?? null)
            || filled($payload['last_service_type'] ?? null)
            || filled($payload['last_mileage'] ?? null)
            || filled($payload['last_invoice_amount'] ?? null);
    }

    private function rowHasRetentionAction(array $row): bool
    {
        if (! $this->rowIsImportableContact($row)) {
            return false;
        }

        return ($row['suggestion']['segment_code'] ?? 'unclassified') !== 'unclassified';
    }

    private function readRows(UploadedFile $file): array
    {
        try {
            $extension = Str::lower($file->getClientOriginalExtension());
            $readerType = match ($extension) {
                'csv', 'txt' => 'Csv',
                'xls' => 'Xls',
                default => 'Xlsx',
            };

            $reader = IOFactory::createReader($readerType);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read this file. Please upload a valid CSV or Excel file.',
            ]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is empty.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader($value), array_shift($rows));

        return [$headers, $rows];
    }

    private function validateRequiredHeaders(array $headers): void
    {
        $hasName = in_array('name', $headers, true);
        $hasPhone = in_array('phone', $headers, true);
        $hasWhatsapp = in_array('whatsapp', $headers, true);

        $messages = [];

        if (! $hasName) {
            $messages[] = 'name';
        }

        if (! $hasPhone && ! $hasWhatsapp) {
            $messages[] = 'phone or whatsapp';
        }

        if ($messages) {
            throw ValidationException::withMessages([
                'file' => 'Missing required column: ' . implode(', ', $messages) . '.',
            ]);
        }
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = array_fill_keys(self::HEADERS, null);

        foreach ($headers as $index => $header) {
            if (! $header || ! array_key_exists($header, $mapped)) {
                continue;
            }

            $mapped[$header] = $this->stringValue($row[$index] ?? null);
        }

        return $mapped;
    }

    private function analyzeRow(array $row, int $companyId, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        $name = trim((string) ($row['name'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $whatsapp = trim((string) ($row['whatsapp'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));

        if ($name === '') {
            $errors[] = 'Missing customer name.';
        }

        if ($phone === '' && $whatsapp === '') {
            $errors[] = 'Missing phone or WhatsApp number.';
        }

        $dateFields = [
            'last_service_date' => 'Last service date',
            'insurance_expiry_date' => 'Insurance expiry date',
            'mulkia_expiry_date' => 'Mulkia expiry date',
        ];

        $dates = [];

        foreach ($dateFields as $field => $label) {
            $dates[$field] = null;

            if (! filled($row[$field])) {
                continue;
            }

            $dates[$field] = $this->parseDate($row[$field]);

            if (! $dates[$field]) {
                $errors[] = "{$label} has an invalid date format.";
            }
        }

        if ($dates['last_service_date']?->isFuture()) {
            $warnings[] = 'Last service date is in the future.';
        }

        if (! filled($row['vehicle_make']) || ! filled($row['vehicle_model'])) {
            $warnings[] = 'Vehicle make or model is missing.';
        }

        if (! $dates['last_service_date']) {
            $warnings[] = 'Last service date is missing.';
        }

        $duplicate = $this->findDuplicate($companyId, $phone, $whatsapp, $email);

        if ($duplicate) {
            $warnings[] = 'Possible duplicate client found.';
        }

        $suggestion = $this->suggestRetention($row, $dates, $name);

        if ($this->isVip($row['is_vip'] ?? null) && ($suggestion['segment_code'] ?? null) !== 'vip_follow_up') {
            $suggestion['secondary_segments'][] = [
                'segment_code' => 'vip_follow_up',
                'segment_label' => 'VIP Follow-up',
            ];
            $warnings[] = 'VIP client: consider a premium follow-up.';
        }

        if (filled($row['last_service_type']) && ($suggestion['service_type_matched'] ?? false) === false) {
            $warnings[] = 'Service type was not recognized for a specific retention segment.';
        }

        $status = 'valid';

        if ($errors) {
            $status = 'invalid';
        } elseif ($warnings) {
            $status = 'warning';
        }

        return [
            'row_number' => $rowNumber,
            'status' => $status,
            'raw_payload' => $row,
            'data' => [
                'name' => $name,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'email' => $email,
                'vehicle_make' => $row['vehicle_make'],
                'vehicle_model' => $row['vehicle_model'],
                'plate_number' => $row['plate_number'],
                'vehicle_year' => $row['vehicle_year'],
                'last_service_date' => $this->formatDate($dates['last_service_date']),
                'last_service_type' => $row['last_service_type'],
                'last_invoice_amount' => $row['last_invoice_amount'],
                'last_mileage' => $row['last_mileage'],
                'insurance_expiry_date' => $this->formatDate($dates['insurance_expiry_date']),
                'mulkia_expiry_date' => $this->formatDate($dates['mulkia_expiry_date']),
                'source' => $row['source'],
                'status' => $row['status'],
                'is_vip' => $row['is_vip'],
                'preferred_channel' => $row['preferred_channel'],
                'notes' => $row['notes'],
            ],
            'duplicate' => $duplicate,
            'duplicate_status' => $this->duplicateStatus($duplicate, $phone, $whatsapp, $email),
            'suggestion' => $suggestion,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function duplicateStatus(?array $duplicate, ?string $phone, ?string $whatsapp, ?string $email): string
    {
        if (! $duplicate) {
            return 'none';
        }

        $matchedPhone = Client::normalizePhone($phone);
        $matchedWhatsapp = Client::normalizePhone($whatsapp);

        if ($matchedPhone && in_array($matchedPhone, [
            Client::normalizePhone($duplicate['phone'] ?? null),
            Client::normalizePhone($duplicate['whatsapp'] ?? null),
        ], true)) {
            return 'matched_phone';
        }

        if ($matchedWhatsapp && in_array($matchedWhatsapp, [
            Client::normalizePhone($duplicate['phone'] ?? null),
            Client::normalizePhone($duplicate['whatsapp'] ?? null),
        ], true)) {
            return 'matched_whatsapp';
        }

        if (Client::normalizeEmail($email) && Client::normalizeEmail($email) === Client::normalizeEmail($duplicate['email'] ?? null)) {
            return 'matched_email';
        }

        return 'matched_phone';
    }

    private function findDuplicate(int $companyId, ?string $phone, ?string $whatsapp, ?string $email): ?array
    {
        $normalizedPhone = Client::normalizePhone($phone);
        $normalizedWhatsapp = Client::normalizePhone($whatsapp);
        $normalizedEmail = Client::normalizeEmail($email);
        $numbers = array_values(array_unique(array_filter([
            $normalizedPhone,
            $normalizedWhatsapp,
            trim((string) $phone) ?: null,
            trim((string) $whatsapp) ?: null,
        ])));

        $client = null;

        if ($numbers) {
            $client = Client::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($numbers) {
                    foreach ($numbers as $number) {
                        $query->orWhere('phone_norm', $number)
                            ->orWhere('phone', $number)
                            ->orWhere('whatsapp', $number);
                    }
                })
                ->first(['id', 'name', 'phone', 'whatsapp', 'email']);
        }

        if (! $client && $normalizedEmail) {
            $client = Client::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($normalizedEmail, $email) {
                    $query->where('email_norm', $normalizedEmail)
                        ->orWhere('email', $email);
                })
                ->first(['id', 'name', 'phone', 'whatsapp', 'email']);
        }

        return $client ? [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'whatsapp' => $client->whatsapp,
            'email' => $client->email,
        ] : null;
    }

    private function suggestRetention(array $row, array $dates, string $name): array
    {
        $today = today();
        $serviceType = Str::lower((string) ($row['last_service_type'] ?? ''));
        $vehicle = trim(implode(' ', array_filter([
            $row['vehicle_make'] ?? null,
            $row['vehicle_model'] ?? null,
        ]))) ?: 'your vehicle';

        $base = [
            'segment_code' => 'unclassified',
            'segment_label' => 'Unclassified',
            'follow_up_date' => null,
            'message' => "Hi {$name}, we would be happy to help with your next vehicle service whenever convenient.",
            'secondary_segments' => [],
            'service_type_matched' => false,
        ];

        if ($dates['insurance_expiry_date'] && $dates['insurance_expiry_date']->betweenIncluded($today, $today->copy()->addDays(30))) {
            return array_merge($base, [
                'segment_code' => 'insurance_expiry_reminder',
                'segment_label' => 'Insurance Expiry Reminder',
                'follow_up_date' => $this->suggestActionDate($dates['insurance_expiry_date']->copy()->subDays(14)),
                'message' => "Hi {$name}, your vehicle insurance may be due for renewal soon. Would you like assistance with the renewal?",
            ]);
        }

        if ($dates['mulkia_expiry_date'] && $dates['mulkia_expiry_date']->betweenIncluded($today, $today->copy()->addDays(30))) {
            return array_merge($base, [
                'segment_code' => 'mulkia_renewal_reminder',
                'segment_label' => 'Mulkia Renewal Reminder',
                'follow_up_date' => $this->suggestActionDate($dates['mulkia_expiry_date']->copy()->subDays(14)),
                'message' => "Hi {$name}, your vehicle registration renewal may be coming up soon. Would you like us to help with the process?",
            ]);
        }

        $serviceRules = [
            'general_service_due' => [
                'label' => 'General Service Due',
                'months' => 6,
                'patterns' => ['general service', 'general'],
                'message' => "Hi {$name}, your {$vehicle} may be due for a general service check. Would you like us to help schedule a convenient time?",
            ],
            'oil_change_due' => [
                'label' => 'Oil Change Due',
                'months' => 3,
                'patterns' => ['oil'],
                'message' => "Hi {$name}, your {$vehicle} may be due for an oil change. Would you like us to help schedule a convenient time?",
            ],
            'tyre_check_due' => [
                'label' => 'Tyre Check Due',
                'months' => 6,
                'patterns' => ['tyre', 'tire'],
                'message' => "Hi {$name}, your {$vehicle} may be due for a tyre check. Would you like us to help schedule a convenient time?",
            ],
            'battery_follow_up' => [
                'label' => 'Battery Follow-up',
                'months' => 12,
                'patterns' => ['battery'],
                'message' => "Hi {$name}, we can help check the battery health on your {$vehicle}. Would you like to schedule a quick check?",
            ],
            'ac_service_reminder' => [
                'label' => 'AC Service Reminder',
                'months' => 6,
                'patterns' => ['ac', 'a/c', 'air condition', 'air-conditioning', 'air conditioning'],
                'message' => "Hi {$name}, your {$vehicle} may be due for an AC service check. Would you like us to help schedule it?",
            ],
            'brake_check_reminder' => [
                'label' => 'Brake Check Reminder',
                'months' => 6,
                'patterns' => ['brake'],
                'message' => "Hi {$name}, your {$vehicle} may be due for a brake check. Would you like us to help schedule a convenient time?",
            ],
        ];

        if ($dates['last_service_date']) {
            foreach ($serviceRules as $code => $rule) {
                if ($this->matchesAny($serviceType, $rule['patterns'])) {
                    return array_merge($base, [
                        'segment_code' => $code,
                        'segment_label' => $rule['label'],
                        'follow_up_date' => $this->suggestActionDate($dates['last_service_date']->copy()->addMonths($rule['months'])),
                        'message' => $rule['message'],
                        'service_type_matched' => true,
                    ]);
                }
            }

            if ($dates['last_service_date']->lt($today->copy()->subMonths(12))) {
                return array_merge($base, [
                    'segment_code' => 'inactive_customer_winback',
                    'segment_label' => 'Inactive Customer Winback',
                    'follow_up_date' => $today->toDateString(),
                    'message' => "Hi {$name}, we haven't seen your {$vehicle} for a while. Would you like to book a quick inspection or service check?",
                ]);
            }
        }

        if ($this->isVip($row['is_vip'] ?? null)) {
            return array_merge($base, [
                'segment_code' => 'vip_follow_up',
                'segment_label' => 'VIP Follow-up',
                'follow_up_date' => $today->copy()->addDays(7)->toDateString(),
                'message' => "Hi {$name}, we would be happy to arrange a priority service check for your {$vehicle} whenever convenient.",
            ]);
        }

        return $base;
    }

    private function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::contains($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value) && (float) $value > 20000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'd M Y',
            'M d Y',
            'd F Y',
            'F d Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function suggestActionDate(Carbon $date): string
    {
        return $date->isPast() ? today()->toDateString() : $date->toDateString();
    }

    private function formatDate(?Carbon $date): ?string
    {
        return $date?->toDateString();
    }

    private function isVip(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'yes', 'true', 'vip'], true);
    }

    private function normalizeHeader(mixed $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        $value = Str::lower(trim($value));

        return preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}

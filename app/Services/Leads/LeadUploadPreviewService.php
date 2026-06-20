<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class LeadUploadPreviewService
{
    public const DEFAULT_LIMIT = 200;
    private const UPLOAD_ACK_EVENT_KEY = 'lead.upload.instant_ack';
    private const FALLBACK_ACK_EVENT_KEY = 'lead.created';

    public function preview(UploadedFile $file, int $companyId, int $limit = self::DEFAULT_LIMIT, ?string $defaultCampaignType = null): array
    {
        $limit = max(1, min($limit, self::DEFAULT_LIMIT));
        $rawRows = $this->parseFile($file, $limit + 1);
        $headers = $rawRows['headers'];
        $rows = $rawRows['rows'];

        $previewRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->cleanRow(array_combine($headers, array_pad($row, count($headers), null)) ?: []);

            if ($this->isEmptyDataRow($data)) {
                continue;
            }

            $previewRows[] = $this->previewRow($data, $rowNumber, $companyId, $defaultCampaignType);
        }

        $previewRows = array_slice($previewRows, 0, $limit);

        return [
            'summary' => $this->summary($previewRows, count($rows), $limit),
            'headers' => $headers,
            'rows' => $previewRows,
            'notice' => 'Preview only. No leads, clients, vehicles, messages, campaigns, or journeys have been created.',
            'event_key' => self::UPLOAD_ACK_EVENT_KEY,
            'fallback_event_key' => self::FALLBACK_ACK_EVENT_KEY,
        ];
    }

    private function previewRow(array $data, int $rowNumber, int $companyId, ?string $defaultCampaignType): array
    {
        $name = trim((string) ($data['customer_name'] ?? $data['name'] ?? ''));
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $whatsapp = $this->normalizePhone($data['whatsapp'] ?? null);
        $contactPhone = $whatsapp ?: $phone;
        $email = $this->normalizeEmail($data['email'] ?? null);
        $source = $this->normalizeSource($data['lead_source'] ?? $data['source'] ?? null);
        $rowCampaignType = trim((string) ($data['campaign_type'] ?? ''));
        $campaignType = LeadCampaignTypeJourneyMap::normalize($rowCampaignType !== '' ? $rowCampaignType : $defaultCampaignType);
        $journeyMapping = LeadCampaignTypeJourneyMap::resolve($companyId, $campaignType);
        $service = $this->serviceLabel($data);
        $campaign = trim((string) ($data['campaign_name'] ?? ''));
        $preferredDate = $data['preferred_date'] ?? $data['follow_up_date'] ?? null;

        $errors = [];
        $warnings = [];

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if (! $contactPhone) {
            $errors[] = 'Phone or WhatsApp is required.';
        }

        if ($source === '') {
            $errors[] = 'Source is required.';
        }

        if (! $campaignType) {
            $errors[] = 'Campaign type is required.';
        }

        if (($data['email'] ?? null) && ! $email) {
            $warnings[] = 'Email format looks invalid and will need review.';
        }

        if (! empty($preferredDate) && ! $this->parseDate($preferredDate)) {
            $errors[] = 'Preferred date has an invalid date format.';
        }

        $clientMatch = $this->findClientMatch($companyId, $contactPhone, $email);
        $leadMatch = $this->findRecentLeadMatch($companyId, $contactPhone, $email);
        $optedOut = $this->isOptedOut($companyId, $contactPhone, $clientMatch?->id, $leadMatch?->id);

        if ($clientMatch) {
            $warnings[] = "Duplicate client match found: #{$clientMatch->id}.";
        }

        if ($leadMatch) {
            $warnings[] = "Recent duplicate lead found: #{$leadMatch->id}.";
        }

        if (! $service) {
            $warnings[] = 'Service category/type is missing.';
        }

        if ($optedOut) {
            $warnings[] = 'Customer appears opted out of WhatsApp automation.';
        }

        $readiness = $this->ackReadiness($companyId, $contactPhone, $name, $service, $optedOut, (bool) $leadMatch, ! empty($errors));
        $status = ! empty($errors) ? 'invalid' : (! empty($warnings) || $readiness['status'] !== 'ready' ? 'warning' : 'valid');

        return [
            'row_number' => $rowNumber,
            'status' => $status,
            'name' => $name,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'contact_phone' => $contactPhone,
            'email' => $email,
            'source' => $source,
            'campaign_type' => $campaignType,
            'journey_key' => $journeyMapping['journey_key'] ?? null,
            'journey_label' => $journeyMapping['journey_label'] ?? null,
            'journey_trigger_key' => $journeyMapping['journey_trigger_key'] ?? null,
            'mapping_status' => $journeyMapping['mapping_status'] ?? 'Missing',
            'whatsapp_status' => $journeyMapping['whatsapp_status'] ?? 'Disabled',
            'journey_mapping' => $journeyMapping,
            'service' => $service,
            'campaign' => $campaign,
            'city' => trim((string) ($data['city'] ?? '')),
            'preferred_date' => $preferredDate,
            'preferred_time' => trim((string) ($data['preferred_time'] ?? '')),
            'vehicle' => $this->vehicleLabel($data),
            'client_match' => $clientMatch ? [
                'id' => $clientMatch->id,
                'name' => $clientMatch->name,
            ] : null,
            'lead_match' => $leadMatch ? [
                'id' => $leadMatch->id,
                'name' => $leadMatch->name,
                'status' => $leadMatch->status,
                'source' => $leadMatch->source,
                'created_at' => optional($leadMatch->created_at)->format('d M Y'),
            ] : null,
            'ack_readiness' => $readiness,
            'suggested_message' => $this->suggestedMessage($name, $service),
            'warnings' => $warnings,
            'errors' => $errors,
            'raw' => $data,
        ];
    }

    private function parseFile(UploadedFile $file, int $limit): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (in_array($extension, ['xls', 'xlsx'], true) && class_exists(Excel::class)) {
            $sheets = Excel::toArray(new class implements ToArray {
                public function array(array $array): array
                {
                    return $array;
                }
            }, $file);

            $rows = $sheets[0] ?? [];
        } else {
            $rows = $this->parseCsv($file);
        }

        $rows = array_values(array_filter($rows, fn ($row) => is_array($row)));

        if (empty($rows)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($header) => $this->cleanHeader($header), array_shift($rows));
        $headers = array_values(array_map(fn ($header) => $header ?: 'column', $headers));

        return [
            'headers' => $headers,
            'rows' => array_slice($rows, 0, $limit),
        ];
    }

    private function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! $path || ! file_exists($path)) {
            return [];
        }

        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function summary(array $rows, int $rowsRead, int $limit): array
    {
        $countBy = fn (callable $callback) => count(array_filter($rows, $callback));

        return [
            'rows_read' => $rowsRead,
            'rows_shown' => count($rows),
            'limit' => $limit,
            'truncated' => $rowsRead > $limit,
            'valid' => $countBy(fn ($row) => $row['status'] === 'valid'),
            'warnings' => $countBy(fn ($row) => $row['status'] === 'warning'),
            'invalid' => $countBy(fn ($row) => $row['status'] === 'invalid'),
            'duplicate_clients' => $countBy(fn ($row) => ! empty($row['client_match'])),
            'duplicate_leads' => $countBy(fn ($row) => ! empty($row['lead_match'])),
            'ready_for_ack' => $countBy(fn ($row) => ($row['ack_readiness']['status'] ?? null) === 'ready'),
            'blocked_or_not_ready' => $countBy(fn ($row) => ($row['ack_readiness']['status'] ?? null) !== 'ready'),
        ];
    }

    private function ackReadiness(int $companyId, ?string $phone, string $name, ?string $service, bool $optedOut, bool $duplicateLead, bool $invalidRow): array
    {
        if ($invalidRow) {
            return ['status' => 'invalid_row', 'label' => 'Invalid row', 'reason' => 'Fix row errors before instant response can be considered.'];
        }

        if (! $phone) {
            return ['status' => 'missing_phone', 'label' => 'Missing phone', 'reason' => 'Phone or WhatsApp is required.'];
        }

        if ($optedOut) {
            return ['status' => 'opted_out', 'label' => 'Opted out', 'reason' => 'Customer appears opted out from WhatsApp automation.'];
        }

        if ($duplicateLead) {
            return ['status' => 'duplicate_recent_lead', 'label' => 'Duplicate lead', 'reason' => 'A recent lead already exists for this phone/email.'];
        }

        if ($name === '' || ! $service) {
            return ['status' => 'needs_review', 'label' => 'Needs review', 'reason' => 'Required message variables are incomplete.'];
        }

        $mapping = $this->templateMapping($companyId, self::UPLOAD_ACK_EVENT_KEY);
        $fallback = $this->templateMapping($companyId, self::FALLBACK_ACK_EVENT_KEY);

        if ($mapping && ! $mapping->template) {
            return ['status' => 'missing_template_mapping', 'label' => 'Missing template', 'reason' => 'Upload ACK mapping exists but has no template assigned.'];
        }

        if ($mapping && $mapping->template && ! $this->isTemplateSendable((string) $mapping->template->status)) {
            return ['status' => 'template_pending', 'label' => 'Template pending', 'reason' => 'Upload ACK template exists but is not active/approved.'];
        }

        if ($mapping && $mapping->template && $this->isTemplateSendable((string) $mapping->template->status)) {
            return [
                'status' => 'ready',
                'label' => 'Ready',
                'reason' => 'Upload ACK mapping and active template are available.',
                'event_key' => self::UPLOAD_ACK_EVENT_KEY,
                'template' => $mapping->template->name,
            ];
        }

        if ($fallback && $fallback->template && $this->isTemplateSendable((string) $fallback->template->status)) {
            return [
                'status' => 'needs_review',
                'label' => 'Fallback available',
                'reason' => 'lead.upload.instant_ack is missing; lead.created fallback is active for future review.',
                'event_key' => self::FALLBACK_ACK_EVENT_KEY,
                'template' => $fallback->template->name,
            ];
        }

        return ['status' => 'missing_template_mapping', 'label' => 'Missing mapping', 'reason' => 'No active upload ACK template mapping found.'];
    }

    private function templateMapping(int $companyId, string $eventKey): ?WhatsAppTemplateMapping
    {
        if (! Schema::hasTable('whatsapp_template_mappings')) {
            return null;
        }

        return WhatsAppTemplateMapping::query()
            ->where('company_id', $companyId)
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->with('template')
            ->first();
    }

    private function isTemplateSendable(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['active', 'approved'], true);
    }

    private function findClientMatch(int $companyId, ?string $phone, ?string $email): ?Client
    {
        if (! Schema::hasTable('clients') || (! $phone && ! $email)) {
            return null;
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($phone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone);

                    foreach (['phone_norm', 'whatsapp', 'whatsapp_number'] as $field) {
                        if (Schema::hasColumn('clients', $field)) {
                            $query->orWhere($field, $phone);
                        }
                    }
                }

                if ($email) {
                    $query->orWhere('email', $email);

                    if (Schema::hasColumn('clients', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }
            })
            ->first();
    }

    private function findRecentLeadMatch(int $companyId, ?string $phone, ?string $email): ?Lead
    {
        if (! Schema::hasTable('leads') || (! $phone && ! $email)) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->where(function ($query) use ($phone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone);

                    if (Schema::hasColumn('leads', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phone);
                    }
                }

                if ($email) {
                    $query->orWhere('email', $email);

                    if (Schema::hasColumn('leads', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }
            })
            ->latest()
            ->first();
    }

    private function isOptedOut(int $companyId, ?string $phone, ?int $clientId, ?int $leadId): bool
    {
        $phoneDigits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($phoneDigits && Schema::hasTable('whatsapp_opt_outs')) {
            $query = DB::table('whatsapp_opt_outs')->where('company_id', $companyId);
            $columns = Schema::getColumnListing('whatsapp_opt_outs');

            $query->where(function ($q) use ($columns, $phone, $phoneDigits) {
                foreach (['phone', 'phone_e164', 'phone_norm', 'mobile', 'mobile_norm'] as $column) {
                    if (in_array($column, $columns, true)) {
                        $q->orWhere($column, in_array($column, ['phone_norm', 'mobile_norm'], true) ? $phoneDigits : $phone);
                    }
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        foreach ([['clients', $clientId], ['leads', $leadId]] as [$table, $id]) {
            if (! $id || ! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $optColumns = array_values(array_intersect($columns, [
                'whatsapp_opt_out',
                'is_whatsapp_opted_out',
                'opted_out_whatsapp',
                'wa_opt_out',
                'marketing_opt_out',
            ]));

            if (empty($optColumns)) {
                continue;
            }

            $row = DB::table($table)
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->first($optColumns);

            foreach ($optColumns as $column) {
                if ($this->truthy($row->{$column} ?? null)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function suggestedMessage(string $name, ?string $service): string
    {
        $name = $name !== '' ? $name : 'there';
        $service = $service ?: 'your enquiry';

        return "Hi {$name}, thank you for showing interest in {$service}. Our team will contact you shortly.";
    }

    private function vehicleLabel(array $data): ?string
    {
        $parts = array_filter([
            $data['vehicle_year'] ?? null,
            $data['vehicle_make'] ?? null,
            $data['vehicle_model'] ?? null,
            $data['plate_number'] ?? null,
        ]);

        return $parts ? implode(' ', $parts) : null;
    }

    private function serviceLabel(array $data): ?string
    {
        $service = trim((string) ($data['service_type'] ?? ''));

        if ($service !== '') {
            return $service;
        }

        $category = trim((string) ($data['service_category'] ?? ''));

        return $category !== '' ? $category : null;
    }

    private function cleanRow(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $key = $this->cleanHeader($key);

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$key] = $value === '' ? null : $value;
        }

        return $clean;
    }

    private function cleanHeader(mixed $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
        $header = trim(strtolower($header));

        return str_replace([' ', '-'], '_', $header);
    }

    private function isEmptyDataRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $phone = trim((string) $phone);

        if (stripos($phone, 'E+') !== false || stripos($phone, 'E-') !== false) {
            $phone = number_format((float) $phone, 0, '', '');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeSource(mixed $source): string
    {
        return strtolower(trim((string) $source));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on', 'opted_out'], true);
    }
}

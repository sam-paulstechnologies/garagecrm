<?php

namespace App\Support;

use App\Models\System\Company;
use Illuminate\Support\Facades\Schema;

class WhatsAppChannelSummary
{
    public static function forCompany(?Company $company): array
    {
        $displayNumber = self::columnValue($company, 'meta_display_phone_number');
        $phoneNumberId = self::columnValue($company, 'meta_phone_number_id');
        $accessToken = self::columnValue($company, 'meta_access_token');
        $isActive = (bool) ($company?->is_whatsapp_active ?? false);
        $isConfigured = filled($displayNumber) || filled($phoneNumberId);
        $isConnected = $isActive && $isConfigured && filled($accessToken);

        return [
            'company_name' => $company?->name ?: 'Garage',
            'status' => $isConnected ? 'Connected' : 'Not connected',
            'is_connected' => $isConnected,
            'display_phone_number' => $displayNumber,
            'phone_number_id_masked' => blank($displayNumber) && filled($phoneNumberId)
                ? self::maskIdentifier((string) $phoneNumberId)
                : null,
            'summary' => $isConnected
                ? 'Connected WhatsApp channel configured'
                : 'No connected WhatsApp channel found',
        ];
    }

    protected static function columnValue(?Company $company, string $column): ?string
    {
        if (! $company || ! Schema::hasColumn($company->getTable(), $column)) {
            return null;
        }

        $value = trim((string) ($company->{$column} ?? ''));

        return $value !== '' ? $value : null;
    }

    protected static function maskIdentifier(string $value): string
    {
        $suffix = substr($value, -4);

        return '****'.$suffix;
    }
}

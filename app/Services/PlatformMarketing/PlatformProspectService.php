<?php

namespace App\Services\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingActivityLog;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use Illuminate\Validation\ValidationException;

class PlatformProspectService
{
    public function __construct(private PlatformPhoneNormalizer $phoneNormalizer)
    {
    }

    public function createOrUpdate(array $data, ?PlatformMarketingProspect $prospect = null, ?int $actorId = null): PlatformMarketingProspect
    {
        $normalizedPhone = $this->phoneNormalizer->normalize($data['whatsapp_number'] ?? $prospect?->whatsapp_number);

        if ($normalizedPhone === '') {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'A valid WhatsApp number is required.',
            ]);
        }

        $duplicate = PlatformMarketingProspect::query()
            ->where('normalized_phone', $normalizedPhone)
            ->when($prospect, fn ($query) => $query->whereKeyNot($prospect->id))
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'A platform prospect already exists for this WhatsApp number.',
            ]);
        }

        $payload = array_merge($data, [
            'normalized_phone' => $normalizedPhone,
            'whatsapp_number' => $data['whatsapp_number'] ?? '+'.$normalizedPhone,
        ]);

        $prospect ??= new PlatformMarketingProspect();
        $prospect->fill($payload)->save();

        PlatformMarketingActivityLog::query()->create([
            'prospect_id' => $prospect->id,
            'user_id' => $actorId,
            'action' => $prospect->wasRecentlyCreated ? 'prospect.created' : 'prospect.updated',
            'metadata' => ['status' => $prospect->status],
        ]);

        return $prospect;
    }
}

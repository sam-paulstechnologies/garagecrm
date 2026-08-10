<?php

namespace App\Commercial;

use App\Models\Commercial\ProductEvent;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ProductEventRecorder
{
    /** @param array<string, scalar|null> $properties */
    public function recordSafely(
        string $eventType,
        ?Company $company = null,
        ?User $user = null,
        array $properties = [],
        ?string $dedupeKey = null,
    ): ?ProductEvent {
        try {
            if (! Schema::hasTable('product_events')) {
                return null;
            }

            return $this->record($eventType, $company, $user, $properties, $dedupeKey);
        } catch (QueryException $exception) {
            Log::warning('Product event persistence failed without blocking the primary operation.', [
                'event_type' => $eventType,
                'company_id' => $company?->id,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /** @param array<string, scalar|null> $properties */
    public function record(
        string $eventType,
        ?Company $company = null,
        ?User $user = null,
        array $properties = [],
        ?string $dedupeKey = null,
    ): ProductEvent {
        if (! in_array($eventType, ProductEvents::ALL, true)) {
            throw new InvalidArgumentException('Unknown product event type.');
        }
        if ($user && (! $company || (int) $user->company_id !== (int) $company->id)) {
            throw new InvalidArgumentException('Product event user is outside the tenant.');
        }

        $allowed = ProductEvents::PROPERTIES[$eventType];
        foreach ($properties as $key => $value) {
            if (! in_array($key, $allowed, true) || ! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Product event contains an unapproved property.');
            }
            $string = (string) $value;
            if (str_contains($string, '@') || preg_match('/\b\d{7,}\b/', $string) || strlen($string) > 120) {
                throw new InvalidArgumentException('Product event property may contain personal or sensitive data.');
            }
        }

        $key = hash('sha256', $dedupeKey ?: implode('|', [
            $eventType,
            $company?->id ?? 'platform',
            $user?->id ?? 'anonymous',
            now()->format('Y-m-d-H-i-s-u'),
            bin2hex(random_bytes(8)),
        ]));

        return ProductEvent::query()->firstOrCreate(
            ['dedupe_key' => $key],
            [
                'company_id' => $company?->id,
                'user_id' => $user?->id,
                'event_type' => $eventType,
                'source' => 'application',
                'properties' => $properties ?: null,
                'occurred_at' => now(),
            ],
        );
    }
}

<?php

namespace App\Notifications;

use App\Models\Notifications\PushDevice;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

final class PushDeviceService
{
    public function register(User $user, string $provider, string $token, ?string $platform, ?string $deviceName): PushDevice
    {
        if (! $user->company_id || ! in_array($provider, config('mobile_notifications.allowed_drivers', []), true)) {
            throw ValidationException::withMessages(['provider' => 'This push provider is not allowed.']);
        }

        $token = trim($token);
        if ($token === '') {
            throw ValidationException::withMessages(['token' => 'A device token is required.']);
        }

        $hash = hash_hmac('sha256', $token, (string) config('app.key'));

        return PushDevice::query()->updateOrCreate(
            ['company_id' => $user->company_id, 'provider' => $provider, 'token_hash' => $hash],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'encrypted_token' => Crypt::encryptString($token),
                'status' => 'active',
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function revoke(User $user, PushDevice $device): void
    {
        abort_unless((int) $device->company_id === (int) $user->company_id && (int) $device->user_id === (int) $user->id, 404);
        $device->update(['status' => 'revoked', 'revoked_at' => now()]);
    }
}

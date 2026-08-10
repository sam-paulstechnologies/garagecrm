<?php

namespace App\Messaging\WhatsApp;

use App\Commercial\EntitlementService;
use App\Commercial\ResourceLimitService;
use App\Messaging\Models\MessagingNumberClaim;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WhatsAppNumberClaimService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly ResourceLimitService $limits,
        private readonly PhoneNumberNormalizer $normalizer,
    ) {}

    public function create(Company $company, User $user, array $input): MessagingNumberClaim
    {
        $this->entitlements->assertCan($company, 'whatsapp_connect');
        $phone = $this->normalize($input);

        return DB::transaction(function () use ($company, $user, $input, $phone): MessagingNumberClaim {
            Company::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();

            $existing = MessagingNumberClaim::query()->where('phone_e164', $phone)->first();
            if ($existing && (int) $existing->company_id === (int) $company->id) {
                return $existing;
            }

            $this->assertAvailable($company, $phone);
            $this->limits->assertCanAddPhoneClaim($company);

            return MessagingNumberClaim::query()->create([
                'company_id' => $company->id,
                'created_by' => $user->id,
                'phone_e164' => $phone,
                'label' => $this->label($input),
                'connection_mode' => (string) $input['connection_mode'],
                'status' => MessagingNumberClaim::PENDING_META,
            ]);
        });
    }

    public function update(Company $company, MessagingNumberClaim $claim, array $input): MessagingNumberClaim
    {
        abort_unless((int) $claim->company_id === (int) $company->id, 404);
        if (! $claim->isUnverified()) {
            throw ValidationException::withMessages(['phone_number' => 'A Meta-verified number cannot be edited here.']);
        }

        $phone = $this->normalize($input);

        return DB::transaction(function () use ($company, $claim, $input, $phone): MessagingNumberClaim {
            Company::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $locked = MessagingNumberClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->company_id === (int) $company->id, 404);
            if (! $locked->isUnverified()) {
                throw ValidationException::withMessages(['phone_number' => 'A Meta-verified number cannot be edited here.']);
            }

            $this->assertAvailable($company, $phone, $locked->id);
            $locked->forceFill([
                'phone_e164' => $phone,
                'label' => $this->label($input),
                'connection_mode' => (string) $input['connection_mode'],
            ])->save();

            return $locked;
        });
    }

    public function delete(Company $company, MessagingNumberClaim $claim): void
    {
        abort_unless((int) $claim->company_id === (int) $company->id, 404);
        if (! $claim->isUnverified()) {
            throw ValidationException::withMessages(['phone_number' => 'A Meta-verified number cannot be removed here.']);
        }

        $claim->delete();
    }

    private function normalize(array $input): string
    {
        try {
            return $this->normalizer->normalize(
                (string) ($input['phone_number'] ?? ''),
                isset($input['country_code']) ? (string) $input['country_code'] : null,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone_number' => $exception->getMessage()]);
        }
    }

    private function assertAvailable(Company $company, string $phone, ?int $ignoreClaimId = null): void
    {
        $existingClaim = MessagingNumberClaim::query()
            ->where('phone_e164', $phone)
            ->when($ignoreClaimId, fn ($query) => $query->where('id', '!=', $ignoreClaimId))
            ->first();

        if ($existingClaim && (int) $existingClaim->company_id === (int) $company->id) {
            throw ValidationException::withMessages(['phone_number' => 'This WhatsApp number has already been added to your account.']);
        }

        if ($existingClaim || $this->verifiedPhoneBelongsElsewhere($company, $phone)) {
            throw ValidationException::withMessages(['phone_number' => 'This WhatsApp number is already assigned to another account.']);
        }

        $sameTenantVerified = MessagingPhoneNumber::query()
            ->where('phone_e164', $phone)
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->exists();
        if ($sameTenantVerified) {
            throw ValidationException::withMessages(['phone_number' => 'This WhatsApp number is already connected to your account.']);
        }
    }

    private function verifiedPhoneBelongsElsewhere(Company $company, string $phone): bool
    {
        return MessagingPhoneNumber::query()
            ->with('connection:id,company_id')
            ->whereHas('connection', fn ($query) => $query->where('company_id', '!=', $company->id))
            ->get(['id', 'messaging_connection_id', 'phone_e164', 'display_phone_number'])
            ->contains(function (MessagingPhoneNumber $candidate) use ($phone): bool {
                if ($candidate->phone_e164 === $phone) {
                    return true;
                }

                try {
                    return filled($candidate->display_phone_number)
                        && $this->normalizer->normalize((string) $candidate->display_phone_number) === $phone;
                } catch (InvalidArgumentException) {
                    return false;
                }
            });
    }

    private function label(array $input): ?string
    {
        $label = trim((string) ($input['label'] ?? ''));

        return $label === '' ? null : $label;
    }
}

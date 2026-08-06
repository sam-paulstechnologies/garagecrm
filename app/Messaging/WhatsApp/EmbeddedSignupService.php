<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionMode;
use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConsent;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingOnboardingSession;
use App\Messaging\Services\MessagingAuditService;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmbeddedSignupService
{
    public const CONSENT_VERSION = '2026-08-phase-1';

    public const CONSENT_CAPABILITIES = [
        'receive_and_store_business_messages',
        'employee_conversation_access',
        'crm_record_updates',
    ];

    public function __construct(
        private readonly MetaApiClient $meta,
        private readonly OnboardingStateService $states,
        private readonly MessagingAuditService $audit,
    ) {
    }

    public function configuration(string $mode): array
    {
        $this->assertMode($mode);

        return $this->meta->signupConfiguration($mode);
    }

    public function start(Company $company, User $user, string $mode, bool $consentAccepted): array
    {
        $this->assertMode($mode);
        if (! $consentAccepted) {
            throw new MessagingProvisioningException('consent_required', 'Accept the messaging consent before connecting WhatsApp.');
        }

        $productKey = (string) config('messaging.default_product', 'sayaraforce');
        $configuration = $this->configuration($mode);
        if (! $configuration['is_configured']) {
            throw new MessagingProvisioningException('meta_not_configured', 'This WhatsApp connection option is not configured yet.');
        }

        $issued = DB::transaction(function () use ($company, $user, $mode, $productKey): array {
            $issued = $this->states->issue((int) $company->id, (int) $user->id, $productKey, $mode);

            MessagingConsent::query()->create([
                'company_id' => $company->id,
                'messaging_onboarding_session_id' => $issued['session']->id,
                'product_key' => $productKey,
                'consent_version' => self::CONSENT_VERSION,
                'accepted_by' => $user->id,
                'accepted_at' => now(),
                'enabled_capabilities' => self::CONSENT_CAPABILITIES,
            ]);

            $this->audit->record(
                (int) $company->id,
                null,
                (int) $user->id,
                $productKey,
                'onboarding_started',
                'success',
                ['connection_mode' => $mode],
                idempotencyKey: $issued['session']->public_id,
            );

            return $issued;
        });

        return [
            'state' => $issued['state'],
            'nonce' => $issued['nonce'],
            'configuration' => $configuration,
            'expires_at' => $issued['session']->expires_at->toIso8601String(),
        ];
    }

    public function currentConnection(Company $company): ?MessagingConnection
    {
        return MessagingConnection::query()
            ->where('company_id', $company->id)
            ->where('product_key', config('messaging.default_product', 'sayaraforce'))
            ->where('provider', 'meta_whatsapp')
            ->with(['phoneNumbers', 'checks', 'audits' => fn ($query) => $query->latest('occurred_at')->limit(12)])
            ->first();
    }

    public function validateSession(
        string $state,
        string $nonce,
        Company $company,
        User $user,
    ): MessagingOnboardingSession {
        return $this->states->validate(
            $state,
            $nonce,
            (int) $company->id,
            (int) $user->id,
            (string) config('messaging.default_product', 'sayaraforce'),
        );
    }

    private function assertMode(string $mode): void
    {
        if (! in_array($mode, array_column(ConnectionMode::cases(), 'value'), true)) {
            throw new MessagingProvisioningException('invalid_mode', 'Choose a valid WhatsApp connection option.');
        }
    }
}

<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionMode;
use App\Messaging\Enums\ConnectionStatus;
use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingConsent;
use App\Messaging\Models\MessagingOnboardingSession;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Services\MessagingAuditService;
use App\Messaging\Services\TokenService;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    public function __construct(
        private readonly EmbeddedSignupService $signup,
        private readonly MetaApiClient $meta,
        private readonly SubscriptionService $subscriptions,
        private readonly PhoneRegistrationService $phoneRegistration,
        private readonly ConnectionHealthService $health,
        private readonly LegacyCompanyConnectionSynchronizer $legacy,
        private readonly TokenService $tokens,
        private readonly MessagingAuditService $audit,
    ) {
    }

    public function complete(Company $company, User $user, array $input): array
    {
        $session = $this->signup->validateSession($input['state'], $input['nonce'], $company, $user);
        if ($session->status === 'completed' && $session->messaging_connection_id) {
            return ['connection' => MessagingConnection::with(['phoneNumbers', 'checks'])->findOrFail($session->messaging_connection_id), 'idempotent' => true];
        }

        if (! $this->claimSession($session)) {
            $session->refresh();

            return [
                'connection' => MessagingConnection::with(['phoneNumbers', 'checks'])->findOrFail($session->messaging_connection_id),
                'idempotent' => true,
            ];
        }

        try {
            $this->assertSessionEvent($session->connection_mode, (string) $input['session_event']);
            $tokenPayload = $this->meta->exchangeCode((string) $input['code']);
            $token = trim((string) ($tokenPayload['access_token'] ?? ''));
            if ($token === '') {
                throw new MessagingProvisioningException('missing_token', 'Meta did not return usable access. Restart the connection.');
            }

            $inspection = $this->meta->inspectToken($token);
            $wabaId = $this->resolveWaba($inspection, $input['waba_id'] ?? null);
            $waba = $this->meta->getWaba($wabaId, $token);
            $businessId = $this->resolveBusinessId($waba, $input['business_id'] ?? null, $token);
            $phone = $this->resolvePhone($wabaId, $input['phone_number_id'] ?? null, $token, $session->connection_mode);

            $connection = $this->persistDiscoveredAssets(
                $company,
                $user,
                $session,
                $tokenPayload,
                $token,
                $wabaId,
                $businessId,
                $phone,
                (string) $input['session_event'],
            );

            $connection->forceFill(['status' => ConnectionStatus::AssigningAccess])->save();
            $systemUserAssigned = $this->meta->assignConfiguredSystemUser($wabaId);
            $this->audit->record($company->id, $connection->id, $user->id, $connection->product_key,
                'embedded_signup_access_validated', 'success', [
                    'access_source' => $systemUserAssigned ? 'configured_system_user' : 'tenant_authorization',
                ]);

            $connection->forceFill(['status' => ConnectionStatus::Subscribing, 'failure_code' => null, 'failure_message' => null])->save();
            $subscription = $this->subscriptions->ensureSubscribed($wabaId, $token);

            $connection->forceFill(['status' => ConnectionStatus::RegisteringPhone])->save();
            $registration = $this->phoneRegistration->verify((string) $phone['id'], $token, $session->connection_mode);
            if (! $registration['ready']) {
                throw new MessagingProvisioningException((string) $registration['reason'], 'The WhatsApp number still requires action in Meta.');
            }

            $connection->forceFill(['status' => ConnectionStatus::Verifying])->save();
            $health = $this->health->run($connection->fresh(['phoneNumbers']));
            if (! $health['healthy']) {
                throw new MessagingProvisioningException('health_checks_failed', 'WhatsApp was saved, but one or more required checks need attention.');
            }

            DB::transaction(function () use ($connection, $session, $user): void {
                $locked = MessagingConnection::query()->lockForUpdate()->findOrFail($connection->id);
                $phone = $locked->phoneNumbers()->where('is_primary', true)->firstOrFail();
                $this->legacy->synchronize($locked, $phone);
                $locked->forceFill([
                    'status' => ConnectionStatus::Connected,
                    'connected_at' => $locked->connected_at ?? now(),
                    'disconnected_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                    'updated_by' => $user->id,
                ])->save();
                $session->forceFill([
                    'status' => 'completed',
                    'messaging_connection_id' => $locked->id,
                    'completed_at' => now(),
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();
                MessagingConsent::query()
                    ->where('messaging_onboarding_session_id', $session->id)
                    ->update(['messaging_connection_id' => $locked->id]);
            });

            $this->audit->record($company->id, $connection->id, $user->id, $connection->product_key,
                'provisioning_completed', 'success', ['subscription_attempts' => $subscription['attempts']],
                idempotencyKey: $session->public_id);

            return ['connection' => $connection->fresh(['phoneNumbers', 'checks']), 'idempotent' => false];
        } catch (MessagingProvisioningException $exception) {
            $this->markFailure($session, $company, $user, $exception);
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('[Messaging] Provisioning failed unexpectedly', [
                'company_id' => $company->id,
                'product_key' => $session->product_key,
                'operation' => 'complete_embedded_signup',
                'exception' => $exception::class,
                'exception_file' => basename($exception->getFile()),
                'exception_line' => $exception->getLine(),
            ]);
            $safe = new MessagingProvisioningException('provisioning_failed', 'WhatsApp could not be connected. Retry safely from this page.', previous: $exception);
            $this->markFailure($session, $company, $user, $safe);
            throw $safe;
        }
    }

    public function retry(MessagingConnection $connection, User $user, bool $platformOverride = false): array
    {
        if (! $platformOverride && (int) $connection->company_id !== (int) $user->company_id) {
            abort(403);
        }

        $token = $this->tokens->retrieve($connection);
        $connection->forceFill(['status' => ConnectionStatus::Subscribing, 'updated_by' => $user->id])->save();

        try {
            $this->subscriptions->ensureSubscribed((string) $connection->waba_id, $token);
            $health = $this->health->run($connection->fresh(['phoneNumbers']));
            if (! $health['healthy']) {
                throw new MessagingProvisioningException('health_checks_failed', 'Required WhatsApp checks still need attention.');
            }

            DB::transaction(function () use ($connection, $user): void {
                $locked = MessagingConnection::query()->lockForUpdate()->findOrFail($connection->id);
                $phone = $locked->phoneNumbers()->where('is_primary', true)->firstOrFail();
                $this->legacy->synchronize($locked, $phone);
                $locked->forceFill([
                    'status' => ConnectionStatus::Connected,
                    'connected_at' => $locked->connected_at ?? now(),
                    'failure_code' => null,
                    'failure_message' => null,
                    'updated_by' => $user->id,
                ])->save();
            });

            $this->audit->record($connection->company_id, $connection->id, $user->id, $connection->product_key, 'provisioning_retried', 'success');
            return $this->health->run($connection->fresh(['phoneNumbers']));
        } catch (MessagingProvisioningException $exception) {
            $connection->forceFill([
                'status' => ConnectionStatus::RequiresAction,
                'failure_code' => $exception->reason,
                'failure_message' => $exception->getMessage(),
            ])->save();
            $this->audit->record($connection->company_id, $connection->id, $user->id, $connection->product_key,
                'provisioning_retried', 'failed', $exception->safeContext, $exception->reason);
            throw $exception;
        }
    }

    private function claimSession(MessagingOnboardingSession $session): bool
    {
        $claimed = DB::transaction(function () use ($session): bool {
            $locked = MessagingOnboardingSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status === 'completed') {
                return false;
            }
            if ($locked->status === 'processing' && $locked->last_attempted_at?->gt(now()->subMinutes(2))) {
                throw new MessagingProvisioningException('callback_in_progress', 'This connection is already being completed. Wait a moment and refresh.');
            }
            $locked->forceFill([
                'status' => 'processing',
                'last_attempted_at' => now(),
                'attempt_count' => $locked->attempt_count + 1,
            ])->save();

            return true;
        });
        $session->refresh();

        return $claimed;
    }

    private function resolveWaba(array $inspection, ?string $supplied): string
    {
        $targets = [];
        foreach ((array) ($inspection['granular_scopes'] ?? []) as $scope) {
            if (($scope['scope'] ?? null) === 'whatsapp_business_management') {
                $targets = array_merge($targets, array_map('strval', (array) ($scope['target_ids'] ?? [])));
            }
        }
        $targets = array_values(array_unique(array_filter($targets)));
        if (filled($supplied) && in_array((string) $supplied, $targets, true)) {
            return (string) $supplied;
        }
        if (count($targets) === 1) {
            return $targets[0];
        }

        throw new MessagingProvisioningException('waba_not_shared', 'Meta did not share exactly one WhatsApp account with SayaraForce.');
    }

    private function resolveBusinessId(array $waba, ?string $supplied, string $token): ?string
    {
        $ownerId = (string) (data_get($waba, 'owner_business_info.id') ?? '');
        $candidate = trim((string) ($supplied ?: $ownerId));
        if ($candidate === '') {
            return null;
        }
        $business = $this->meta->getBusiness($candidate, $token);
        if ((string) ($business['id'] ?? '') !== $candidate || ($ownerId !== '' && $ownerId !== $candidate)) {
            throw new MessagingProvisioningException('business_mismatch', 'The selected Meta business does not own this WhatsApp account.');
        }
        return $candidate;
    }

    private function resolvePhone(string $wabaId, ?string $supplied, string $token, string $mode): array
    {
        $phones = $this->meta->getPhoneNumbers($wabaId, $token);
        $phone = filled($supplied)
            ? collect($phones)->firstWhere('id', (string) $supplied)
            : (count($phones) === 1 ? $phones[0] : null);
        if (! is_array($phone)) {
            throw new MessagingProvisioningException('phone_not_shared', 'Meta did not share exactly one eligible WhatsApp number.');
        }
        $isOnBusinessApp = filter_var($phone['is_on_biz_app'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($mode === ConnectionMode::BusinessApp->value && $isOnBusinessApp !== true) {
            throw new MessagingProvisioningException('coexistence_not_eligible', 'Meta did not confirm that this number remains in the WhatsApp Business app.');
        }
        if ($mode === ConnectionMode::CloudApi->value && $isOnBusinessApp === true) {
            throw new MessagingProvisioningException('wrong_onboarding_mode', 'Use the existing Business app option for this number.');
        }
        return $phone;
    }

    private function persistDiscoveredAssets(Company $company, User $user, MessagingOnboardingSession $session, array $tokenPayload, string $token, string $wabaId, ?string $businessId, array $phone, string $sessionEvent): MessagingConnection
    {
        $conflict = MessagingPhoneNumber::query()
            ->where('provider', 'meta_whatsapp')
            ->where('phone_number_id', (string) $phone['id'])
            ->whereHas('connection', fn ($query) => $query->where('company_id', '!=', $company->id))
            ->exists();
        if ($conflict) {
            throw new MessagingProvisioningException('number_already_connected', 'This WhatsApp number is already connected to another account.');
        }

        return DB::transaction(function () use ($company, $user, $session, $tokenPayload, $token, $wabaId, $businessId, $phone, $sessionEvent): MessagingConnection {
            $connection = MessagingConnection::query()->lockForUpdate()->firstOrNew([
                'company_id' => $company->id,
                'product_key' => $session->product_key,
                'provider' => 'meta_whatsapp',
            ]);
            if (! $connection->exists) {
                $connection->created_by = $user->id;
            }
            $connection->forceFill([
                'status' => ConnectionStatus::DiscoveringAssets,
                'connection_mode' => $session->connection_mode,
                'meta_business_id' => $businessId,
                'waba_id' => $wabaId,
                'external_account_id' => $wabaId,
                'token_expires_at' => filled($tokenPayload['expires_in'] ?? null) ? now()->addSeconds((int) $tokenPayload['expires_in']) : null,
                'updated_by' => $user->id,
                'disconnected_at' => null,
                'metadata' => ['token_type' => $tokenPayload['token_type'] ?? null],
            ]);
            $this->tokens->store($connection, $token);
            $connection->save();

            $phoneModel = MessagingPhoneNumber::query()->updateOrCreate(
                ['provider' => 'meta_whatsapp', 'phone_number_id' => (string) $phone['id']],
                [
                    'messaging_connection_id' => $connection->id,
                    'display_phone_number' => $phone['display_phone_number'] ?? null,
                    'verified_name' => $phone['verified_name'] ?? null,
                    'display_name_status' => $phone['name_status'] ?? null,
                    'quality_rating' => $phone['quality_rating'] ?? null,
                    'registration_status' => $phone['status'] ?? $phone['code_verification_status'] ?? null,
                    'coexistence_status' => filter_var($phone['is_on_biz_app'] ?? false, FILTER_VALIDATE_BOOL) ? 'connected' : 'not_applicable',
                    'is_primary' => true,
                    'metadata' => ['platform_type' => $phone['platform_type'] ?? null],
                ],
            );
            MessagingPhoneNumber::query()->where('messaging_connection_id', $connection->id)->where('id', '!=', $phoneModel->id)->update(['is_primary' => false]);

            $session->forceFill([
                'messaging_connection_id' => $connection->id,
                'session_event' => $sessionEvent,
                'metadata' => array_merge((array) $session->metadata, [
                    'assets_discovered' => true,
                    'waba_shared' => true,
                    'phone_shared' => true,
                ]),
            ])->save();

            MessagingConsent::query()->where('messaging_onboarding_session_id', $session->id)->update(['messaging_connection_id' => $connection->id]);
            return $connection;
        });
    }

    private function assertSessionEvent(string $mode, string $event): void
    {
        $valid = $mode === ConnectionMode::BusinessApp->value
            ? $event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING'
            : in_array($event, ['FINISH', 'FINISH_ONLY_WABA'], true);
        if (! $valid) {
            throw new MessagingProvisioningException('wrong_signup_flow', 'Meta returned a different onboarding flow than the one selected. Nothing was connected.');
        }
    }

    private function markFailure(MessagingOnboardingSession $session, Company $company, User $user, MessagingProvisioningException $exception): void
    {
        $session->forceFill(['status' => 'failed', 'failure_code' => $exception->reason, 'failure_message' => $exception->getMessage()])->save();
        if ($session->messaging_connection_id) {
            MessagingConnection::query()->whereKey($session->messaging_connection_id)->update([
                'status' => ConnectionStatus::RequiresAction->value,
                'failure_code' => $exception->reason,
                'failure_message' => $exception->getMessage(),
                'updated_by' => $user->id,
            ]);
        }
        $this->audit->record($company->id, $session->messaging_connection_id, $user->id, $session->product_key,
            'provisioning_failed', 'failed', $exception->safeContext, $exception->reason,
            $session->attempt_count, $session->public_id);
    }
}

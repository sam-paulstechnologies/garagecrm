<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionMode;
use App\Messaging\Enums\HealthCheckStatus;
use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingConnectionCheck;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Services\TokenService;
use Illuminate\Support\Facades\Route;

class ConnectionHealthService
{
    private const MANDATORY = [
        'token_decryption', 'token_validity', 'waba_access', 'app_subscription',
        'phone_access', 'phone_registration', 'webhook_fields', 'tenant_mapping', 'webhook_route',
    ];

    public function __construct(
        private readonly MetaApiClient $meta,
        private readonly SubscriptionService $subscriptions,
        private readonly PhoneRegistrationService $phoneRegistration,
        private readonly TokenService $tokens,
    ) {
    }

    public function run(MessagingConnection $connection): array
    {
        $connection->loadMissing('phoneNumbers');
        $phone = $connection->phoneNumbers->firstWhere('is_primary', true) ?? $connection->phoneNumbers->first();
        $results = [];
        $token = null;

        $results['token_decryption'] = $this->capture($connection, 'token_decryption', function () use ($connection, &$token): array {
            $token = $this->tokens->retrieve($connection);
            return ['status' => HealthCheckStatus::Passed, 'summary' => 'Stored authorization can be read securely.'];
        });

        if ($token !== null) {
            $results['token_validity'] = $this->capture($connection, 'token_validity', function () use ($token): array {
                $this->meta->inspectToken($token);
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'Meta authorization is valid for this application.'];
            });
            $results['waba_access'] = $this->capture($connection, 'waba_access', function () use ($connection, $token): array {
                $this->meta->getWaba((string) $connection->waba_id, $token);
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'WhatsApp Business Account is accessible.'];
            });
            $results['app_subscription'] = $this->capture($connection, 'app_subscription', function () use ($connection, $token): array {
                if (! $this->subscriptions->isSubscribed((string) $connection->waba_id, $token)) {
                    throw new MessagingProvisioningException('app_not_subscribed', 'Meta has not confirmed webhook delivery for this account.');
                }
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'SayaraForce is subscribed to the WhatsApp account.'];
            });
            $results['phone_access'] = $this->capture($connection, 'phone_access', function () use ($phone, $token): array {
                if (! $phone) {
                    throw new MessagingProvisioningException('missing_phone', 'No WhatsApp number is mapped to this connection.');
                }
                $this->meta->getPhone($phone->phone_number_id, $token);
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'Connected WhatsApp number is accessible.'];
            });
            $results['phone_registration'] = $this->capture($connection, 'phone_registration', function () use ($connection, $phone, $token): array {
                if (! $phone) {
                    throw new MessagingProvisioningException('missing_phone', 'No WhatsApp number is mapped to this connection.');
                }
                $registration = $this->phoneRegistration->verify($phone->phone_number_id, $token, (string) $connection->connection_mode);
                if (! $registration['ready']) {
                    throw new MessagingProvisioningException((string) $registration['reason'], 'The WhatsApp number still requires action in Meta.');
                }
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'WhatsApp number registration is ready.'];
            });
            $results['webhook_fields'] = $this->capture($connection, 'webhook_fields', function (): array {
                $configured = $this->meta->getAppWebhookFields();
                $required = (array) config('messaging.providers.meta_whatsapp.required_webhook_fields', []);
                $missing = array_values(array_diff($required, $configured));
                if ($missing !== []) {
                    throw new MessagingProvisioningException('missing_webhook_fields', 'Required WhatsApp webhook fields are not enabled.', [
                        'missing_fields' => $missing,
                    ]);
                }
                return ['status' => HealthCheckStatus::Passed, 'summary' => 'Required WhatsApp webhook fields are enabled.'];
            });
        } else {
            foreach (['token_validity', 'waba_access', 'app_subscription', 'phone_access', 'phone_registration', 'webhook_fields'] as $key) {
                $results[$key] = $this->persist($connection, $key, HealthCheckStatus::Failed, 'Check could not run without valid authorization.');
            }
        }

        $results['tenant_mapping'] = $this->capture($connection, 'tenant_mapping', function () use ($connection, $phone): array {
            if (! $phone || (int) $connection->company_id < 1) {
                throw new MessagingProvisioningException('tenant_mapping_missing', 'The WhatsApp number is not mapped to a garage.');
            }
            $conflicts = MessagingPhoneNumber::query()
                ->where('provider', $phone->provider)
                ->where('phone_number_id', $phone->phone_number_id)
                ->where('id', '!=', $phone->id)
                ->count();
            if ($conflicts > 0) {
                throw new MessagingProvisioningException('tenant_mapping_conflict', 'The WhatsApp number is mapped more than once.');
            }
            return ['status' => HealthCheckStatus::Passed, 'summary' => 'Provider assets map to exactly one garage.'];
        });

        $results['webhook_route'] = Route::has('api.webhooks.meta.whatsapp.handle')
            ? $this->persist($connection, 'webhook_route', HealthCheckStatus::Passed, 'Signed webhook route is available.')
            : $this->persist($connection, 'webhook_route', HealthCheckStatus::Failed, 'Signed webhook route is unavailable.');

        $results['coexistence'] = $connection->connection_mode === ConnectionMode::BusinessApp->value
            ? $this->persist(
                $connection,
                'coexistence',
                ($phone?->coexistence_status === 'connected') ? HealthCheckStatus::Passed : HealthCheckStatus::Warning,
                ($phone?->coexistence_status === 'connected')
                    ? 'WhatsApp Business app coexistence is confirmed.'
                    : 'Meta has not yet confirmed WhatsApp Business app coexistence.',
            )
            : $this->persist($connection, 'coexistence', HealthCheckStatus::Passed, 'Dedicated Cloud API mode does not require coexistence.');

        $legacy = $connection->company;
        $results['last_inbound'] = $this->persist(
            $connection,
            'last_inbound',
            $legacy?->whatsapp_last_inbound_at ? HealthCheckStatus::Passed : HealthCheckStatus::Pending,
            $legacy?->whatsapp_last_inbound_at ? 'An inbound message has been received.' : 'No inbound message has been received yet.',
        );
        $results['last_outbound'] = $this->persist(
            $connection,
            'last_outbound',
            HealthCheckStatus::Pending,
            'Outbound delivery is verified after the first staff reply.',
        );

        $healthy = collect(self::MANDATORY)->every(
            fn (string $key): bool => ($results[$key]['status'] ?? null) === HealthCheckStatus::Passed->value
        );

        $connection->forceFill(['last_verified_at' => now()])->save();
        if ($phone) {
            $phone->forceFill(['last_health_check_at' => now()])->save();
        }

        return ['healthy' => $healthy, 'checks' => $results];
    }

    private function capture(MessagingConnection $connection, string $key, callable $callback): array
    {
        try {
            $result = $callback();
            return $this->persist($connection, $key, $result['status'], $result['summary']);
        } catch (MessagingProvisioningException $exception) {
            return $this->persist(
                $connection,
                $key,
                HealthCheckStatus::Failed,
                $exception->getMessage(),
                $exception->reason,
                $exception->safeContext,
            );
        } catch (\Throwable) {
            return $this->persist($connection, $key, HealthCheckStatus::Failed, 'The check could not be completed.', 'unexpected_failure');
        }
    }

    private function persist(
        MessagingConnection $connection,
        string $key,
        HealthCheckStatus $status,
        string $summary,
        ?string $providerErrorCode = null,
        array $metadata = [],
    ): array {
        MessagingConnectionCheck::query()->updateOrCreate(
            ['messaging_connection_id' => $connection->id, 'check_key' => $key],
            [
                'status' => $status->value,
                'summary' => $summary,
                'provider_error_code' => $providerErrorCode,
                'metadata' => $metadata,
                'checked_at' => now(),
            ],
        );

        return ['status' => $status->value, 'summary' => $summary, 'code' => $providerErrorCode];
    }
}

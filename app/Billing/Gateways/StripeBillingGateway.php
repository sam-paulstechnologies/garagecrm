<?php

namespace App\Billing\Gateways;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Data\CheckoutResult;
use App\Billing\Data\NormalizedBillingEvent;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Data\ProviderSubscription;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class StripeBillingGateway implements BillingGateway
{
    public const SUPPORTED_API_VERSION = '2026-07-29.dahlia';

    public function provider(): string
    {
        return 'stripe';
    }

    public function createCustomer(Company $company, string $idempotencyKey): ProviderCustomer
    {
        $payload = array_filter([
            'name' => $company->name,
            'email' => $company->email,
            'metadata[company_id]' => (string) $company->id,
        ], fn ($value) => filled($value));
        $data = $this->request($idempotencyKey)->post('/v1/customers', $payload)->throw()->json();

        return new ProviderCustomer((string) Arr::get($data, 'id'));
    }

    public function createCheckout(ProviderCustomer $customer, PriceProviderMapping $mapping, string $successUrl, string $cancelUrl, string $idempotencyKey, array $metadata = []): CheckoutResult
    {
        $payload = [
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items[0][price]' => $mapping->provider_price_id,
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'allow_promotion_codes' => 'false',
        ];
        foreach ($metadata as $key => $value) {
            if ($value !== null) {
                $payload['metadata['.$key.']'] = (string) $value;
                $payload['subscription_data[metadata]['.$key.']'] = (string) $value;
            }
        }
        $data = $this->request($idempotencyKey)->post('/v1/checkout/sessions', $payload)->throw()->json();

        return new CheckoutResult(
            (string) Arr::get($data, 'id'),
            (string) Arr::get($data, 'url'),
            $this->timestamp(Arr::get($data, 'expires_at')),
        );
    }

    public function createSubscription(ProviderCustomer $customer, PriceProviderMapping $mapping, string $idempotencyKey): ProviderSubscription
    {
        $data = $this->request($idempotencyKey)->post('/v1/subscriptions', [
            'customer' => $customer->id,
            'items[0][price]' => $mapping->provider_price_id,
            'payment_behavior' => 'default_incomplete',
        ])->throw()->json();

        return $this->subscriptionFrom((array) $data);
    }

    public function cancelSubscription(string $providerSubscriptionId, bool $atPeriodEnd = true): ProviderSubscription
    {
        $request = $this->request();
        $response = $atPeriodEnd
            ? $request->post('/v1/subscriptions/'.$this->segment($providerSubscriptionId), ['cancel_at_period_end' => 'true'])
            : $request->delete('/v1/subscriptions/'.$this->segment($providerSubscriptionId));

        return $this->subscriptionFrom((array) $response->throw()->json());
    }

    public function changeSubscription(string $providerSubscriptionId, PriceProviderMapping $mapping, string $idempotencyKey, array $metadata = []): ProviderSubscription
    {
        $current = $this->request()->get('/v1/subscriptions/'.$this->segment($providerSubscriptionId), ['expand[]' => 'items'])->throw()->json();
        $itemId = (string) Arr::get($current, 'items.data.0.id');
        if ($itemId === '') {
            throw new BillingConfigurationException('Stripe subscription item could not be resolved.');
        }
        $payload = [
            'items[0][id]' => $itemId,
            'items[0][price]' => $mapping->provider_price_id,
            'proration_behavior' => 'create_prorations',
        ];
        foreach ($metadata as $key => $value) {
            if ($value !== null) {
                $payload['metadata['.$key.']'] = (string) $value;
            }
        }
        $data = $this->request($idempotencyKey)->post('/v1/subscriptions/'.$this->segment($providerSubscriptionId), $payload)->throw()->json();

        return $this->subscriptionFrom((array) $data);
    }

    public function retrieveSubscription(string $providerSubscriptionId): ProviderSubscription
    {
        $data = $this->request()->get('/v1/subscriptions/'.$this->segment($providerSubscriptionId))->throw()->json();

        return $this->subscriptionFrom((array) $data);
    }

    public function createBillingPortal(string $providerCustomerId, string $returnUrl): string
    {
        $data = $this->request()->post('/v1/billing_portal/sessions', [
            'customer' => $providerCustomerId,
            'return_url' => $returnUrl,
        ])->throw()->json();

        return (string) Arr::get($data, 'url');
    }

    public function verifyWebhook(string $payload, string $signature): void
    {
        $this->assertSandboxConfiguration(requireSecretKey: false);
        $secret = (string) config('billing.stripe.webhook_secret');
        if (! str_starts_with($secret, 'whsec_')) {
            throw new InvalidBillingWebhook('Stripe webhook verification is not configured.');
        }

        $parts = collect(explode(',', $signature))->reduce(function (array $parts, string $part): array {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key && $value) {
                $parts[$key][] = $value;
            }

            return $parts;
        }, []);
        $timestamp = (int) ($parts['t'][0] ?? 0);
        $candidates = $parts['v1'] ?? [];
        $tolerance = (int) config('billing.stripe.webhook_tolerance', 300);
        if ($timestamp <= 0 || abs(now()->timestamp - $timestamp) > $tolerance) {
            throw new InvalidBillingWebhook('Stripe webhook timestamp is outside the allowed tolerance.');
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $verified = collect($candidates)->contains(
            fn (string $candidate): bool => hash_equals($expected, $candidate),
        );
        if (! $verified) {
            throw new InvalidBillingWebhook('Stripe webhook signature is invalid.');
        }
    }

    public function normalizeEvent(string $payload): NormalizedBillingEvent
    {
        try {
            $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidBillingWebhook('Stripe webhook JSON is invalid.', previous: $exception);
        }
        if (Arr::get($event, 'livemode') !== false) {
            throw new InvalidBillingWebhook('Stripe live-mode or unclassified events are forbidden in this release.');
        }
        $expectedVersion = (string) config('billing.stripe.api_version');
        $eventVersion = (string) Arr::get($event, 'api_version');
        if ($expectedVersion === '' || $eventVersion !== $expectedVersion) {
            throw new InvalidBillingWebhook(sprintf(
                'Stripe webhook API version is incompatible; expected %s and received %s.',
                $expectedVersion !== '' ? $expectedVersion : '<unconfigured>',
                $eventVersion !== '' ? $eventVersion : '<missing>',
            ));
        }
        $id = (string) Arr::get($event, 'id');
        $type = (string) Arr::get($event, 'type');
        $object = (array) Arr::get($event, 'data.object', []);
        $objectId = (string) Arr::get($object, 'id');
        $objectType = (string) Arr::get($object, 'object');
        $created = Arr::get($event, 'created');
        if (Arr::get($event, 'object') !== 'event' || $id === '' || $type === ''
            || ! is_numeric($created) || (int) $created <= 0 || $objectId === '' || $objectType === '') {
            throw new InvalidBillingWebhook('Stripe webhook envelope is incomplete.');
        }

        $metadata = array_merge(
            (array) Arr::get($object, 'subscription_details.metadata', []),
            (array) Arr::get($object, 'parent.subscription_details.metadata', []),
            (array) Arr::get($object, 'metadata', []),
        );
        $subscriptionId = Arr::get($object, 'subscription')
            ?? Arr::get($object, 'parent.subscription_details.subscription')
            ?? ($objectType === 'subscription' ? $objectId : null);
        $priceId = Arr::get($object, 'items.data.0.price.id')
            ?? Arr::get($object, 'lines.data.0.price.id')
            ?? Arr::get($object, 'lines.data.0.pricing.price_details.price')
            ?? Arr::get($metadata, 'provider_price_id');
        $periodStart = Arr::get($object, 'current_period_start')
            ?? Arr::get($object, 'items.data.0.current_period_start')
            ?? Arr::get($object, 'lines.data.0.period.start');
        $periodEnd = Arr::get($object, 'current_period_end')
            ?? Arr::get($object, 'items.data.0.current_period_end')
            ?? Arr::get($object, 'lines.data.0.period.end');

        $data = array_filter([
            'local_checkout_id' => $metadata['local_checkout_id'] ?? null,
            'provider_customer_id' => Arr::get($object, 'customer'),
            'provider_subscription_id' => $subscriptionId,
            'provider_price_id' => $priceId,
            'provider_invoice_id' => $objectType === 'invoice' ? $objectId : null,
            'subscription_status' => $objectType === 'subscription' ? Arr::get($object, 'status') : null,
            'payment_status' => Arr::get($object, 'payment_status') ?? Arr::get($object, 'status'),
            'cancel_at_period_end' => Arr::get($object, 'cancel_at_period_end'),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'currency' => Arr::get($object, 'currency'),
            'amount_due_minor' => Arr::get($object, 'amount_due'),
            'amount_paid_minor' => Arr::get($object, 'amount_paid'),
            'due_at' => Arr::get($object, 'due_date'),
            'paid_at' => Arr::get($object, 'status_transitions.paid_at'),
            'hosted_invoice_url' => Arr::get($object, 'hosted_invoice_url'),
            'test_mode' => ! (bool) Arr::get($event, 'livemode', true),
        ], fn ($value) => $value !== null && $value !== '');
        $this->assertSupportedEventShape($type, $objectType, $data);

        return new NormalizedBillingEvent(
            $id,
            $type,
            CarbonImmutable::createFromTimestampUTC((int) $created),
            $objectType,
            $objectId,
            $data,
        );
    }

    private function request(?string $idempotencyKey = null): PendingRequest
    {
        $this->assertSandboxConfiguration();
        $secret = (string) config('billing.stripe.secret_key');
        $request = Http::baseUrl((string) config('billing.stripe.api_base', 'https://api.stripe.com'))
            ->asForm()
            ->acceptJson()
            ->withHeaders(['Stripe-Version' => (string) config('billing.stripe.api_version')])
            ->withBasicAuth($secret, '')
            ->timeout(30);

        return $idempotencyKey ? $request->withHeaders(['Idempotency-Key' => $idempotencyKey]) : $request;
    }

    public function assertSandboxConfiguration(bool $requireSecretKey = true): void
    {
        if (config('billing.mode') !== 'test') {
            throw new BillingConfigurationException('Stripe adapter refuses non-test billing mode in this release.');
        }

        $secret = (string) config('billing.stripe.secret_key');
        if (($requireSecretKey || $secret !== '') && ! str_starts_with($secret, 'sk_test_')) {
            throw new BillingConfigurationException('Stripe adapter refuses non-test secret credentials in this release.');
        }

        $publishable = (string) config('billing.stripe.publishable_key');
        if ($publishable !== '' && ! str_starts_with($publishable, 'pk_test_')) {
            throw new BillingConfigurationException('Stripe adapter refuses non-test publishable credentials in this release.');
        }

        $apiBase = rtrim((string) config('billing.stripe.api_base', 'https://api.stripe.com'), '/');
        if (app()->environment('staging') && $apiBase !== 'https://api.stripe.com') {
            throw new BillingConfigurationException('Stripe staging API base must use the official Stripe endpoint.');
        }
        if ((string) config('billing.stripe.api_version') !== self::SUPPORTED_API_VERSION) {
            throw new BillingConfigurationException('Stripe API version must match the reviewed '.self::SUPPORTED_API_VERSION.' fixture contract.');
        }
    }

    /** @param array<string, scalar|null> $data */
    private function assertSupportedEventShape(string $type, string $objectType, array $data): void
    {
        $expectedObject = match ($type) {
            'checkout.session.completed', 'checkout.session.expired' => 'checkout.session',
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => 'subscription',
            'invoice.paid', 'invoice.payment_succeeded', 'invoice.payment_failed' => 'invoice',
            default => null,
        };
        if ($expectedObject === null) {
            return;
        }
        if ($objectType !== $expectedObject) {
            throw new InvalidBillingWebhook("Stripe {$type} must contain a {$expectedObject} object.");
        }

        $required = match ($type) {
            'checkout.session.completed' => [
                'local_checkout_id', 'provider_customer_id', 'provider_subscription_id',
                'provider_price_id', 'payment_status',
            ],
            'checkout.session.expired' => ['local_checkout_id', 'provider_price_id'],
            'customer.subscription.created' => [
                'local_checkout_id', 'provider_customer_id', 'provider_subscription_id',
                'provider_price_id', 'subscription_status', 'period_start', 'period_end',
            ],
            'customer.subscription.updated' => [
                'provider_customer_id', 'provider_subscription_id', 'provider_price_id',
                'subscription_status', 'period_start', 'period_end',
            ],
            'customer.subscription.deleted' => ['provider_subscription_id', 'subscription_status'],
            'invoice.paid', 'invoice.payment_succeeded' => [
                'provider_customer_id', 'provider_subscription_id', 'provider_price_id',
                'provider_invoice_id', 'payment_status', 'currency', 'amount_due_minor',
                'amount_paid_minor', 'period_start', 'period_end', 'paid_at',
            ],
            'invoice.payment_failed' => [
                'provider_customer_id', 'provider_subscription_id', 'provider_price_id',
                'provider_invoice_id', 'payment_status', 'currency', 'amount_due_minor',
                'amount_paid_minor', 'period_start', 'period_end',
            ],
            default => [],
        };
        foreach ($required as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                throw new InvalidBillingWebhook("Stripe {$type} is missing required {$key} data.");
            }
        }

        if (isset($data['provider_price_id'])
            && ! preg_match('/^price_[A-Za-z0-9]{8,}$/', (string) $data['provider_price_id'])) {
            throw new InvalidBillingWebhook("Stripe {$type} contains an invalid Price identifier.");
        }
        if (isset($data['currency']) && strtolower((string) $data['currency']) !== 'aed') {
            throw new InvalidBillingWebhook("Stripe {$type} contains a non-AED invoice.");
        }
    }

    /** @param array<string, mixed> $data */
    private function subscriptionFrom(array $data): ProviderSubscription
    {
        return new ProviderSubscription(
            (string) Arr::get($data, 'id'),
            (string) Arr::get($data, 'customer'),
            (string) Arr::get($data, 'items.data.0.price.id'),
            (string) Arr::get($data, 'status'),
            $this->timestamp(Arr::get($data, 'current_period_start')),
            $this->timestamp(Arr::get($data, 'current_period_end')),
            (bool) Arr::get($data, 'cancel_at_period_end', false),
        );
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        return is_numeric($value) ? CarbonImmutable::createFromTimestampUTC((int) $value) : null;
    }

    private function segment(string $value): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $value)) {
            throw new BillingConfigurationException('Provider identifier has an invalid format.');
        }

        return $value;
    }
}

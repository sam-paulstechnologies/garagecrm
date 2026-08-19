<?php

namespace App\Providers;

use App\Billing\BillingGatewayResolver;
use App\Billing\Contracts\BillingGateway;
use App\Commercial\EntitlementService;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Services\ProductAdapterRegistry;
use App\Models\MessageLog;
use App\Models\System\Company;
// ✅ R2 Observer wiring
use App\Observers\MessageLogObserver;
use App\SayaraForce\Messaging\SayaraForceMessagingAdapter;
use App\Support\Staging\StagingSafety;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Keep the existing Breeze-style controllers and use Fortify only for
        // its audited TOTP, recovery-code, encryption, and QR primitives.
        Fortify::ignoreRoutes();

        // M7: shared tenant context for the isolation backstop global scope.
        $this->app->singleton(\App\Support\Tenancy\TenantContext::class);

        $this->app->singleton(BillingGateway::class, fn ($app): BillingGateway => $app->make(BillingGatewayResolver::class)->configured()
        );

        // AI services singletons
        $this->app->singleton(\App\Services\Ai\NlpService::class);

        if (class_exists(\App\Services\Ai\ActionSuggestService::class)) {
            $this->app->singleton(\App\Services\Ai\ActionSuggestService::class);
        }

        $this->app->singleton(ProductAdapterRegistry::class, function (): ProductAdapterRegistry {
            $registry = new ProductAdapterRegistry;
            $registry->register('sayaraforce', SayaraForceMessagingAdapter::class);

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('entitled', function (string $capability): bool {
            $company = auth()->user()?->company;

            return $company ? app(EntitlementService::class)->can($company, $capability) : false;
        });

        /*
        |--------------------------------------------------------------------------
        | Keep Vite assets on the current request host
        |--------------------------------------------------------------------------
        | SayaraForce serves the public site and authenticated application from
        | separate hostnames. Absolute Vite URLs derived from APP_URL cause ES
        | modules requested by sayaraforce.com to cross into app.sayaraforce.com,
        | where static files are not CORS-enabled. Root-relative build paths keep
        | each request on its own origin while still using the same manifest.
        */
        Vite::createAssetPathsUsing(
            static fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/')
        );

        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in production
        |--------------------------------------------------------------------------
        | Azure terminates SSL before the request reaches Laravel.
        | Without this, Laravel may generate http:// URLs and browser blocks login.
        */
        if (app()->environment(['production', 'staging'])) {
            URL::forceScheme('https');
        }

        // Fable H2: surface a degraded/critical MFA-enforcement baseline once at
        // boot so monitoring notices a protected environment that lost its
        // TWO_FACTOR_ENFORCEMENT setting (which now fails closed rather than
        // silently disabling privileged MFA). No secrets are logged.
        $securityConfig = app(\App\Security\SecurityConfigurationValidator::class);
        if ($securityConfig->status() !== 'ok') {
            $level = $securityConfig->status() === 'critical' ? 'critical' : 'warning';
            Log::log($level, 'Security configuration baseline degraded.', $securityConfig->readiness());
        }

        // Fable M31: staging safety hooks must NOT hinge on a single APP_ENV
        // string. Register them whenever the multi-signal staging guard is active
        // (APP_ENV=staging, STAGING_SAFETY_ENFORCED, matching staging host/DB
        // identity, or an ambiguous/mislabeled environment that fails closed).
        // An APP_ENV drift can no longer silently drop the email recipient
        // allowlist or the provider-asset save guards.
        if (app(StagingSafety::class)->outboundGuardActive()) {
            Log::withContext(['environment' => app()->environment()]);

            Company::saving(function (Company $company): void {
                app(StagingSafety::class)->assertProviderAssetsAllowed(
                    $company->meta_waba_id,
                    $company->meta_phone_number_id
                );
            });

            MessagingConnection::saving(function (MessagingConnection $connection): void {
                app(StagingSafety::class)->assertProviderAssetsAllowed($connection->waba_id, null);
            });

            MessagingPhoneNumber::saving(function (MessagingPhoneNumber $phone): void {
                app(StagingSafety::class)->assertProviderAssetsAllowed(null, $phone->phone_number_id);
            });

            Event::listen(MessageSending::class, function (MessageSending $event): ?bool {
                $addresses = collect([
                    ...$event->message->getTo(),
                    ...$event->message->getCc(),
                    ...$event->message->getBcc(),
                ])->map(fn ($address): string => method_exists($address, 'getAddress')
                    ? $address->getAddress()
                    : (string) $address)->all();

                if (! app(StagingSafety::class)->emailRecipientsAreAllowed($addresses)) {
                    Log::warning('Staging email blocked by recipient allowlist.', [
                        'environment' => 'staging',
                        'recipient_count' => count($addresses),
                    ]);

                    return false;
                }

                return null;
            });
        }

        Vite::prefetch(concurrency: 3);

        // ✅ R2: Auto-generate AI suggestions when inbound messages are logged
        if (class_exists(MessageLog::class) && class_exists(MessageLogObserver::class)) {
            MessageLog::observe(MessageLogObserver::class);
        }
    }
}

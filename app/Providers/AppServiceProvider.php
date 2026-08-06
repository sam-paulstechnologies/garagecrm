<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSending;

// ✅ R2 Observer wiring
use App\Models\MessageLog;
use App\Observers\MessageLogObserver;
use App\Messaging\Services\ProductAdapterRegistry;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\System\Company;
use App\SayaraForce\Messaging\SayaraForceMessagingAdapter;
use App\Support\Staging\StagingSafety;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AI services singletons
        $this->app->singleton(\App\Services\Ai\NlpService::class);

        if (class_exists(\App\Services\Ai\ActionSuggestService::class)) {
            $this->app->singleton(\App\Services\Ai\ActionSuggestService::class);
        }

        $this->app->singleton(ProductAdapterRegistry::class, function (): ProductAdapterRegistry {
            $registry = new ProductAdapterRegistry();
            $registry->register('sayaraforce', SayaraForceMessagingAdapter::class);

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        if (app()->environment('staging')) {
            Log::withContext(['environment' => 'staging']);

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

            Event::listen(MessageSending::class, function (MessageSending $event): bool|null {
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

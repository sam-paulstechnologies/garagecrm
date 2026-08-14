<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\EmailInboundWebhookController;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Jobs\Middleware\EnforceAiPolicy;
use App\Logging\RedactSensitiveLogs;
use App\Models\Client\Client;
use App\Models\Client\ClientDocument;
use App\Models\MetaPage;
use App\Models\Settings\CompanySetting;
use App\Models\System\Company;
use App\Models\User;
use App\Security\Uploads\PrivateUploadStorage;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_applied_without_exposing_framework_details(): void
    {
        $response = $this->get('https://localhost/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Strict-Transport-Security')
            ->assertHeader('Content-Security-Policy');

        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_log_processor_redacts_provider_and_authentication_secrets(): void
    {
        $handler = new TestHandler;
        $logger = new Logger('security-test', [$handler]);
        (new RedactSensitiveLogs)($logger);

        $logger->warning('Bearer sensitive-token-value', [
            'password' => 'do-not-log',
            'nested' => ['access_token' => 'provider-token'],
            'safe' => 'retained',
        ]);

        $record = $handler->getRecords()[0];
        $encoded = json_encode([$record->message, $record->context], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('sensitive-token-value', $encoded);
        $this->assertStringNotContainsString('do-not-log', $encoded);
        $this->assertStringNotContainsString('provider-token', $encoded);
        $this->assertStringContainsString('retained', $encoded);
    }

    public function test_private_upload_uses_magic_mime_and_never_generates_a_public_url(): void
    {
        Storage::fake('local');
        config(['security.private_upload_disk' => 'local']);

        $stored = app(PrivateUploadStorage::class)->storeUploadedFile(
            UploadedFile::fake()->image('garage-photo.jpg'),
            'companies/1/uploads'
        );

        $this->assertSame('local', $stored['disk']);
        $this->assertSame('image/jpeg', $stored['mime']);
        $this->assertStringStartsWith('companies/1/uploads/', $stored['path']);
        Storage::disk('local')->assertExists($stored['path']);
        Storage::disk('public')->assertMissing($stored['path']);
    }

    public function test_disguised_executable_upload_and_path_traversal_fail_closed(): void
    {
        Storage::fake('local');
        config(['security.private_upload_disk' => 'local']);

        try {
            app(PrivateUploadStorage::class)->storeUploadedFile(
                UploadedFile::fake()->createWithContent('invoice.pdf', '<?php echo "unsafe";'),
                'companies/1/uploads'
            );
            $this->fail('A disguised executable was accepted.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(NotFoundHttpException::class);
        app(PrivateUploadStorage::class)->download('local', '../outside.pdf', 'outside.pdf');
    }

    public function test_document_route_binding_is_tenant_scoped(): void
    {
        $ownCompany = Company::query()->create(['name' => 'Own Security Garage']);
        $otherCompany = Company::query()->create(['name' => 'Other Security Garage']);
        $user = User::factory()->create([
            'company_id' => $ownCompany->id,
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
        $ownClient = Client::query()->create(['company_id' => $ownCompany->id, 'name' => 'Own Client', 'phone' => '+971500000101']);
        $otherClient = Client::query()->create(['company_id' => $otherCompany->id, 'name' => 'Other Client', 'phone' => '+971500000102']);
        $own = ClientDocument::query()->create([
            'company_id' => $ownCompany->id,
            'client_id' => $ownClient->id,
            'document_name' => 'own.pdf',
            'document_path' => 'companies/own.pdf',
        ]);
        $other = ClientDocument::query()->create([
            'company_id' => $otherCompany->id,
            'client_id' => $otherClient->id,
            'document_name' => 'other.pdf',
            'document_path' => 'companies/other.pdf',
        ]);

        $this->actingAs($user);
        $this->assertSame($own->id, (new ClientDocument)->resolveRouteBinding($own->id)?->id);
        $this->assertNull((new ClientDocument)->resolveRouteBinding($other->id));
    }

    public function test_meta_page_tokens_are_encrypted_and_legacy_tokens_can_be_migrated_idempotently(): void
    {
        $company = Company::query()->create([
            'name' => 'Provider Token Garage',
            'meta_access_token' => 'company-provider-token-fixture',
            'meta_verify_token' => 'company-verify-token-fixture',
        ]);
        $companyRaw = DB::table('companies')->where('id', $company->id)->first(['meta_access_token', 'meta_verify_token', 'meta_verify_token_hash']);
        $this->assertNotSame('company-provider-token-fixture', $companyRaw->meta_access_token);
        $this->assertNotSame('company-verify-token-fixture', $companyRaw->meta_verify_token);
        $this->assertSame('company-provider-token-fixture', Crypt::decryptString((string) $company->fresh()->meta_access_token));
        $this->assertSame(Company::metaVerifyTokenHash('company-verify-token-fixture'), $companyRaw->meta_verify_token_hash);
        $page = MetaPage::query()->create([
            'company_id' => $company->id,
            'page_id' => 'fixture-page',
            'page_name' => 'Fixture',
            'page_access_token' => 'legacy-fixture-token',
        ]);
        $rawEncrypted = DB::table('meta_pages')->where('id', $page->id)->value('page_access_token');
        $this->assertNotSame('legacy-fixture-token', $rawEncrypted);
        $this->assertSame('legacy-fixture-token', $page->fresh()->page_access_token);

        DB::table('meta_pages')->where('id', $page->id)->update(['page_access_token' => 'older-plaintext-fixture']);
        $this->artisan('security:encrypt-legacy-provider-tokens')->assertSuccessful();
        $this->assertSame('older-plaintext-fixture', DB::table('meta_pages')->where('id', $page->id)->value('page_access_token'));

        $this->artisan('security:encrypt-legacy-provider-tokens', ['--apply' => true])->assertSuccessful();
        $migrated = (string) DB::table('meta_pages')->where('id', $page->id)->value('page_access_token');
        $this->assertSame('older-plaintext-fixture', Crypt::decryptString($migrated));

        $this->artisan('security:encrypt-legacy-provider-tokens', ['--apply' => true])->assertSuccessful();
        $this->assertSame($migrated, DB::table('meta_pages')->where('id', $page->id)->value('page_access_token'));
    }

    public function test_company_settings_encrypt_flag_encrypts_at_rest_and_legacy_plaintext_is_migrated(): void
    {
        $company = Company::query()->create(['name' => 'Encrypted Settings Garage']);
        $setting = new CompanySetting([
            'company_id' => $company->id,
            'key' => 'provider.secret.fixture',
            'group' => 'provider',
        ]);
        $setting->is_encrypted = true;
        $setting->value = 'settings-secret-fixture';
        $setting->save();

        $raw = DB::table('company_settings')->where('id', $setting->id)->value('value');
        $this->assertNotSame('settings-secret-fixture', $raw);
        $this->assertSame('settings-secret-fixture', $setting->fresh()->value);

        DB::table('company_settings')->where('id', $setting->id)->update(['value' => 'legacy-settings-fixture']);
        $this->artisan('security:encrypt-legacy-provider-tokens', ['--apply' => true])->assertSuccessful();
        $migrated = (string) DB::table('company_settings')->where('id', $setting->id)->value('value');
        $this->assertSame('legacy-settings-fixture', Crypt::decryptString($migrated));
    }

    public function test_tenant_settings_reject_browser_supplied_provider_credentials_and_assets(): void
    {
        $request = new UpdateSettingsRequest;
        $validator = Validator::make([
            'company' => ['name' => 'Security Garage'],
            'meta' => [
                'access_token' => 'browser-token-fixture',
                'page_id' => 'browser-page-fixture',
                'form_id' => 'browser-form-fixture',
            ],
            'twilio' => [
                'auth_token' => 'browser-twilio-fixture',
                'whatsapp_from' => 'whatsapp:+971500000000',
            ],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('meta.access_token', $validator->errors()->toArray());
        $this->assertArrayHasKey('meta.page_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('meta.form_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('twilio.auth_token', $validator->errors()->toArray());
        $this->assertArrayHasKey('twilio.whatsapp_from', $validator->errors()->toArray());
    }

    public function test_inconsistent_legacy_job_card_mutation_routes_are_not_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->map(fn ($route) => $route->getName());

        $this->assertFalse($routes->contains('tenant.jobcards.store'));
        $this->assertFalse($routes->contains('tenant.jobcards.update'));
    }

    public function test_ai_policy_errors_and_unknown_decisions_fail_closed_to_human_handoff(): void
    {
        $company = Company::query()->create(['name' => 'AI Policy Garage']);
        DB::table('company_settings')->insert([
            'company_id' => $company->id,
            'key' => 'ai.enabled',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = new class($company->id)
        {
            public ?array $nlp = ['intent' => 'unexpected_intent', 'confidence' => 0.99];

            public string $body = 'Synthetic policy fixture';

            public function __construct(public int $companyId) {}

            public function policyHandoff(string $reason, float $confidence): string
            {
                return "handoff:{$reason}";
            }
        };

        $continued = false;
        $result = (new EnforceAiPolicy)->handle($job, function () use (&$continued): string {
            $continued = true;

            return 'continued';
        });

        $this->assertFalse($continued);
        $this->assertSame('handoff:policy_decision_unavailable', $result);
    }

    public function test_remote_attachment_fetching_requires_https_explicit_host_allowlist_and_public_dns(): void
    {
        config([
            'document_ingest.allow_remote_attachment_urls' => true,
            'document_ingest.allowed_attachment_hosts' => ['localhost'],
        ]);
        $controller = new class(app(UploadIngestService::class)) extends EmailInboundWebhookController
        {
            public function urlIsAllowed(string $url): bool
            {
                return $this->isAllowedAttachmentUrl($url);
            }
        };

        $this->assertFalse($controller->urlIsAllowed('http://localhost/private'));
        $this->assertFalse($controller->urlIsAllowed('https://localhost/private'));
        $this->assertFalse($controller->urlIsAllowed('https://unapproved.example.test/file.pdf'));
    }

    public function test_known_stored_xss_and_secret_logging_sinks_are_removed(): void
    {
        $chat = (string) file_get_contents(resource_path('views/admin/chat/index.blade.php'));
        $meta = (string) file_get_contents(app_path('Http/Controllers/Admin/MetaConnectController.php'));
        $legacyWebhook = (string) file_get_contents(app_path('Http/Controllers/Automation/WhatsAppWebhookController.php'));
        $userController = (string) file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));

        $this->assertStringContainsString('escapeConversationText(conv.customer_name', $chat);
        $this->assertStringNotContainsString("'payload' => \$request->all()", $meta);
        $this->assertStringNotContainsString("Log::info('WA INCOMING PAYLOAD', \$payload)", $legacyWebhook);
        $this->assertStringNotContainsString('Temporary password:', $userController);
    }
}

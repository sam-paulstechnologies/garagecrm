<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicWebsiteLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_exposes_approved_pricing_audit_capture_and_legal_links(): void
    {
        $response = $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Understand every enquiry.')
            ->assertSee('Turn every customer message into a clear next step.')
            ->assertSee('AI suggestions are reviewed by your team before they are sent.')
            ->assertSee('Human review required')
            ->assertSee('Book My Free Audit')
            ->assertSee(route('public.demo.store'), false)
            ->assertSee(route('privacy-policy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee('AED 999')
            ->assertSee('AED 1,499')
            ->assertSee('AED 1,999')
            ->assertSee('Recommended')
            ->assertSee('WhatsApp, Meta, AI usage and provider fees may be charged separately where applicable.')
            ->assertSee('AI Communication Copilot access is initially available to selected Pro pilot garages.')
            ->assertSee('/css/sayaraforce-brand.css', false)
            ->assertSee('/images/brand/sayaraforce-logo-horizontal.png', false)
            ->assertSee('/images/brand/sayaraforce-logo-tagline.png', false)
            ->assertSee('/site.webmanifest', false)
            ->assertSee('aria-controls="faq-panel-0"', false)
            ->assertSee('aria-expanded="true"', false);

        $content = $response->getContent();

        $this->assertStringNotContainsString('AED 499', $content);
        $this->assertStringNotContainsString('AED 699', $content);
        $this->assertStringNotContainsString('aggregateRating', $content);
        $this->assertStringNotContainsString('Trusted by', $content);
        $this->assertStringNotContainsString('Fully autonomous AI', $content);
        $this->assertStringNotContainsString('AI automatically sends', $content);
        $this->assertStringNotContainsString('Unlimited AI', $content);
        $this->assertStringNotContainsString('Inter Tight', $content);
        $this->assertStringNotContainsString('Satoshi', $content);
        $this->assertStringNotContainsString('General Sans', $content);
        $this->assertStringContainsString('#0D1B3D', file_get_contents(public_path('css/sayaraforce-brand.css')));
        $this->assertStringContainsString('#FF6A00', file_get_contents(public_path('css/sayaraforce-brand.css')));
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('images/brand/sayaraforce-app-icon-512.png'));
        $this->assertSame(1, substr_count($content, '<h1'));
    }

    public function test_demo_request_falls_back_to_local_enquiry_storage_without_public_token(): void
    {
        Storage::fake('local');

        $this->post(route('public.demo.store'), [
            'garage_name' => 'Al Noor Garage',
            'name' => 'Noura Haddad',
            'phone' => '971500000056',
            'email' => 'noura@example.test',
            'current_management_system' => 'Existing workshop system',
            'monthly_leads' => '50_100',
            'main_challenge' => 'We want to recover missed WhatsApp leads.',
        ])
            ->assertRedirect(route('public.demo.thank-you'))
            ->assertSessionHas('success');

        Storage::disk('local')->assertExists('sayaraforce/demo-enquiries.jsonl');

        $stored = Storage::disk('local')->get('sayaraforce/demo-enquiries.jsonl');

        $this->assertStringContainsString('Al Noor Garage', $stored);
        $this->assertStringContainsString('Noura Haddad', $stored);
        $this->assertStringContainsString('public_website', $stored);
        $this->assertStringContainsString('Existing workshop system', $stored);
        $this->assertStringContainsString('50_100', $stored);
        $this->assertStringContainsString('recover missed WhatsApp leads', $stored);
    }

    public function test_demo_request_requires_core_contact_fields(): void
    {
        $this->from(route('public.home'))
            ->post(route('public.demo.store'), [])
            ->assertRedirect(route('public.home'))
            ->assertSessionHasErrors(['garage_name', 'name', 'phone']);
    }

    public function test_public_thank_you_privacy_and_terms_pages_render(): void
    {
        $this->get(route('public.demo.thank-you'))
            ->assertOk()
            ->assertSee('Thank you')
            ->assertSee('/images/brand/sayaraforce-logo-horizontal.png', false);

        $this->get(route('privacy-policy'))
            ->assertOk();

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms');
    }
}

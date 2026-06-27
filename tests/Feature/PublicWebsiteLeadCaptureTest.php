<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicWebsiteLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_exposes_demo_capture_and_legal_links(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('SayaraForce helps UAE garages recover missed leads')
            ->assertSee('Book Demo / Request Free Audit')
            ->assertSee(route('public.demo.store'), false)
            ->assertSee('WhatsApp Us')
            ->assertSee(route('privacy-policy'), false)
            ->assertSee(route('terms'), false);
    }

    public function test_demo_request_falls_back_to_local_enquiry_storage_without_public_token(): void
    {
        Storage::fake('local');

        $this->post(route('public.demo.store'), [
            'garage_name' => 'Al Noor Garage',
            'name' => 'Noura Haddad',
            'phone' => '971500000056',
            'email' => 'noura@example.test',
            'monthly_cars' => '50-100',
            'message' => 'We want to recover missed WhatsApp leads.',
        ])
            ->assertRedirect(route('public.demo.thank-you'))
            ->assertSessionHas('success');

        Storage::disk('local')->assertExists('sayaraforce/demo-enquiries.jsonl');

        $stored = Storage::disk('local')->get('sayaraforce/demo-enquiries.jsonl');

        $this->assertStringContainsString('Al Noor Garage', $stored);
        $this->assertStringContainsString('Noura Haddad', $stored);
        $this->assertStringContainsString('public_website', $stored);
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
            ->assertSee('Thank you');

        $this->get(route('privacy-policy'))
            ->assertOk();

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms');
    }
}

<?php

namespace Tests\Unit;

use App\Services\Ai\PromptPiiMinimizer;
use PHPUnit\Framework\TestCase;

class PromptPiiMinimizerTest extends TestCase
{
    public function test_empty_string_is_safe(): void
    {
        $this->assertSame('', PromptPiiMinimizer::redact(''));
    }

    public function test_email_addresses_are_redacted(): void
    {
        $out = PromptPiiMinimizer::redact('Contact me at john.doe+garage@example.co.uk please');

        $this->assertStringNotContainsString('john.doe', $out);
        $this->assertStringNotContainsString('example.co.uk', $out);
        $this->assertStringContainsString('[email]', $out);
    }

    public function test_uae_phone_number_is_redacted(): void
    {
        $out = PromptPiiMinimizer::redact('Call me on +971 50 123 4567 tomorrow');

        $this->assertStringNotContainsString('4567', $out);
        $this->assertStringContainsString('[phone]', $out);
    }

    public function test_local_phone_number_is_redacted(): void
    {
        $out = PromptPiiMinimizer::redact('my number is 0501234567');

        $this->assertStringContainsString('[phone]', $out);
        $this->assertStringNotContainsString('0501234567', $out);
    }

    public function test_service_request_is_preserved_intact(): void
    {
        $input = 'needs Toyota brake pads';

        $this->assertSame($input, PromptPiiMinimizer::redact($input));
    }

    public function test_vehicle_details_with_year_are_preserved(): void
    {
        $input = '2018 Nissan Patrol needs an oil change and AC service';

        $this->assertSame($input, PromptPiiMinimizer::redact($input));
    }

    public function test_service_relevant_plate_token_is_preserved(): void
    {
        // A UAE-style plate ("A 12345" / "Dubai A 54321") and short number
        // groups must survive: they are alphanumeric / short and are relevant
        // to identifying the vehicle for service.
        $input = 'Plate A 54321 booked for tyre rotation';

        $out = PromptPiiMinimizer::redact($input);

        $this->assertSame($input, $out);
        $this->assertStringContainsString('54321', $out);
    }

    public function test_vin_like_alphanumeric_token_is_preserved(): void
    {
        // A VIN is 17 alphanumeric chars; it is not a bare digit run so it is
        // preserved for service context.
        $input = 'VIN JT2BF22K1W0123456 for engine diagnostics';

        $out = PromptPiiMinimizer::redact($input);

        $this->assertStringContainsString('JT2BF22K1W0123456', $out);
    }

    public function test_long_bare_digit_run_is_redacted(): void
    {
        $out = PromptPiiMinimizer::redact('order reference 1234567890123');

        $this->assertStringContainsString('[number]', $out);
        $this->assertStringNotContainsString('1234567890123', $out);
    }

    public function test_neutral_lead_descriptor_drops_identity(): void
    {
        $bits = PromptPiiMinimizer::neutralLeadDescriptor([
            'name' => 'Jane Customer',
            'phone' => '+971501234567',
            'vehicle_make_id' => 5,
            'other_model' => 'Patrol',
            'conversation_state' => 'awaiting_time',
            'last_intent' => 'booking',
        ]);

        $joined = implode(', ', $bits);

        $this->assertStringNotContainsString('Jane', $joined);
        $this->assertStringNotContainsString('971', $joined);
        $this->assertStringContainsString('make=known', $joined);
        $this->assertStringContainsString('model=known', $joined);
        $this->assertStringContainsString('state=awaiting_time', $joined);
        $this->assertStringContainsString('last_intent=booking', $joined);
    }
}

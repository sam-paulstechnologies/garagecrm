<?php

namespace Tests\Unit;

use App\Models\Client\Opportunity;
use PHPUnit\Framework\TestCase;

class OpportunityStageTest extends TestCase
{
    public function test_opportunity_stage_source_of_truth_uses_final_lifecycle(): void
    {
        $this->assertSame([
            'new',
            'attempting_contact',
            'appointment',
            'offer',
            'manager_confirmation_pending',
            'booking_confirmed',
            'closed_lost',
        ], Opportunity::STAGES);
    }

    public function test_legacy_stages_normalize_to_current_lifecycle(): void
    {
        $this->assertSame(Opportunity::STAGE_BOOKING_CONFIRMED, Opportunity::normalizeStage('closed_won'));
        $this->assertSame(Opportunity::STAGE_ATTEMPTING_CONTACT, Opportunity::normalizeStage('collecting_details'));
    }

    public function test_legacy_stages_display_as_current_labels(): void
    {
        $closedWon = new Opportunity(['stage' => 'closed_won']);
        $collectingDetails = new Opportunity(['stage' => 'collecting_details']);

        $this->assertSame('Booking Confirmed', $closedWon->stage_label);
        $this->assertSame('Attempting Contact', $collectingDetails->stage_label);
    }
}

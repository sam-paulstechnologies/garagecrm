<?php

namespace Tests\Unit;

use App\Models\Client\Lead;
use PHPUnit\Framework\TestCase;

class LeadStatusTest extends TestCase
{
    public function test_lead_status_source_of_truth_uses_current_lifecycle(): void
    {
        $this->assertSame([
            'new',
            'attempting_contact',
            'contact_on_hold',
            'qualified',
            'disqualified',
        ], Lead::STATUSES);
    }

    public function test_legacy_statuses_normalize_to_current_lifecycle(): void
    {
        $this->assertSame(Lead::STATUS_QUALIFIED, Lead::normalizeStatus('converted'));
        $this->assertSame(Lead::STATUS_DISQUALIFIED, Lead::normalizeStatus('lost'));
        $this->assertSame(Lead::STATUS_HOLD, Lead::normalizeStatus('contact_on_hold'));
    }

    public function test_legacy_statuses_display_as_current_labels(): void
    {
        $converted = new Lead(['status' => 'converted']);
        $lost = new Lead(['status' => 'lost']);

        $this->assertSame('Qualified', $converted->status_label);
        $this->assertSame('Disqualified', $lost->status_label);
    }
}

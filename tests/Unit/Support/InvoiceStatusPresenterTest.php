<?php

namespace Tests\Unit\Support;

use App\Support\InvoiceStatusPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InvoiceStatusPresenterTest extends TestCase
{
    #[DataProvider('supportedStatuses')]
    public function test_supported_statuses_have_readable_semantic_presentations(
        string $status,
        string $label,
        string $adminClass,
        string $managerClass,
    ): void {
        $presentation = (new InvoiceStatusPresenter())->present($status);

        $this->assertSame($status, $presentation['value']);
        $this->assertSame($label, $presentation['label']);
        $this->assertSame($adminClass, $presentation['admin_badge_class']);
        $this->assertSame($managerClass, $presentation['manager_badge_class']);
        $this->assertTrue($presentation['supported']);
    }

    public static function supportedStatuses(): array
    {
        return [
            'pending' => ['pending', 'Pending', 'sf-badge-yellow', 'badge-soft-warning'],
            'paid' => ['paid', 'Paid', 'sf-badge-green', 'badge-soft-success'],
            'overdue' => ['overdue', 'Overdue', 'sf-badge-red', 'badge-soft-danger'],
        ];
    }

    public function test_legacy_and_unknown_statuses_are_read_safe(): void
    {
        $presenter = new InvoiceStatusPresenter();

        $this->assertSame('Partially Paid', $presenter->present('partially_paid')['label']);
        $this->assertSame('sf-badge-red', $presenter->present('void')['admin_badge_class']);

        $unknown = $presenter->present('provider_hold');

        $this->assertSame('Provider Hold', $unknown['label']);
        $this->assertSame('neutral', $unknown['semantic']);
        $this->assertSame('sf-badge-slate', $unknown['admin_badge_class']);
        $this->assertSame('badge-soft-muted', $unknown['manager_badge_class']);
        $this->assertFalse($unknown['supported']);
    }
}

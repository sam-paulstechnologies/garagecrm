<?php

namespace App\Support;

final class InvoiceStatusPresenter
{
    /**
     * Statuses accepted by the current invoice write paths and database enum.
     *
     * @var list<string>
     */
    public const SUPPORTED_STATUSES = [
        'pending',
        'paid',
        'overdue',
    ];

    /**
     * Return role-specific CSS classes and a readable label for an invoice status.
     *
     * Legacy values are intentionally read-safe. Unknown values receive a neutral
     * treatment instead of being presented as a supported payment state.
     *
     * @return array{
     *     value: string,
     *     label: string,
     *     semantic: string,
     *     admin_badge_class: string,
     *     manager_badge_class: string,
     *     help: string,
     *     supported: bool
     * }
     */
    public function present(?string $status): array
    {
        $value = strtolower(trim((string) $status));
        $value = str_replace([' ', '-'], '_', $value);
        $value = $value !== '' ? $value : 'pending';

        $presentation = match ($value) {
            'pending' => [
                'label' => 'Pending',
                'semantic' => 'warning',
                'admin_badge_class' => 'sf-badge-yellow',
                'manager_badge_class' => 'badge-soft-warning',
                'help' => 'Invoice is awaiting payment.',
            ],
            'paid' => [
                'label' => 'Paid',
                'semantic' => 'success',
                'admin_badge_class' => 'sf-badge-green',
                'manager_badge_class' => 'badge-soft-success',
                'help' => 'Invoice revenue is confirmed for reporting.',
            ],
            'overdue' => [
                'label' => 'Overdue',
                'semantic' => 'danger',
                'admin_badge_class' => 'sf-badge-red',
                'manager_badge_class' => 'badge-soft-danger',
                'help' => 'Invoice payment is past due or needs attention.',
            ],

            // Read-only compatibility for data created by older invoice schemas.
            'draft' => [
                'label' => 'Draft',
                'semantic' => 'neutral',
                'admin_badge_class' => 'sf-badge-slate',
                'manager_badge_class' => 'badge-soft-muted',
                'help' => 'Legacy draft invoice.',
            ],
            'sent', 'issued', 'unpaid', 'open' => [
                'label' => $this->readableLabel($value),
                'semantic' => 'warning',
                'admin_badge_class' => 'sf-badge-yellow',
                'manager_badge_class' => 'badge-soft-warning',
                'help' => 'Legacy invoice status retained for display.',
            ],
            'partially_paid' => [
                'label' => 'Partially Paid',
                'semantic' => 'warning',
                'admin_badge_class' => 'sf-badge-yellow',
                'manager_badge_class' => 'badge-soft-warning',
                'help' => 'Legacy partial-payment status retained for display.',
            ],
            'cancelled', 'canceled', 'void' => [
                'label' => $this->readableLabel($value),
                'semantic' => 'danger',
                'admin_badge_class' => 'sf-badge-red',
                'manager_badge_class' => 'badge-soft-danger',
                'help' => 'Legacy closed invoice status retained for display.',
            ],
            default => [
                'label' => $this->readableLabel($value),
                'semantic' => 'neutral',
                'admin_badge_class' => 'sf-badge-slate',
                'manager_badge_class' => 'badge-soft-muted',
                'help' => 'Unrecognised invoice status shown without changing the record.',
            ],
        };

        return [
            'value' => $value,
            ...$presentation,
            'supported' => in_array($value, self::SUPPORTED_STATUSES, true),
        ];
    }

    private function readableLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

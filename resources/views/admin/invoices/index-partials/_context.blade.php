@php
    $stats = $stats ?? [
        'total' => $invoices->total(),
        'paid' => 0,
        'pending' => 0,
        'overdue' => 0,
        'roi_revenue' => 0,
    ];

    $currentStatus = $status ?? request('status', '');
    $currentSearch = $q ?? request('q', request('search', ''));

    $statusBadgeClass = function ($statusValue) {
        return match($statusValue) {
            'paid' => 'sf-badge-green',
            'overdue' => 'sf-badge-red',
            default => 'sf-badge-yellow',
        };
    };
@endphp

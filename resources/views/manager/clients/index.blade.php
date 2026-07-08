@extends('layouts.manager')

@section('title', 'Manager Clients')

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $clientCounts = $clientCounts ?? [];

    $clientName = function ($client) {
        return $client->name
            ?? $client->full_name
            ?? $client->customer_name
            ?? $client->client_name
            ?? 'Client #' . $client->id;
    };

    $clientPhone = function ($client) {
        return $client->phone
            ?? $client->mobile
            ?? $client->phone_number
            ?? $client->whatsapp_number
            ?? $client->whatsapp
            ?? null;
    };

    $clientEmail = function ($client) {
        return $client->email
            ?? $client->email_norm
            ?? null;
    };

    $clientVehicle = function ($client) {
        $make = $client->vehicle_make ?? $client->make ?? null;
        $model = $client->vehicle_model ?? $client->model ?? null;
        $plate = $client->plate_number ?? $client->registration_number ?? null;

        $vehicle = trim(($make ?? '') . ' ' . ($model ?? ''));

        if ($plate) {
            $vehicle = $vehicle ? $vehicle . ' / ' . $plate : $plate;
        }

        return $vehicle ?: 'Vehicle not linked';
    };

    $clientSource = fn ($client) => $client->source ? Str::headline($client->source) : 'No source';

    $clientNotes = function ($client) {
        $attributes = $client->getAttributes();

        return $attributes['notes']
            ?? $attributes['internal_notes']
            ?? null;
    };

    $selectedSearch = $q ?? request('q', '');

    $statCards = [
        ['label' => 'Active Clients', 'value' => $clientCounts['total'] ?? 0, 'note' => 'Company-scoped records'],
        ['label' => 'Phone Ready', 'value' => $clientCounts['with_phone'] ?? 0, 'note' => 'Callable or WhatsApp-ready'],
        ['label' => 'Email Captured', 'value' => $clientCounts['with_email'] ?? 0, 'note' => 'Has email on file'],
        ['label' => 'New 30 Days', 'value' => $clientCounts['new_30_days'] ?? 0, 'note' => 'Recently created clients'],
    ];
@endphp

<div class="manager-clients-page">
    <section class="manager-index-hero">
        <div>
            <p class="manager-eyebrow">Manager Customer Desk</p>
            <h1 class="sf-page-title mb-2">Clients</h1>
            <p class="sf-page-subtitle mb-0">
                Review customer records linked to leads, bookings, jobs, and invoices for this garage.
            </p>
        </div>

        <div class="manager-hero-actions">
            @if(Route::has('manager.dashboard'))
                <a href="{{ route('manager.dashboard') }}" class="sf-action-button light">
                    Back to Dashboard
                </a>
            @endif
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success manager-alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="client-stat-grid">
        @foreach($statCards as $card)
            <div class="client-stat-card">
                <span>{{ $card['label'] }}</span>
                <strong>{{ number_format((int) $card['value']) }}</strong>
                <em>{{ $card['note'] }}</em>
            </div>
        @endforeach
    </div>

    <section class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Search Clients</h2>
                <p class="sf-panel-subtitle">
                    Search by name, phone, email, vehicle, plate number, or notes.
                </p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.clients.index') }}" class="client-filter-grid">
                <label>
                    <span>Search</span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $selectedSearch }}"
                        placeholder="Name, phone, email, vehicle, plate number"
                    >
                </label>

                <div class="client-filter-actions">
                    <button type="submit" class="sf-action-button primary">
                        Search
                    </button>

                    <a href="{{ route('manager.clients.index') }}" class="sf-action-button light">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </section>

    <section class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Client List</h2>
                <p class="sf-panel-subtitle">
                    Read-only customer context for day-to-day manager work.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($clients, 'total') ? number_format($clients->total()) : number_format($clients->count()) }} client(s)
            </span>
        </div>

        @if($clients->count())
            <div class="client-table-wrap">
                <table class="manager-clients-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Vehicle / Plate</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th>Notes</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($clients as $client)
                            @php
                                $phone = $clientPhone($client);
                                $email = $clientEmail($client);
                            @endphp
                            <tr>
                                <td data-label="Client">
                                    <div class="client-primary">{{ $clientName($client) }}</div>
                                    <div class="client-muted">Client ID: #{{ $client->id }}</div>
                                </td>

                                <td data-label="Contact">
                                    @if($phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="client-link">
                                            {{ $phone }}
                                        </a>
                                    @else
                                        <span class="client-muted">No phone</span>
                                    @endif

                                    <div class="client-muted mt-1">
                                        {{ $email ?: 'No email' }}
                                    </div>

                                    @if(!empty($client->preferred_channel))
                                        <span class="client-badge mt-2">
                                            {{ Str::headline($client->preferred_channel) }}
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Vehicle / Plate">
                                    <div class="client-value">{{ $clientVehicle($client) }}</div>
                                </td>

                                <td data-label="Source">
                                    <span class="client-badge">{{ $clientSource($client) }}</span>
                                </td>

                                <td data-label="Created">
                                    <div class="client-value">
                                        @if(!empty($client->created_at))
                                            {{ \Carbon\Carbon::parse($client->created_at)->format('d M Y') }}
                                        @else
                                            Not available
                                        @endif
                                    </div>
                                </td>

                                <td data-label="Notes">
                                    <div class="client-muted client-notes">
                                        {{ Str::limit($clientNotes($client) ?? 'No notes captured.', 130) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="manager-empty-state">
                <h3>No clients found</h3>
                <p>Customer records will appear here when leads, bookings, or jobs create client profiles.</p>
            </div>
        @endif

        @if(method_exists($clients, 'links'))
            <div class="manager-pagination">
                {{ $clients->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@push('styles')
<style>
    .manager-clients-page {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .manager-index-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        border: 1px solid var(--sf-border-light);
        border-radius: 22px;
        padding: 28px;
        background: var(--sf-surface);
        box-shadow: var(--sf-soft-shadow);
    }

    .manager-eyebrow {
        display: inline-flex;
        width: max-content;
        margin: 0 0 12px;
        border-radius: 999px;
        padding: 7px 12px;
        background: var(--sf-orange-soft);
        color: var(--sf-orange);
        border: 1px solid rgba(249, 115, 22, 0.20);
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .manager-hero-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .manager-alert {
        margin: 0;
        border-radius: 16px;
        font-weight: 750;
    }

    .client-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .client-stat-card {
        display: flex;
        min-height: 132px;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid var(--sf-border-light);
        border-radius: 20px;
        padding: 18px;
        background: var(--sf-surface);
        color: var(--sf-text);
        box-shadow: var(--sf-soft-shadow);
    }

    .client-stat-card span {
        color: var(--sf-muted-strong);
        font-size: 13px;
        font-weight: 900;
    }

    .client-stat-card strong {
        color: var(--sf-text-strong);
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.04em;
    }

    .client-stat-card em {
        color: var(--sf-muted);
        font-size: 12px;
        font-style: normal;
        font-weight: 750;
    }

    .client-filter-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: end;
    }

    .client-filter-grid label {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .client-filter-grid label > span {
        color: var(--sf-muted-strong);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .client-filter-grid input {
        width: 100%;
        min-height: 44px;
        border: 1px solid var(--sf-border-light);
        border-radius: 12px;
        padding: 9px 11px;
        background: var(--sf-input-bg);
        color: var(--sf-input-text);
        font-size: 13px;
        font-weight: 750;
    }

    .client-filter-actions {
        display: flex;
        gap: 10px;
    }

    .client-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .manager-clients-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .manager-clients-table th {
        padding: 14px 18px;
        background: var(--sf-surface-soft);
        color: var(--sf-muted-strong);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.05em;
        text-align: left;
        text-transform: uppercase;
    }

    .manager-clients-table td {
        padding: 18px;
        border-top: 1px solid var(--sf-border-light);
        color: var(--sf-text);
        vertical-align: top;
        overflow-wrap: anywhere;
    }

    .manager-clients-table tbody tr:hover td {
        background: var(--sf-row-hover);
    }

    .client-primary,
    .client-value {
        color: var(--sf-text-strong);
        font-weight: 950;
    }

    .client-muted,
    .client-notes {
        color: var(--sf-muted);
        font-size: 12px;
        font-weight: 750;
    }

    .client-link {
        color: var(--sf-orange);
        font-weight: 950;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .client-badge {
        display: inline-flex;
        width: fit-content;
        border: 1px solid var(--sf-border-light);
        border-radius: 999px;
        padding: 7px 10px;
        background: var(--sf-surface-soft);
        color: var(--sf-muted-strong);
        font-size: 11px;
        font-weight: 950;
        line-height: 1;
    }

    .manager-empty-state {
        padding: 64px 20px;
        text-align: center;
    }

    .manager-empty-state h3 {
        margin: 0;
        color: var(--sf-text-strong);
        font-size: 20px;
        font-weight: 950;
    }

    .manager-empty-state p {
        margin: 8px 0 0;
        color: var(--sf-muted);
        font-weight: 750;
    }

    @media (max-width: 1180px) {
        .client-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 860px) {
        .manager-index-hero {
            flex-direction: column;
            padding: 22px;
        }

        .manager-hero-actions {
            justify-content: flex-start;
        }

        .client-filter-grid {
            grid-template-columns: 1fr;
        }

        .client-filter-actions {
            align-items: stretch;
        }

        .client-filter-actions .sf-action-button {
            flex: 1;
        }

        .manager-clients-table,
        .manager-clients-table thead,
        .manager-clients-table tbody,
        .manager-clients-table tr,
        .manager-clients-table td {
            display: block;
            width: 100%;
        }

        .manager-clients-table thead {
            display: none;
        }

        .manager-clients-table tr {
            border-top: 1px solid var(--sf-border-light);
            padding: 14px;
        }

        .manager-clients-table td {
            display: grid;
            grid-template-columns: 132px minmax(0, 1fr);
            gap: 12px;
            border-top: 0;
            padding: 10px 4px;
        }

        .manager-clients-table td::before {
            content: attr(data-label);
            color: var(--sf-muted-strong);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
    }

    @media (max-width: 560px) {
        .client-stat-grid {
            grid-template-columns: 1fr;
        }

        .client-filter-actions {
            flex-direction: column;
        }

        .manager-clients-table td {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

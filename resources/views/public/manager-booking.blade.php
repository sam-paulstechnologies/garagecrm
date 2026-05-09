<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 24px;
            color: #111827;
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 24px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 24px;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .field {
            margin-bottom: 18px;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        button {
            width: 100%;
            background: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #15803d;
        }

        .hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }
    </style>
</head>
<body>
@php
    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    | Prefer the customer-requested date from opportunity.
    | If manager validation fails, keep old input.
    |--------------------------------------------------------------------------
    */

    $defaultDate = old('booking_date');

    if (! $defaultDate && $opportunity->expected_close_date) {
        $defaultDate = $opportunity->expected_close_date->format('Y-m-d');
    }

    if (! $defaultDate && $opportunity->next_follow_up) {
        $defaultDate = $opportunity->next_follow_up->format('Y-m-d');
    }

    $defaultSlot = old('slot', 'afternoon');

    $clientName = $opportunity->client?->name ?? 'Customer';
    $vehicle = $opportunity->vehicle_label ?? 'Vehicle not captured';
    $serviceType = $opportunity->service_type ?? 'Service not specified';
@endphp

<div class="card">
    <h2>Confirm Booking</h2>
    <div class="subtitle">
        Review the customer request and confirm the booking slot.
    </div>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="info-box">
        <div class="info-row">
            <strong>Customer:</strong> {{ $clientName }}
        </div>

        <div class="info-row">
            <strong>Phone:</strong> {{ $opportunity->client?->phone ?? $opportunity->client?->whatsapp ?? '-' }}
        </div>

        <div class="info-row">
            <strong>Vehicle:</strong> {{ $vehicle }}
        </div>

        <div class="info-row">
            <strong>Service:</strong> {{ $serviceType }}
        </div>

        <div class="info-row">
            <strong>Requested Date:</strong>
            {{ $opportunity->expected_close_date ? $opportunity->expected_close_date->format('d M Y') : '-' }}
        </div>
    </div>

    <form method="POST">
        @csrf

        <div class="field">
            <label for="booking_date">Booking Date</label>
            <input
                type="date"
                id="booking_date"
                name="booking_date"
                value="{{ $defaultDate }}"
                required
            >
            <div class="hint">
                This is prefilled from the customer requested date. Change only if needed.
            </div>
        </div>

        <div class="field">
            <label for="slot">Slot</label>
            <select id="slot" name="slot" required>
                <option value="morning" {{ $defaultSlot === 'morning' ? 'selected' : '' }}>
                    Morning
                </option>

                <option value="afternoon" {{ $defaultSlot === 'afternoon' ? 'selected' : '' }}>
                    Afternoon
                </option>

                <option value="evening" {{ $defaultSlot === 'evening' ? 'selected' : '' }}>
                    Evening
                </option>

                <option value="full_day" {{ $defaultSlot === 'full_day' ? 'selected' : '' }}>
                    Full Day
                </option>
            </select>
        </div>

        <button type="submit">
            Confirm Booking
        </button>
    </form>
</div>
</body>
</html>
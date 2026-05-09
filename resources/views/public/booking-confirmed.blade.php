<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            color: #111827;
        }

        .wrap {
            max-width: 640px;
            margin: 60px auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            text-align: center;
        }

        .icon {
            font-size: 52px;
            margin-bottom: 16px;
        }

        h1 {
            margin-bottom: 10px;
            color: #065f46;
        }

        p {
            color: #4b5563;
            line-height: 1.6;
        }

        .details {
            margin-top: 24px;
            background: #f9fafb;
            border-radius: 10px;
            padding: 16px;
            text-align: left;
        }

        .details div {
            margin-bottom: 8px;
        }

        .label {
            font-weight: bold;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="icon">✅</div>

        <h1>Booking Confirmed</h1>

        <p>
            The booking has been confirmed successfully.
            Our team will follow up if any additional details are required.
        </p>

        @isset($booking)
            <div class="details">
                <div>
                    <span class="label">Booking:</span>
                    {{ $booking->name ?? 'Service Booking' }}
                </div>

                <div>
                    <span class="label">Date:</span>
                    {{ optional($booking->booking_date)->format('d M Y') ?? 'Confirmed date' }}
                </div>

                <div>
                    <span class="label">Slot:</span>
                    {{ $booking->slot_label ?? ucfirst((string) $booking->slot) }}
                </div>

                <div>
                    <span class="label">Status:</span>
                    {{ $booking->status_label ?? ucfirst((string) $booking->status) }}
                </div>
            </div>
        @endisset
    </div>
</body>
</html>
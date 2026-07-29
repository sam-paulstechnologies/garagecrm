<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0D1B3D">
    <link rel="stylesheet" href="/css/sayaraforce-brand.css">

    <style>
        body {
            background: var(--paper);
            margin: 0;
            padding: 0;
            color: var(--text-on-light);
        }

        .wrap {
            max-width: 640px;
            margin: 60px auto;
            background: var(--brand-white);
            border: 1px solid var(--line-light);
            border-radius: 14px;
            padding: 32px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }

        .logo {
            width: min(300px, 78vw);
            height: auto;
            margin: 0 auto 24px;
        }

        .icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: var(--brand-orange);
            color: var(--brand-navy);
            font-size: 30px;
            font-weight: 600;
        }

        h1 {
            margin-bottom: 10px;
            color: var(--brand-navy);
        }

        p {
            color: var(--muted-on-light);
            line-height: 1.6;
        }

        .details {
            margin-top: 24px;
            background: var(--paper);
            border: 1px solid var(--line-light);
            border-radius: 10px;
            padding: 16px;
            text-align: left;
        }

        .details div {
            margin-bottom: 8px;
        }

        .label {
            font-weight: 600;
            color: var(--brand-navy);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <img class="logo" src="/images/brand/sayaraforce-logo-horizontal.png" width="1153" height="326" alt="SayaraForce">
        <div class="icon" aria-hidden="true">&#10003;</div>

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

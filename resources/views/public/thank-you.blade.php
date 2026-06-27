<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - SayaraForce</title>
    <meta name="description" content="Thank you for requesting a SayaraForce demo or lead recovery audit.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://sayaraforce.com/thank-you">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SayaraForce">
    <meta property="og:title" content="Thank You - SayaraForce">
    <meta property="og:description" content="Thank you for requesting a SayaraForce demo or lead recovery audit.">
    <meta property="og:url" content="https://sayaraforce.com/thank-you">
    <meta property="og:image" content="https://sayaraforce.com/apple-touch-icon.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Thank You - SayaraForce">
    <meta name="twitter:description" content="Thank you for requesting a SayaraForce demo or lead recovery audit.">
    <meta name="twitter:image" content="https://sayaraforce.com/apple-touch-icon.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @if(config('services.sayaraforce.ga4_measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.sayaraforce.ga4_measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.sayaraforce.ga4_measurement_id') }}');
            gtag('event', 'generate_lead', {
                event_category: 'website',
                event_label: 'book_demo'
            });
        </script>
    @endif
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f4f7fb;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .card {
            width: min(680px, calc(100% - 32px));
            border: 1px solid #d9e1ec;
            border-radius: 24px;
            background: #ffffff;
            padding: 38px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.10);
        }

        .eyebrow {
            display: inline-flex;
            border-radius: 999px;
            padding: 8px 12px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        h1 {
            margin: 20px 0 10px;
            font-size: clamp(34px, 6vw, 54px);
            line-height: 1;
            letter-spacing: -0.05em;
        }

        p {
            margin: 0;
            color: #475569;
            font-size: 17px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
        }

        a {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            padding: 0 18px;
            color: #111827;
            border: 1px solid #cbd5e1;
            font-weight: 800;
            text-decoration: none;
        }

        a.primary {
            border-color: #ea580c;
            background: #f97316;
            color: #111827;
            box-shadow: 0 14px 30px rgba(234, 88, 12, 0.25);
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="eyebrow">Request received</span>
        <h1>Thank you.</h1>
        <p>
            Your SayaraForce demo or lead recovery audit request has been received.
            The founder will review it and follow up manually before any WhatsApp or campaign action is started.
        </p>

        <div class="actions">
            <a href="{{ route('public.home') }}" class="primary">Back to SayaraForce</a>
            <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
        </div>
    </main>
</body>
</html>

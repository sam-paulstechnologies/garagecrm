<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms - SayaraForce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Terms for SayaraForce demos, pilots and customer service discussions.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://sayaraforce.com/terms">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SayaraForce">
    <meta property="og:title" content="Terms - SayaraForce">
    <meta property="og:description" content="Terms for SayaraForce demos, pilots and customer service discussions.">
    <meta property="og:url" content="https://sayaraforce.com/terms">
    <meta property="og:image" content="https://sayaraforce.com/images/sayaraforce-social-card.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Terms - SayaraForce">
    <meta name="twitter:description" content="Terms for SayaraForce demos, pilots and customer service discussions.">
    <meta name="twitter:image" content="https://sayaraforce.com/images/sayaraforce-social-card.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0D1B3D">
    <link rel="stylesheet" href="/css/sayaraforce-brand.css">
    <style>
        body {
            margin: 0;
            background: var(--paper);
            color: var(--text-on-light);
            line-height: 1.7;
        }

        .page {
            max-width: 920px;
            margin: 0 auto;
            padding: 48px 20px;
        }

        .card {
            background: var(--brand-white);
            border: 1px solid var(--line-light);
            border-radius: 18px;
            padding: 36px;
            box-shadow: var(--shadow-light);
        }

        h1, h2 {
            color: var(--brand-navy);
        }

        h1 {
            margin-top: 0;
            font-size: 34px;
            line-height: 1.2;
        }

        h2 {
            margin-top: 34px;
            font-size: 22px;
        }

        p, li {
            font-size: 15px;
        }

        .brand {
            width: min(300px, 78vw);
            height: auto;
            display: block;
            margin-bottom: 18px;
        }

        .footer {
            margin-top: 34px;
            padding-top: 20px;
            border-top: 1px solid var(--line-light);
            color: var(--muted-on-light);
            font-size: 14px;
        }

        a {
            color: var(--brand-navy);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <img class="brand" src="/images/brand/sayaraforce-logo-horizontal.png" width="1153" height="326" alt="SayaraForce">
            <h1>Terms</h1>
            <p>
                These basic terms describe the demo, pilot, and early customer discussions for SayaraForce,
                an AI-assisted growth and communication platform for UAE garages. A final service agreement must be approved before a paid rollout.
            </p>

            <h2>1. Demo and Pilot Use</h2>
            <p>
                Demo access is provided for evaluation only. Demo data may be sample data, seeded data,
                or pilot garage data provided by the customer with permission.
            </p>

            <h2>2. WhatsApp and Messaging</h2>
            <p>
                WhatsApp sending, templates, WABA setup, and Meta approval remain subject to Meta policies,
                customer consent, and final configuration by the founder/customer.
            </p>

            <h2>3. Customer Data</h2>
            <p>
                Garages are responsible for ensuring that customer data uploaded or connected to SayaraForce
                is collected and used lawfully.
            </p>

            <h2>4. Pricing</h2>
            <p>
                Approved monthly plan pricing is AED 999 for Starter, AED 1,499 for Growth, and AED 1,999 for Pro.
                WhatsApp, Meta, AI usage and provider fees may be charged separately where applicable. The selected plan and
                implementation scope must be confirmed in the final service agreement.
            </p>

            <h2>5. No Guaranteed Results</h2>
            <p>
                SayaraForce is designed to improve lead capture, follow-up, booking workflow, and retention.
                Business results depend on the garage's operations, staff use, customer base, and messaging approvals.
            </p>

            <h2>6. Final Agreement</h2>
            <p>
                A paid implementation should use a signed service agreement covering scope, support, payment,
                data handling, WhatsApp usage, cancellation, and liability.
            </p>

            <div class="footer">
                Last updated: {{ date('d M Y') }}.
                <a href="{{ route('public.home') }}">Back to SayaraForce</a>
            </div>
        </section>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms - SayaraForce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Basic terms for SayaraForce demo, pilot, and founder offer conversations.">
    <style>
        body {
            margin: 0;
            background: #f8fafc;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.7;
        }

        .page {
            max-width: 920px;
            margin: 0 auto;
            padding: 48px 20px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 36px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
        }

        h1, h2 {
            color: #0f172a;
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
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-weight: 700;
            font-size: 13px;
        }

        .footer {
            margin-top: 34px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 14px;
        }

        a {
            color: #9a3412;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <span class="brand">SayaraForce</span>
            <h1>Terms</h1>
            <p>
                These basic terms describe the demo, pilot, and early customer discussions for SayaraForce,
                a WhatsApp-first CRM for UAE garages. A final service agreement must be approved before a paid rollout.
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

            <h2>4. Pricing and Founder Offer</h2>
            <p>
                Any pricing or founder offer shown on the website or sales documents is subject to final written approval.
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

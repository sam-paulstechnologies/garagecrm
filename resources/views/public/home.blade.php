<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SayaraForce — Lead Recovery & Retention CRM for UAE Garages</title>
    <meta name="description" content="SayaraForce helps UAE garages recover missed leads, track WhatsApp follow-ups, manage bookings, and bring old customers back with retention campaigns.">

    @if(config('services.sayaraforce.ga4_measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.sayaraforce.ga4_measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.sayaraforce.ga4_measurement_id') }}');
        </script>
    @endif

    <style>
        :root {
            --bg: #050914;
            --bg-soft: #0b1220;
            --surface: #111827;
            --surface-2: #172033;
            --text: #f8fafc;
            --muted: #9ca3af;
            --muted-2: #64748b;
            --orange: #ff6b14;
            --orange-dark: #ea580c;
            --green: #22c55e;
            --blue: #60a5fa;
            --border: rgba(255, 255, 255, 0.10);
            --shadow: 0 30px 70px rgba(0, 0, 0, 0.32);
            --radius: 22px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at top right, rgba(255, 107, 20, 0.18), transparent 34%),
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 28%),
                var(--bg);
        }

        .container {
            width: min(1040px, calc(100% - 36px));
            margin: 0 auto;
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(5, 9, 20, 0.86);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            min-height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
        }

        .logo {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--orange), #f97316);
            color: #fff;
            font-size: 11px;
            font-weight: 950;
            box-shadow: 0 10px 24px rgba(255, 107, 20, 0.24);
        }

        .brand-name {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .brand-title {
            font-size: 13px;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 9px;
            color: var(--muted);
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 12px;
            font-weight: 850;
            color: #cbd5e1;
        }

        .nav-links a:hover {
            color: #ffffff;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-link {
            font-size: 12px;
            font-weight: 850;
            color: #cbd5e1;
        }

        .login-link:hover {
            color: #ffffff;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            border-radius: 10px;
            padding: 0 16px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 950;
            white-space: nowrap;
            cursor: pointer;
            transition: transform 0.16s ease, filter 0.16s ease, background 0.16s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #ffffff;
            background: linear-gradient(135deg, var(--orange), #f97316);
            box-shadow: 0 14px 26px rgba(255, 107, 20, 0.24);
        }

        .btn-secondary {
            color: #ffffff;
            background: rgba(15, 23, 42, 0.68);
            border-color: var(--border);
        }

        .section {
            padding: 72px 0;
        }

        .hero {
            padding: 86px 0 84px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 0.95fr;
            gap: 54px;
            align-items: center;
        }

        .eyebrow {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 107, 20, 0.12);
            border: 1px solid rgba(255, 107, 20, 0.20);
            color: #fdba74;
            font-size: 11px;
            font-weight: 950;
        }

        .hero h1 {
            margin: 20px 0 0;
            font-size: clamp(42px, 5.4vw, 70px);
            line-height: 0.94;
            letter-spacing: -0.065em;
            font-weight: 950;
        }

        .accent {
            color: var(--orange);
        }

        .hero-copy {
            margin: 20px 0 0;
            max-width: 560px;
            color: #aeb8c9;
            font-size: 14px;
            font-weight: 650;
        }

        .hero-badges {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .badge-247 {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            max-width: 520px;
            padding: 12px 16px;
            border-radius: 999px;
            background: rgba(255, 107, 20, 0.12);
            border: 1px solid rgba(255, 107, 20, 0.30);
            color: #fdba74;
            box-shadow: 0 14px 30px rgba(255, 107, 20, 0.12);
        }

        .badge-247-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b14, #f97316);
            color: #ffffff;
            flex: 0 0 34px;
            box-shadow: 0 12px 22px rgba(255, 107, 20, 0.26);
        }

        .badge-247-content {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .badge-247-title {
            color: #ffffff;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: -0.01em;
            line-height: 1;
        }

        .badge-247-title span {
            color: #fdba74;
        }

        .badge-247-line {
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 750;
            line-height: 1.35;
        }

        .hero-actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-stats {
            margin-top: 26px;
            display: flex;
            gap: 48px;
            flex-wrap: wrap;
        }

        .stat-value {
            display: block;
            color: #ffffff;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .stat-label {
            margin-top: 4px;
            display: block;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
        }

        .dashboard-card {
            position: relative;
            border-radius: 28px;
            padding: 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .dashboard-inner {
            border-radius: 20px;
            background: #101827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 22px;
        }

        .dashboard-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .dash-small {
            color: var(--muted);
            font-size: 11px;
            font-weight: 850;
        }

        .dash-title {
            margin-top: 2px;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .live {
            padding: 5px 9px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
            font-size: 10px;
            font-weight: 950;
        }

        .dash-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .dash-metric {
            border-radius: 14px;
            background: #1b2435;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .dash-metric-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 850;
        }

        .dash-metric-value {
            margin-top: 8px;
            color: #ffffff;
            font-size: 24px;
            line-height: 1;
            font-weight: 950;
        }

        .dash-metric-note {
            margin-top: 8px;
            color: #86efac;
            font-size: 10px;
            font-weight: 900;
        }

        .dash-metric-note.orange {
            color: #fdba74;
        }

        .next-action {
            margin-top: 14px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(255, 107, 20, 0.09);
            border: 1px solid rgba(255, 107, 20, 0.16);
        }

        .next-action strong {
            display: block;
            font-size: 12px;
            color: #ffffff;
        }

        .next-action p {
            margin: 6px 0 0;
            color: #fcd9c5;
            font-size: 11px;
            font-weight: 750;
        }

        .section-kicker {
            color: var(--orange);
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .section-title {
            margin: 12px 0 0;
            max-width: 700px;
            color: #ffffff;
            font-size: clamp(34px, 4.5vw, 52px);
            line-height: 0.98;
            letter-spacing: -0.055em;
            font-weight: 950;
        }

        .section-copy {
            margin: 18px 0 0;
            max-width: 680px;
            color: #aeb8c9;
            font-size: 14px;
            font-weight: 650;
        }

        .cards-3 {
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .card {
            border-radius: var(--radius);
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid var(--border);
            padding: 22px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.16);
        }

        .card h3 {
            margin: 0;
            color: #ffffff;
            font-size: 15px;
            font-weight: 950;
            letter-spacing: -0.025em;
        }

        .card p {
            margin: 10px 0 0;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 650;
        }

        .solution-grid,
        .retention-grid {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 56px;
            align-items: center;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .feature {
            min-height: 112px;
            border-radius: 18px;
            padding: 18px;
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid var(--border);
        }

        .feature h3 {
            margin: 0;
            color: #ffffff;
            font-size: 14px;
            font-weight: 950;
        }

        .feature p {
            margin: 9px 0 0;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 650;
        }

        .retention {
            background:
                radial-gradient(circle at bottom left, rgba(34, 197, 94, 0.10), transparent 28%),
                linear-gradient(180deg, #080d19, #0b1220);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .retention-panel {
            border-radius: 24px;
            padding: 26px;
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .retention-panel h3 {
            margin: 0;
            font-size: 22px;
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        .retention-list {
            margin: 22px 0 0;
            display: grid;
            gap: 13px;
        }

        .retention-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 750;
        }

        .check {
            width: 21px;
            height: 21px;
            flex: 0 0 21px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
            font-size: 11px;
            font-weight: 950;
        }

        .orange-band {
            background: var(--orange);
            color: #111827;
            padding: 42px 0;
        }

        .orange-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 36px;
            align-items: center;
        }

        .orange-band h2 {
            margin: 0;
            max-width: 710px;
            color: #0b1220;
            font-size: clamp(28px, 4vw, 40px);
            line-height: 1.03;
            letter-spacing: -0.05em;
            font-weight: 950;
        }

        .orange-band p {
            margin: 12px 0 0;
            color: #33160b;
            font-size: 13px;
            font-weight: 800;
        }

        .orange-card {
            border-radius: 18px;
            background: #ffffff;
            padding: 22px;
            box-shadow: 0 20px 45px rgba(68, 24, 6, 0.18);
        }

        .orange-card .mini {
            color: var(--muted-2);
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .orange-card strong {
            margin-top: 6px;
            display: block;
            font-size: 20px;
            font-weight: 950;
            color: #111827;
            letter-spacing: -0.04em;
        }

        .orange-card p {
            color: #475569;
            font-size: 12px;
        }

        .pricing {
            text-align: center;
        }

        .pricing .section-title,
        .pricing .section-copy {
            margin-left: auto;
            margin-right: auto;
        }

        .pricing-grid {
            margin-top: 42px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            text-align: left;
        }

        .price-card {
            position: relative;
            border-radius: 22px;
            padding: 28px;
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid var(--border);
        }

        .price-card.featured {
            background: #ffffff;
            color: #111827;
            transform: translateY(-10px);
            box-shadow: var(--shadow);
        }

        .recommended {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--orange);
            color: #ffffff;
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .plan-name {
            margin: 0;
            font-size: 17px;
            font-weight: 950;
        }

        .plan-desc {
            min-height: 44px;
            margin: 9px 0 0;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 650;
        }

        .featured .plan-desc {
            color: #64748b;
        }

        .old-price {
            margin-top: 26px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .price {
            margin-top: 4px;
            font-size: 34px;
            font-weight: 950;
            letter-spacing: -0.06em;
        }

        .price span {
            font-size: 14px;
            letter-spacing: 0;
            font-weight: 850;
            color: #9ca3af;
        }

        .features-list {
            margin: 24px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 750;
        }

        .featured .features-list {
            color: #334155;
        }

        .features-list li::before {
            content: "✓ ";
            color: var(--green);
            font-weight: 950;
        }

        .price-card .btn {
            width: 100%;
            margin-top: 26px;
        }

        .audit {
            background: #0b1220;
        }

        .audit-box {
            width: min(760px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: 0.92fr 1.08fr;
            gap: 32px;
            align-items: center;
            border-radius: 28px;
            background: #1b2435;
            border: 1px solid var(--border);
            padding: 34px;
            box-shadow: var(--shadow);
        }

        .audit h2 {
            margin: 10px 0 0;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 0.98;
            letter-spacing: -0.055em;
        }

        .audit p {
            margin: 16px 0 0;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 650;
        }

        .audit-points {
            margin-top: 18px;
            display: grid;
            gap: 8px;
            color: #e5e7eb;
            font-size: 12px;
            font-weight: 750;
        }

        .audit-points span::before {
            content: "✓ ";
            color: var(--green);
            font-weight: 950;
        }

        .form {
            display: grid;
            gap: 12px;
        }

        .form label {
            display: grid;
            gap: 6px;
            color: #cbd5e1;
            font-size: 11px;
            font-weight: 850;
        }

        .form input,
        .form select {
            width: 100%;
            height: 42px;
            border: 0;
            outline: 0;
            border-radius: 11px;
            background: #050914;
            color: #ffffff;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .form input::placeholder {
            color: #64748b;
        }

        .form-note {
            margin: 0;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
        }

        .footer {
            padding: 28px 0;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .nav-links {
                display: none;
            }

            .hero {
                padding-top: 70px;
            }

            .hero-grid,
            .solution-grid,
            .retention-grid,
            .orange-grid,
            .audit-box {
                grid-template-columns: 1fr;
            }

            .cards-3,
            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .price-card.featured {
                transform: none;
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 26px, 1040px);
            }

            .nav-actions {
                gap: 8px;
            }

            .login-link {
                display: none;
            }

            .btn {
                min-height: 40px;
                padding: 0 13px;
            }

            .section {
                padding: 62px 0;
            }

            .hero h1 {
                font-size: 43px;
            }

            .dash-grid {
                grid-template-columns: 1fr;
            }

            .hero-stats {
                gap: 24px;
            }

            .audit-box {
                padding: 24px;
            }
        }
    </style>
</head>

<body>
<div class="page">

    <nav class="nav">
        <div class="container nav-inner">
            <a href="#top" class="brand">
                <span class="logo">SF</span>
                <span class="brand-name">
                    <span class="brand-title">SayaraForce</span>
                    <span class="brand-subtitle">Garage Growth CRM</span>
                </span>
            </a>

            <div class="nav-links">
                <a href="#problem">Problem</a>
                <a href="#solution">Solution</a>
                <a href="#retention">Retention</a>
                <a href="#pricing">Pricing</a>
                <a href="#audit">Audit</a>
            </div>

            <div class="nav-actions">
                <a href="https://app.sayaraforce.com/login" class="login-link">Login</a>
                <a href="#audit" class="btn btn-primary">Get Free Audit</a>
            </div>
        </div>
    </nav>

    <header id="top" class="hero">
        <div class="container hero-grid">
            <div>
                <div class="eyebrow">Founders offer for selected UAE garages</div>

                <h1>
                    Recover missed leads.
                    <span class="accent">Retain more garage customers.</span>
                </h1>

                <p class="hero-copy">
                    SayaraForce helps garages capture every enquiry, follow up faster, convert more bookings,
                    and bring old customers back with WhatsApp-first lead recovery and retention.
                </p>

                <div class="hero-badges">
                    <div class="badge-247">
                        <span class="badge-247-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </span>

                        <span class="badge-247-content">
                            <span class="badge-247-title">
                                <span>24/7</span> Lead Desk
                            </span>

                            <span class="badge-247-line">
                                Never miss a lead just because your garage is closed.
                            </span>
                        </span>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="#audit" class="btn btn-primary">Request Free 7-Day Lead Recovery Audit</a>
                    <a href="{{ config('services.sayaraforce.public_whatsapp_click_url') ?: '#audit' }}"
                       class="btn btn-secondary"
                       @if(config('services.sayaraforce.public_whatsapp_click_url')) target="_blank" rel="noopener" @endif>
                        WhatsApp Us
                    </a>
                    <a href="#pricing" class="btn btn-secondary">View Founders Pricing</a>
                </div>

                <div class="hero-stats">
                    <div>
                        <span class="stat-value">1</span>
                        <span class="stat-label">Lead inbox</span>
                    </div>
                    <div>
                        <span class="stat-value">24/7</span>
                        <span class="stat-label">Follow-up tracking</span>
                    </div>
                    <div>
                        <span class="stat-value">UAE</span>
                        <span class="stat-label">Garage focused</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-inner">
                    <div class="dashboard-top">
                        <div>
                            <div class="dash-small">Today's Lead Recovery</div>
                            <div class="dash-title">Garage Dashboard</div>
                        </div>

                        <span class="live">Live</span>
                    </div>

                    <div class="dash-grid">
                        <div class="dash-metric">
                            <div class="dash-metric-label">New Leads</div>
                            <div class="dash-metric-value">18</div>
                            <div class="dash-metric-note">+5 from WhatsApp</div>
                        </div>

                        <div class="dash-metric">
                            <div class="dash-metric-label">Pending Follow-ups</div>
                            <div class="dash-metric-value">7</div>
                            <div class="dash-metric-note orange">Action required</div>
                        </div>

                        <div class="dash-metric">
                            <div class="dash-metric-label">Bookings</div>
                            <div class="dash-metric-value">5</div>
                            <div class="dash-metric-note">Confirmed today</div>
                        </div>

                        <div class="dash-metric">
                            <div class="dash-metric-label">Recovered Jobs</div>
                            <div class="dash-metric-value">3</div>
                            <div class="dash-metric-note">From old leads</div>
                        </div>
                    </div>

                    <div class="next-action">
                        <strong>Next best action</strong>
                        <p>4 customers asked for price but were not followed up. Send WhatsApp follow-up now.</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="problem" class="section">
        <div class="container">
            <div class="section-kicker">The Problem</div>

            <h2 class="section-title">
                Garages do not lose customers because of bad service.
                They lose them because follow-up is scattered.
            </h2>

            <div class="cards-3">
                <div class="card">
                    <h3>WhatsApp chaos</h3>
                    <p>Customer enquiries sit inside personal phones with no proper tracking.</p>
                </div>

                <div class="card">
                    <h3>Missed follow-ups</h3>
                    <p>Staff forget to follow up with customers who asked for prices, appointments, or service reminders.</p>
                </div>

                <div class="card">
                    <h3>No clear pipeline</h3>
                    <p>Owners cannot see how many leads came in, who followed up, and which jobs were won or lost.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="solution" class="section">
        <div class="container solution-grid">
            <div>
                <div class="section-kicker">The Solution</div>

                <h2 class="section-title">
                    SayaraForce is not just a CRM. It is a lead recovery and follow-up system built for garages.
                </h2>

                <p class="section-copy">
                    Capture leads from WhatsApp, website forms, Meta campaigns, and manual enquiries.
                    Assign follow-ups, confirm bookings, track jobs, and bring customers back with campaigns.
                </p>
            </div>

            <div class="feature-grid">
                <div class="feature">
                    <h3>Lead Flow Management</h3>
                    <p>Capture, assign, and track every garage enquiry.</p>
                </div>

                <div class="feature">
                    <h3>WhatsApp Follow-ups</h3>
                    <p>Keep conversations and follow-ups organized.</p>
                </div>

                <div class="feature">
                    <h3>Booking Pipeline</h3>
                    <p>Move enquiries into confirmed service bookings.</p>
                </div>

                <div class="feature">
                    <h3>Job Tracking</h3>
                    <p>Track work progress from booking to job completion.</p>
                </div>

                <div class="feature">
                    <h3>Retention Campaigns</h3>
                    <p>Bring old customers back for service reminders.</p>
                </div>

                <div class="feature">
                    <h3>Owner Dashboard</h3>
                    <p>See leads, jobs, revenue, and staff activity.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="retention" class="section retention">
        <div class="container retention-grid">
            <div>
                <div class="section-kicker">Retention Engine</div>

                <h2 class="section-title">
                    Do not just win new customers.
                    Bring old customers back.
                </h2>

                <p class="section-copy">
                    Most garages already have money sitting inside old WhatsApp chats, service history,
                    invoices, and forgotten customer lists. SayaraForce helps you turn that data into repeat jobs.
                </p>

                <div class="hero-actions">
                    <a href="#audit" class="btn btn-primary">Find My Lost Customers</a>
                    <a href="#pricing" class="btn btn-secondary">See Retention Plan</a>
                </div>
            </div>

            <div class="retention-panel">
                <h3>Repeat service revenue is where garages win.</h3>

                <div class="retention-list">
                    <div class="retention-row">
                        <span class="check">✓</span>
                        <span><strong>Service reminders:</strong> oil change, AC check, tyres, battery, general service, and seasonal checks.</span>
                    </div>

                    <div class="retention-row">
                        <span class="check">✓</span>
                        <span><strong>Lost customer follow-up:</strong> identify customers who have not returned in 3, 6, or 12 months.</span>
                    </div>

                    <div class="retention-row">
                        <span class="check">✓</span>
                        <span><strong>Feedback and review push:</strong> collect feedback after jobs and push happy customers toward Google reviews.</span>
                    </div>

                    <div class="retention-row">
                        <span class="check">✓</span>
                        <span><strong>WhatsApp campaigns:</strong> send structured offers and reminders to the right customer segment.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="orange-band">
        <div class="container orange-grid">
            <div>
                <h2>
                    If your garage misses even 2–3 jobs a month,
                    SayaraForce can pay for itself.
                </h2>

                <p>
                    The launch offer is designed to help early garages recover missed leads and bring back old customers before spending more on ads.
                </p>
            </div>

            <div class="orange-card">
                <div class="mini">Launch Focus</div>
                <strong>Lead Recovery + Retention First</strong>
                <p>We help you identify missed enquiries, forgotten customers, and weak follow-up before scaling campaigns.</p>
            </div>
        </div>
    </section>

    <section id="pricing" class="section pricing">
        <div class="container">
            <div class="section-kicker">Founders Pricing</div>

            <h2 class="section-title">
                Launch pricing for selected early garages.
            </h2>

            <p class="section-copy">
                Draft launch pricing for founder review. First 10 UAE garages can receive 50% off
                for the first 3 months, setup included.
            </p>

            <div class="pricing-grid">
                <div class="price-card">
                    <h3 class="plan-name">Starter</h3>
                    <p class="plan-desc">For small garages starting with lead tracking and WhatsApp follow-up.</p>

                    <div class="old-price">Draft launch range</div>
                    <div class="price">AED 499-699 <span>/month</span></div>

                    <ul class="features-list">
                        <li>Lead capture</li>
                        <li>Client management</li>
                        <li>WhatsApp follow-up tracking</li>
                        <li>Basic booking pipeline</li>
                        <li>Guided setup</li>
                    </ul>

                    <a href="#audit" class="btn btn-secondary">Claim Founders Offer</a>
                </div>

                <div class="price-card featured">
                    <div class="recommended">Recommended</div>

                    <h3 class="plan-name">Growth</h3>
                    <p class="plan-desc">For garages handling WhatsApp, Meta, website leads and retention follow-ups.</p>

                    <div class="old-price">Draft launch price</div>
                    <div class="price">AED 999 <span>/month</span></div>

                    <ul class="features-list">
                        <li>Everything in Starter</li>
                        <li>Meta / website lead handling</li>
                        <li>Opportunity pipeline</li>
                        <li>Retention segments</li>
                        <li>WhatsApp campaign tracking</li>
                        <li>Manager dashboard</li>
                    </ul>

                    <a href="#audit" class="btn btn-primary">Claim Founders Offer</a>
                </div>

                <div class="price-card">
                    <h3 class="plan-name">Pro</h3>
                    <p class="plan-desc">For garages that want full lead recovery, reports, team workflow, and campaigns.</p>

                    <div class="old-price">Draft launch price</div>
                    <div class="price">AED 1,499 <span>/month</span></div>

                    <ul class="features-list">
                        <li>Everything in Growth</li>
                        <li>Advanced dashboard</li>
                        <li>Jobs and invoice tracking</li>
                        <li>Team roles and permissions</li>
                        <li>Advanced campaign reports</li>
                        <li>Priority onboarding</li>
                    </ul>

                    <a href="#audit" class="btn btn-secondary">Claim Founders Offer</a>
                </div>
            </div>

            <p class="section-copy" style="font-size: 12px; margin-top: 28px;">
                WhatsApp/Meta usage and provider fees are separate where applicable. Final pricing requires founder approval.
            </p>
        </div>
    </section>

    <section id="audit" class="section audit">
        <div class="container">
            <div class="audit-box">
                <div>
                    <div class="section-kicker">Free Audit</div>

                    <h2>
                        Get a free 7-day lead recovery audit.
                    </h2>

                    <p>
                        We will review how your garage handles WhatsApp, website, Meta, manual enquiries,
                        and old customer follow-ups. Then we show where leads and repeat jobs are missed.
                    </p>

                    <div class="audit-points">
                        <span>No contract required</span>
                        <span>Setup guidance included</span>
                        <span>Built specifically for garage lead recovery and retention</span>
                    </div>
                </div>

                @if(session('success'))
                    <div class="form-note" style="border-color: rgba(22, 163, 74, 0.35); color: #14532d; background: #dcfce7;">
                        {{ session('success') }}
                    </div>
                @endif

                <form class="form" method="POST" action="{{ route('public.demo.store') }}">
                    @csrf

                    <label>
                        Garage Name
                        <input type="text" name="garage_name" value="{{ old('garage_name') }}" placeholder="Example: City Auto Garage" required>
                        @error('garage_name') <span class="form-note">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Your Name
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Owner / Manager name" required>
                        @error('name') <span class="form-note">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        WhatsApp Number
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+971 5X XXX XXXX" required>
                        @error('phone') <span class="form-note">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="owner@example.com">
                        @error('email') <span class="form-note">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Cars Serviced Monthly
                        <select name="monthly_cars">
                            <option value="">Select approximate volume</option>
                            <option value="under_50">Under 50 cars</option>
                            <option value="50_100">50–100 cars</option>
                            <option value="100_200">100–200 cars</option>
                            <option value="200_plus">200+ cars</option>
                        </select>
                    </label>

                    <label>
                        What should we review?
                        <textarea name="message" rows="4" placeholder="Example: WhatsApp leads, missed follow-ups, booking tracking">{{ old('message') }}</textarea>
                        @error('message') <span class="form-note">{{ $message }}</span> @enderror
                    </label>

                    <button type="submit" class="btn btn-primary">
                        Book Demo / Request Free Audit
                    </button>

                    <p class="form-note">
                        Your request is stored securely for founder follow-up. Live CRM routing is enabled after final approval.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container footer-inner">
            <div>© {{ date('Y') }} SayaraForce. Built for UAE garages.</div>

            <div class="footer-links">
                <a href="https://app.sayaraforce.com/login">Login</a>
                <a href="#pricing">Pricing</a>
                <a href="#audit">Audit</a>
                <a href="{{ route('privacy-policy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
            </div>
        </div>
    </footer>

</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $seoTitle = 'SayaraForce | AI-Assisted Communication and Growth Platform for UAE Garages';
        $seoDescription = 'SayaraForce helps UAE garages capture leads, manage WhatsApp enquiries, prepare AI-assisted replies, improve bookings and bring customers back with reminders and retention tools.';
        $canonicalUrl = 'https://sayaraforce.com/';
        $socialImageUrl = 'https://sayaraforce.com/images/sayaraforce-social-card.png';
        $ga4MeasurementId = config('services.sayaraforce.ga4_measurement_id');

        $faqItems = [
            [
                'question' => 'Is SayaraForce built for garages in the UAE?',
                'answer' => 'Yes. SayaraForce is built around the lead, booking, customer, vehicle, service and retention workflows used by UAE garages and service centres.',
            ],
            [
                'question' => 'Can SayaraForce help with WhatsApp leads?',
                'answer' => 'Yes. SayaraForce can help teams capture and organise WhatsApp enquiries, manage ownership and follow-up status, and keep the path from enquiry to booking visible. Availability depends on the garage\'s approved WhatsApp and provider setup.',
            ],
            [
                'question' => 'Does SayaraForce replace my existing garage management system?',
                'answer' => 'Not necessarily. SayaraForce can be used as a lead recovery, follow-up and retention layer alongside an existing system. The audit identifies the most practical setup for your garage.',
            ],
            [
                'question' => 'Can SayaraForce help bring old customers back?',
                'answer' => 'Yes. Customer history, service reminders, reactivation campaigns and repeat-service workflows help teams identify appropriate follow-up opportunities.',
            ],
            [
                'question' => 'What is included in onboarding?',
                'answer' => 'Onboarding is scoped to the selected plan and garage setup. It can include initial configuration, team guidance, lead or customer import support, workflow setup and a WhatsApp readiness review.',
            ],
            [
                'question' => 'Are WhatsApp and provider charges included?',
                'answer' => 'No. WhatsApp, Meta, AI usage and provider fees may be charged separately where applicable.',
            ],
            [
                'question' => 'Can I upgrade my plan later?',
                'answer' => 'Yes. A garage can discuss moving to a different plan as its team and workflow requirements change.',
            ],
            [
                'question' => 'Does SayaraForce use AI?',
                'answer' => 'Yes. SayaraForce is introducing AI-assisted communication tools that help teams understand customer enquiries, identify urgency and prepare suggested replies. AI features are being introduced gradually, with staff review and control built into the workflow.',
            ],
            [
                'question' => 'Will AI automatically reply to my customers?',
                'answer' => 'Not during the initial rollout. AI prepares suggestions that your staff can review, edit, approve or reject before sending.',
            ],
            [
                'question' => 'Can AI confirm customer bookings?',
                'answer' => 'No. Booking availability and confirmation remain controlled by SayaraForce\'s existing operational workflow and authorised staff.',
            ],
            [
                'question' => 'Which package includes AI?',
                'answer' => 'Sayara AI Communication Copilot is initially being introduced through selected Pro pilot garages. Package availability may expand after the pilot.',
            ],
            [
                'question' => 'Does AI replace my service advisers?',
                'answer' => 'No. It helps service advisers understand enquiries and prepare replies faster. Your team remains responsible for customer communication and operational decisions.',
            ],
            [
                'question' => 'What happens if the AI service is unavailable?',
                'answer' => 'SayaraForce\'s normal lead, inbox, booking and follow-up workflows continue to operate. AI assistance is designed not to block core garage operations.',
            ],
        ];

        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://sayaraforce.com/#organization',
                    'name' => 'SayaraForce',
                    'url' => $canonicalUrl,
                    'logo' => 'https://sayaraforce.com/images/brand/sayaraforce-logo-horizontal.png',
                    'description' => 'SayaraForce is an AI-assisted growth and communication platform for UAE garages, combining reliable automation with human-controlled communication assistance.',
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => 'https://sayaraforce.com/#software',
                    'name' => 'SayaraForce',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'url' => $canonicalUrl,
                    'logo' => 'https://sayaraforce.com/images/brand/sayaraforce-app-icon-512.png',
                    'description' => $seoDescription,
                    'publisher' => [
                        '@id' => 'https://sayaraforce.com/#organization',
                    ],
                    'offers' => [
                        [
                            '@type' => 'Offer',
                            'name' => 'Starter',
                            'priceCurrency' => 'AED',
                            'price' => '999',
                            'url' => 'https://sayaraforce.com/#pricing',
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'Growth',
                            'priceCurrency' => 'AED',
                            'price' => '1499',
                            'url' => 'https://sayaraforce.com/#pricing',
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'Pro',
                            'priceCurrency' => 'AED',
                            'price' => '1999',
                            'url' => 'https://sayaraforce.com/#pricing',
                        ],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => 'https://sayaraforce.com/#faq-schema',
                    'mainEntity' => array_map(
                        fn (array $item) => [
                            '@type' => 'Question',
                            'name' => $item['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $item['answer'],
                            ],
                        ],
                        $faqItems
                    ),
                ],
            ],
        ];
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SayaraForce">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $socialImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="SayaraForce AI-assisted communication and growth platform for UAE garages">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $socialImageUrl }}">
    <meta name="twitter:image:alt" content="SayaraForce AI-assisted communication and growth platform for UAE garages">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0D1B3D">
    <link rel="stylesheet" href="/css/sayaraforce-brand.css">
    <link rel="preload" href="/fonts/exo-2-latin-600.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/inter-latin-400-600.woff2" as="font" type="font/woff2" crossorigin>

    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    @if($ga4MeasurementId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $ga4MeasurementId }}');
        </script>
    @endif

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 88px;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: var(--ink);
            color: var(--text-on-dark);
            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 18px;
            font-weight: 400;
            line-height: 1.65;
            text-rendering: optimizeLegibility;
        }

        body.menu-open {
            overflow: hidden;
        }

        img,
        svg {
            display: block;
            max-width: 100%;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        :focus-visible {
            outline: 3px solid var(--brand-orange);
            outline-offset: 3px;
            box-shadow: 0 0 0 2px var(--brand-white);
        }

        .skip-link {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 200;
            transform: translateY(-160%);
            border-radius: 10px;
            background: var(--brand-white);
            color: var(--ink);
            padding: 10px 16px;
            font-weight: 600;
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        .container {
            width: min(1240px, calc(100% - 48px));
            margin-inline: auto;
        }

        .dark-section {
            background: var(--ink);
            color: var(--text-on-dark);
        }

        .light-section {
            background: var(--paper);
            color: var(--text-on-light);
        }

        .section {
            padding: 96px 0;
            scroll-margin-top: 84px;
        }

        .section-label {
            margin: 0 0 14px;
            color: var(--orange-light);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .light-section .section-label {
            color: var(--brand-navy);
        }

        h1,
        h2,
        h3,
        p {
            overflow-wrap: anywhere;
        }

        h1,
        h2,
        h3 {
            margin-top: 0;
            text-wrap: balance;
        }

        h1,
        h2,
        .plan-name,
        .plan-price strong {
            font-family: "Exo 2", "Inter", sans-serif;
            font-weight: 600;
        }

        h2 {
            margin-bottom: 18px;
            font-size: clamp(2.25rem, 4.4vw, 4rem);
            line-height: 1.02;
            letter-spacing: -0.025em;
        }

        .section-intro {
            max-width: 760px;
            margin: 0;
            color: var(--muted-on-dark);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .light-section .section-intro {
            color: var(--muted-on-light);
        }

        .button {
            display: inline-flex;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 0.94rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            transition: transform 160ms ease, background-color 160ms ease, border-color 160ms ease;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            background: var(--orange);
            color: var(--brand-navy);
            box-shadow: 0 14px 34px rgba(255, 106, 0, 0.25);
        }

        .button-primary:hover {
            background: var(--orange-hover);
        }

        .button-secondary {
            border-color: rgba(245, 247, 250, 0.3);
            background: rgba(245, 247, 250, 0.05);
            color: var(--text-on-dark);
        }

        .button-secondary:hover {
            border-color: rgba(245, 247, 250, 0.58);
            background: rgba(245, 247, 250, 0.1);
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(13, 27, 61, 0.12);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(18px);
        }

        .nav-shell {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 26px;
        }

        .brand {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0;
            flex: 0 0 auto;
        }

        .brand-logo-full {
            width: 226px;
            height: auto;
        }

        .brand-logo-icon {
            width: 52px;
            height: 49px;
            display: none;
            object-fit: contain;
        }

        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            margin-left: auto;
        }

        .desktop-nav a,
        .login-link {
            color: var(--brand-navy);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .desktop-nav a:hover,
        .login-link:hover {
            color: var(--brand-navy);
            text-decoration: underline;
            text-decoration-color: var(--brand-orange);
            text-decoration-thickness: 2px;
            text-underline-offset: 6px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .nav-actions .button {
            min-height: 44px;
            padding: 10px 17px;
        }

        .menu-button {
            width: 46px;
            height: 46px;
            display: none;
            place-items: center;
            border: 1px solid rgba(13, 27, 61, 0.22);
            border-radius: 12px;
            background: var(--brand-white);
            color: var(--brand-navy);
            cursor: pointer;
        }

        .menu-lines,
        .menu-lines::before,
        .menu-lines::after {
            width: 20px;
            height: 2px;
            display: block;
            border-radius: 99px;
            background: currentColor;
            content: "";
            transition: transform 160ms ease, opacity 160ms ease;
        }

        .menu-lines {
            position: relative;
        }

        .menu-lines::before {
            position: absolute;
            top: -6px;
        }

        .menu-lines::after {
            position: absolute;
            top: 6px;
        }

        .menu-button[aria-expanded="true"] .menu-lines {
            background: transparent;
        }

        .menu-button[aria-expanded="true"] .menu-lines::before {
            top: 0;
            transform: rotate(45deg);
        }

        .menu-button[aria-expanded="true"] .menu-lines::after {
            top: 0;
            transform: rotate(-45deg);
        }

        .mobile-nav {
            display: none;
        }

        .hero {
            position: relative;
            padding: 82px 0 72px;
            overflow: hidden;
            background:
                radial-gradient(circle at 88% 18%, rgba(255, 106, 0, 0.17), transparent 28%),
                radial-gradient(circle at 8% 10%, rgba(255, 255, 255, 0.06), transparent 25%),
                var(--ink);
        }

        .hero::after {
            position: absolute;
            right: -160px;
            bottom: -320px;
            width: 620px;
            height: 620px;
            border: 1px solid rgba(255, 106, 0, 0.15);
            border-radius: 50%;
            content: "";
            box-shadow:
                0 0 0 62px rgba(255, 106, 0, 0.035),
                0 0 0 124px rgba(255, 106, 0, 0.025);
            pointer-events: none;
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(570px, 1.05fr);
            gap: 56px;
            align-items: center;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            border: 1px solid rgba(255, 178, 127, 0.42);
            border-radius: 999px;
            padding: 7px 12px;
            color: var(--orange-light);
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 0.1em;
        }

        .hero-eyebrow::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--orange);
            content: "";
        }

        .hero h1 {
            max-width: 760px;
            margin: 22px 0 0;
            font-size: clamp(2.75rem, 3.25vw, 2.8rem);
            font-weight: 600;
            line-height: 1.02;
            letter-spacing: -0.035em;
        }

        .hero h1 .hero-line {
            display: block;
        }

        .hero h1 .hero-highlight {
            color: var(--orange);
        }

        .hero h1 .hero-keep {
            display: inline;
            white-space: nowrap;
        }

        .hero-copy {
            max-width: 680px;
            margin: 24px 0 0;
            color: var(--muted-on-dark);
            font-size: 1.03rem;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
            margin-top: 30px;
        }

        .hero-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 22px;
            margin: 28px 0 0;
            padding: 0;
            list-style: none;
            color: #D9E0EC;
            font-size: 0.84rem;
            font-weight: 500;
        }

        .hero-facts li {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-facts li::before {
            width: 8px;
            height: 8px;
            border: 2px solid var(--orange);
            border-radius: 50%;
            content: "";
        }

        .hero-control-note {
            max-width: 650px;
            margin: 26px 0 0;
            border-left: 3px solid var(--orange);
            color: #D9E0EC;
            padding-left: 14px;
            font-size: 0.84rem;
            font-weight: 500;
            line-height: 1.55;
        }

        .product-preview {
            position: relative;
            min-width: 0;
            border: 1px solid rgba(245, 247, 250, 0.15);
            border-radius: var(--radius-lg);
            padding: 12px;
            background: rgba(245, 247, 250, 0.055);
            box-shadow: var(--shadow-dark);
        }

        .preview-caption {
            position: absolute;
            top: -15px;
            right: 24px;
            z-index: 2;
            border: 1px solid rgba(255, 178, 127, 0.4);
            border-radius: 999px;
            background: var(--ink);
            color: var(--orange-light);
            padding: 5px 11px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .product-shell {
            min-height: 508px;
            display: grid;
            grid-template-columns: 152px minmax(0, 1fr);
            overflow: hidden;
            border: 1px solid rgba(245, 247, 250, 0.08);
            border-radius: 20px;
            background: #11234A;
        }

        .product-sidebar {
            border-right: 1px solid rgba(245, 247, 250, 0.08);
            padding: 21px 14px;
            background: #0B1835;
        }

        .product-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            color: var(--brand-white);
        }

        .product-brand img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .product-nav {
            display: grid;
            gap: 7px;
        }

        .product-nav span {
            display: flex;
            min-height: 36px;
            align-items: center;
            gap: 9px;
            border-radius: 9px;
            padding: 7px 9px;
            color: #B9C4D8;
            font-size: 0.68rem;
            font-weight: 500;
        }

        .product-nav span::before {
            width: 8px;
            height: 8px;
            border: 1px solid currentColor;
            border-radius: 3px;
            content: "";
        }

        .product-nav span.active {
            background: rgba(255, 106, 0, 0.16);
            color: var(--brand-white);
        }

        .product-nav span.active::before {
            border-color: var(--orange);
            background: var(--orange);
        }

        .product-main {
            min-width: 0;
            padding: 24px;
        }

        .product-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .product-kicker {
            color: #AAB6CC;
            font-size: 0.65rem;
            font-weight: 500;
        }

        .product-title {
            margin-top: 2px;
            color: var(--brand-white);
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: -0.015em;
        }

        .workspace-pill {
            border: 1px solid rgba(245, 247, 250, 0.1);
            border-radius: 999px;
            background: #1B315F;
            color: #E3E8F1;
            padding: 6px 10px;
            font-size: 0.64rem;
            font-weight: 600;
        }

        .workspace-pill.human-review {
            border-color: rgba(255, 178, 127, 0.42);
            background: rgba(255, 106, 0, 0.14);
            color: var(--orange-light);
        }

        .pipeline-labels {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
            margin-top: 24px;
        }

        .pipeline-label {
            border-radius: 10px;
            background: #182E5A;
            padding: 10px;
        }

        .pipeline-label span {
            display: block;
            color: var(--brand-white);
            font-size: 0.67rem;
            font-weight: 600;
        }

        .pipeline-label small {
            display: block;
            margin-top: 3px;
            color: #AEB9CD;
            font-size: 0.57rem;
            line-height: 1.35;
        }

        .pipeline-label.is-current {
            outline: 1px solid rgba(255, 106, 0, 0.55);
            background: rgba(255, 106, 0, 0.13);
        }

        .workspace-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(190px, 0.8fr);
            gap: 12px;
            margin-top: 12px;
        }

        .workspace-panel {
            min-width: 0;
            border: 1px solid rgba(245, 247, 250, 0.08);
            border-radius: 14px;
            background: #142851;
            padding: 15px;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--brand-white);
            font-size: 0.72rem;
            font-weight: 600;
        }

        .panel-heading em {
            color: var(--orange-light);
            font-size: 0.58rem;
            font-style: normal;
        }

        .demo-record {
            margin-top: 12px;
            border: 1px solid rgba(245, 247, 250, 0.08);
            border-radius: 12px;
            background: #1B315F;
            padding: 12px;
        }

        .record-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .record-top strong {
            color: var(--brand-white);
            font-size: 0.7rem;
        }

        .status-tag {
            border-radius: 999px;
            background: rgba(255, 106, 0, 0.15);
            color: var(--orange-light);
            padding: 3px 7px;
            font-size: 0.54rem;
            font-weight: 600;
        }

        .record-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 11px;
        }

        .record-meta div {
            border-radius: 8px;
            background: #102143;
            padding: 8px;
        }

        .record-meta small,
        .record-meta span {
            display: block;
        }

        .record-meta small {
            color: #9EABC2;
            font-size: 0.52rem;
        }

        .record-meta span {
            margin-top: 2px;
            color: #DCE3EE;
            font-size: 0.59rem;
            font-weight: 500;
        }

        .activity-list {
            display: grid;
            gap: 9px;
            margin-top: 12px;
        }

        .activity-item {
            display: grid;
            grid-template-columns: 9px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            color: #CBD4E4;
            font-size: 0.58rem;
            line-height: 1.45;
        }

        .activity-item::before {
            width: 7px;
            height: 7px;
            margin-top: 3px;
            border: 2px solid var(--orange);
            border-radius: 50%;
            content: "";
        }

        .ai-insight-list {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .ai-insight-row {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
            gap: 8px;
            border-radius: 9px;
            background: #102143;
            padding: 8px 9px;
            font-size: 0.58rem;
            line-height: 1.4;
        }

        .ai-insight-row span:first-child {
            color: #9EABC2;
        }

        .ai-insight-row strong {
            color: #E7EBF3;
            font-weight: 600;
        }

        .suggested-reply-preview {
            margin-top: 10px;
            border: 1px solid rgba(255, 178, 127, 0.24);
            border-radius: 10px;
            background: rgba(255, 106, 0, 0.07);
            padding: 10px;
        }

        .suggested-reply-preview strong,
        .suggested-reply-preview span {
            display: block;
        }

        .suggested-reply-preview strong {
            color: var(--orange-light);
            font-size: 0.6rem;
        }

        .suggested-reply-preview span {
            margin-top: 5px;
            color: #DCE3EE;
            font-size: 0.57rem;
            line-height: 1.45;
        }

        .preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 9px;
        }

        .preview-action {
            min-height: 27px;
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(245, 247, 250, 0.12);
            border-radius: 7px;
            background: #1B315F;
            color: #E7EBF3;
            padding: 4px 7px;
            font-family: inherit;
            font-size: 0.51rem;
            font-weight: 600;
        }

        .preview-action.primary {
            border-color: var(--orange);
            background: var(--orange);
            color: var(--brand-navy);
        }

        .capability-strip {
            border-top: 1px solid var(--line-dark);
            border-bottom: 1px solid var(--line-dark);
            background: #0A1733;
        }

        .capability-list {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .capability-list li {
            min-height: 86px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border-right: 1px solid var(--line-dark);
            color: #E7EBF3;
            padding: 16px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.35;
            text-align: center;
        }

        .capability-list li:first-child {
            border-left: 1px solid var(--line-dark);
        }

        .capability-list li::before {
            width: 9px;
            height: 9px;
            flex: 0 0 9px;
            border-radius: 3px;
            background: var(--orange);
            content: "";
        }

        .capability-positioning {
            margin: 0;
            border-bottom: 1px solid var(--line-dark);
            color: #C7D0E0;
            padding: 15px 10px;
            font-size: 0.82rem;
            font-weight: 500;
            text-align: center;
        }

        .problem-layout {
            display: grid;
            grid-template-columns: minmax(280px, 0.68fr) minmax(0, 1.32fr);
            gap: 68px;
            align-items: end;
        }

        .problem-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 46px;
        }

        .problem-card {
            min-height: 168px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid var(--line-light);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.78);
            padding: 22px 18px;
            box-shadow: 0 12px 30px rgba(7, 17, 31, 0.04);
        }

        .problem-number {
            color: var(--brand-navy);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .problem-card h3 {
            margin: 36px 0 0;
            color: var(--text-on-light);
            font-size: 1.02rem;
            line-height: 1.25;
            letter-spacing: -0.025em;
        }

        .solution-section {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.04), transparent 45%),
                var(--ink);
        }

        .section-heading-row {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 48px;
        }

        .section-heading-row > div:first-child {
            max-width: 760px;
        }

        .workflow-note {
            flex: 0 0 auto;
            border-left: 3px solid var(--orange);
            color: #D8DFEB;
            padding-left: 16px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .journey {
            position: relative;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0;
            margin-top: 48px;
            overflow: hidden;
            border: 1px solid var(--line-dark);
            border-radius: var(--radius-lg);
            background: var(--ink-raised);
            box-shadow: var(--shadow-dark);
        }

        .journey-stage {
            position: relative;
            min-height: 430px;
            border-right: 1px solid var(--line-dark);
            padding: 26px 22px;
        }

        .journey-stage:last-child {
            border-right: 0;
        }

        .journey-stage:not(:last-child)::after {
            position: absolute;
            top: 58px;
            right: -14px;
            z-index: 2;
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 178, 127, 0.4);
            border-radius: 50%;
            background: var(--ink-raised);
            color: var(--orange-light);
            content: "\2192";
            font-size: 0.82rem;
            font-weight: 600;
        }

        .stage-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .stage-index {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(255, 106, 0, 0.15);
            color: var(--orange-light);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .stage-status {
            color: #A2AEC3;
            font-size: 0.65rem;
            font-weight: 500;
        }

        .journey-stage h3 {
            margin: 34px 0 8px;
            color: var(--brand-white);
            font-size: 1.42rem;
            letter-spacing: -0.02em;
        }

        .stage-summary {
            min-height: 52px;
            margin: 0;
            color: var(--muted-on-dark);
            font-size: 0.83rem;
            line-height: 1.55;
        }

        .stage-features {
            display: grid;
            gap: 12px;
            margin: 26px 0 0;
            padding: 0;
            list-style: none;
        }

        .stage-features li {
            display: grid;
            grid-template-columns: 8px minmax(0, 1fr);
            gap: 10px;
            color: #E2E7EE;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .stage-features li::before {
            width: 7px;
            height: 7px;
            margin-top: 7px;
            border-radius: 2px;
            background: var(--orange);
            content: "";
        }

        .ai-communication-section {
            border-top: 1px solid var(--line-dark);
            background:
                radial-gradient(circle at 82% 22%, rgba(255, 106, 0, 0.1), transparent 28%),
                #11234A;
        }

        .ai-section-heading {
            max-width: 850px;
        }

        .ai-capabilities {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 42px;
        }

        .ai-capability-card {
            min-height: 230px;
            border: 1px solid var(--line-dark);
            border-radius: var(--radius-md);
            background: rgba(7, 17, 42, 0.42);
            padding: 24px 22px;
        }

        .ai-card-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 178, 127, 0.35);
            border-radius: 12px;
            color: var(--orange-light);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .ai-capability-card h3 {
            margin: 28px 0 10px;
            color: var(--brand-white);
            font-size: 1.15rem;
        }

        .ai-capability-card p {
            margin: 0;
            color: var(--muted-on-dark);
            font-size: 0.86rem;
            line-height: 1.6;
        }

        .ai-safety-note {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 0;
            border: 1px solid rgba(255, 178, 127, 0.28);
            border-radius: 14px;
            background: rgba(255, 106, 0, 0.08);
            color: #F3F5F8;
            padding: 16px 18px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .ai-safety-note::before {
            width: 10px;
            height: 10px;
            flex: 0 0 10px;
            border-radius: 50%;
            background: var(--orange);
            content: "";
        }

        .automation-section {
            background: var(--surface-light);
        }

        .automation-heading {
            max-width: 780px;
        }

        .automation-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 40px;
        }

        .automation-column {
            border: 1px solid var(--line-light);
            border-radius: var(--radius-md);
            background: var(--brand-white);
            padding: 28px;
            box-shadow: 0 12px 30px rgba(7, 17, 31, 0.04);
        }

        .automation-column h3 {
            margin: 0;
            color: var(--text-on-light);
            font-size: 1.3rem;
        }

        .automation-column ul {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 11px 18px;
            margin: 24px 0 0;
            padding: 0;
            list-style: none;
        }

        .automation-column li {
            display: grid;
            grid-template-columns: 8px minmax(0, 1fr);
            gap: 9px;
            color: #34425E;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .automation-column li::before {
            width: 7px;
            height: 7px;
            margin-top: 7px;
            border-radius: 2px;
            background: var(--orange);
            content: "";
        }

        .automation-statement {
            margin: 26px 0 0;
            border-left: 3px solid var(--orange);
            color: var(--text-on-light);
            padding-left: 16px;
            font-size: 0.94rem;
            font-weight: 600;
            line-height: 1.6;
        }

        .retention-section {
            border-top: 1px solid var(--line-dark);
            background: #0A1733;
        }

        .retention-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.85fr) minmax(520px, 1.15fr);
            gap: 72px;
            align-items: center;
        }

        .retention-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 34px 0 0;
            padding: 0;
            list-style: none;
        }

        .retention-list li {
            min-height: 104px;
            border: 1px solid var(--line-dark);
            border-radius: 15px;
            background: rgba(245, 247, 250, 0.035);
            color: #EFF2F6;
            padding: 18px;
            font-size: 0.86rem;
            font-weight: 500;
        }

        .retention-list li::before {
            width: 10px;
            height: 10px;
            display: block;
            margin-bottom: 17px;
            border: 2px solid var(--orange);
            border-radius: 50%;
            content: "";
        }

        .retention-preview {
            border: 1px solid var(--line-dark);
            border-radius: var(--radius-lg);
            background: var(--ink);
            padding: 22px;
            box-shadow: var(--shadow-dark);
        }

        .retention-preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--line-dark);
        }

        .retention-preview-header strong {
            font-size: 1rem;
        }

        .retention-preview-header span {
            color: var(--orange-light);
            font-size: 0.72rem;
            font-weight: 600;
        }

        .customer-record {
            display: grid;
            grid-template-columns: 0.82fr 1.18fr;
            gap: 14px;
            margin-top: 16px;
        }

        .record-card {
            border: 1px solid var(--line-dark);
            border-radius: 16px;
            background: var(--ink-raised);
            padding: 18px;
        }

        .record-card small {
            display: block;
            color: #A8B4C9;
            font-size: 0.65rem;
            font-weight: 500;
        }

        .record-card h3 {
            margin: 8px 0 0;
            color: var(--brand-white);
            font-size: 1.05rem;
            letter-spacing: -0.015em;
        }

        .record-details {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .record-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border-top: 1px solid var(--line-dark);
            padding-top: 10px;
            color: #CBD3DD;
            font-size: 0.7rem;
        }

        .record-detail span:last-child {
            color: var(--brand-white);
            font-weight: 600;
            text-align: right;
        }

        .timeline {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .timeline-dot {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: rgba(255, 106, 0, 0.14);
            color: var(--orange-light);
            font-size: 0.62rem;
            font-weight: 600;
        }

        .timeline-item strong,
        .timeline-item span {
            display: block;
        }

        .timeline-item strong {
            color: var(--brand-white);
            font-size: 0.74rem;
        }

        .timeline-item span {
            margin-top: 2px;
            color: #AEB9CD;
            font-size: 0.64rem;
            line-height: 1.45;
        }

        .pricing-section {
            padding-bottom: 52px;
        }

        .pricing-heading {
            max-width: 770px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 48px;
        }

        .price-card {
            position: relative;
            min-width: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--line-light);
            border-radius: 22px;
            background: var(--paper-raised);
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .price-card.recommended {
            border: 2px solid var(--orange);
            transform: translateY(-8px);
        }

        .recommended-label {
            position: absolute;
            top: -16px;
            left: 24px;
            border-radius: 999px;
            background: var(--orange);
            color: var(--ink);
            padding: 6px 11px;
            font-size: 0.69rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .plan-name {
            margin: 0;
            color: var(--text-on-light);
            font-size: 1.28rem;
            letter-spacing: -0.015em;
        }

        .plan-positioning {
            min-height: 86px;
            margin: 11px 0 0;
            color: var(--muted-on-light);
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .plan-price {
            display: flex;
            align-items: end;
            gap: 7px;
            margin-top: 23px;
            color: var(--text-on-light);
        }

        .plan-price strong {
            font-size: clamp(2rem, 3vw, 2.75rem);
            line-height: 1;
            letter-spacing: -0.025em;
        }

        .plan-price span {
            color: var(--muted-on-light);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .plan-features {
            display: grid;
            gap: 12px;
            margin: 26px 0 30px;
            padding: 24px 0 0;
            border-top: 1px solid var(--line-light);
            list-style: none;
        }

        .plan-features li {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 9px;
            color: #34425E;
            font-size: 0.83rem;
            line-height: 1.45;
        }

        .plan-features li::before {
            width: 18px;
            height: 18px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #FFF0E6;
            color: var(--brand-navy);
            content: "\2713";
            font-size: 0.65rem;
            font-weight: 600;
        }

        .plan-pilot-note {
            margin: -8px 0 22px;
            border-left: 3px solid var(--orange);
            color: #43516D;
            padding-left: 11px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .price-card .button {
            width: 100%;
            margin-top: auto;
            border-color: #C9D0DE;
            background: var(--brand-white);
            color: var(--text-on-light);
        }

        .price-card.recommended .button {
            border-color: var(--orange);
            background: var(--orange);
            color: var(--ink);
        }

        .pricing-note {
            margin: 28px 0 0;
            border-left: 3px solid var(--orange);
            color: #43516D;
            padding-left: 14px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .faq-section {
            padding-top: 52px;
        }

        .faq-layout {
            display: grid;
            grid-template-columns: minmax(280px, 0.66fr) minmax(0, 1.34fr);
            gap: 68px;
            align-items: start;
            border-top: 1px solid var(--line-light);
            padding-top: 60px;
        }

        .faq-layout h2 {
            font-size: clamp(2.2rem, 3.6vw, 3.4rem);
        }

        .accordion {
            display: grid;
            gap: 10px;
        }

        .faq-item {
            overflow: hidden;
            border: 1px solid var(--line-light);
            border-radius: 14px;
            background: var(--brand-white);
        }

        .faq-question {
            width: 100%;
            min-height: 64px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 30px;
            gap: 18px;
            align-items: center;
            border: 0;
            background: transparent;
            color: var(--text-on-light);
            padding: 16px 18px;
            cursor: pointer;
            font-size: 0.91rem;
            font-weight: 600;
            line-height: 1.4;
            text-align: left;
        }

        .faq-icon {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border: 1px solid #CBD2E0;
            border-radius: 9px;
            color: var(--brand-navy);
            transition: transform 160ms ease;
        }

        .faq-question[aria-expanded="true"] .faq-icon {
            transform: rotate(45deg);
        }

        .faq-answer {
            padding: 0 18px 20px;
        }

        .faq-answer p {
            max-width: 760px;
            margin: 0;
            color: var(--muted-on-light);
            font-size: 0.87rem;
            line-height: 1.65;
        }

        .audit-section {
            position: relative;
            overflow: hidden;
            border-top: 1px solid var(--line-dark);
            background:
                radial-gradient(circle at 8% 25%, rgba(255, 106, 0, 0.13), transparent 27%),
                var(--ink);
        }

        .audit-layout {
            display: grid;
            grid-template-columns: minmax(280px, 0.75fr) minmax(0, 1.25fr);
            gap: 70px;
            align-items: start;
        }

        .audit-copy {
            position: sticky;
            top: 116px;
        }

        .audit-copy h2 {
            font-size: clamp(2.5rem, 4.5vw, 4.1rem);
        }

        .audit-benefits {
            display: grid;
            gap: 13px;
            margin: 32px 0 0;
            padding: 0;
            list-style: none;
        }

        .audit-benefits li {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 10px;
            color: #E4E9F1;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .audit-benefits li::before {
            width: 21px;
            height: 21px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(255, 106, 0, 0.16);
            color: var(--orange-light);
            content: "\2713";
            font-size: 0.7rem;
            font-weight: 600;
        }

        .audit-pilot-note {
            margin: 26px 0 0;
            border-left: 3px solid var(--orange);
            color: #D9E0EC;
            padding-left: 14px;
            font-size: 0.82rem;
            font-weight: 500;
            line-height: 1.55;
        }

        .audit-form {
            border: 1px solid var(--line-dark);
            border-radius: var(--radius-lg);
            background: var(--ink-raised);
            padding: 30px;
            box-shadow: var(--shadow-dark);
        }

        .form-alert {
            margin-bottom: 20px;
            border: 1px solid var(--brand-orange);
            border-radius: 12px;
            background: rgba(255, 106, 0, 0.13);
            color: #FFE3D1;
            padding: 14px 16px;
            font-size: 0.82rem;
        }

        .form-alert strong {
            display: block;
            margin-bottom: 4px;
            color: var(--brand-white);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-field {
            min-width: 0;
        }

        .form-field.full {
            grid-column: 1 / -1;
        }

        .form-field label {
            display: block;
            margin-bottom: 7px;
            color: #EEF2F7;
            font-size: 0.77rem;
            font-weight: 600;
        }

        .required-note {
            color: #B7C2D6;
            font-weight: 500;
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            min-height: 50px;
            border: 1px solid #52627F;
            border-radius: 11px;
            background: #0A1733;
            color: var(--brand-white);
            padding: 12px 13px;
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .form-field textarea {
            min-height: 116px;
            resize: vertical;
        }

        .form-field input::placeholder,
        .form-field textarea::placeholder {
            color: #A7B2C7;
            opacity: 1;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: var(--brand-orange);
            outline: 2px solid rgba(255, 106, 0, 0.32);
            outline-offset: 1px;
        }

        .field-error {
            display: block;
            margin-top: 6px;
            color: #FFD1B3;
            font-size: 0.73rem;
            font-weight: 500;
        }

        .audit-form .button {
            width: 100%;
            margin-top: 20px;
        }

        .form-privacy {
            margin: 12px 0 0;
            color: #B5C0D3;
            font-size: 0.72rem;
            line-height: 1.5;
            text-align: center;
        }

        .form-privacy a {
            color: var(--brand-white);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .site-footer {
            border-top: 1px solid var(--line-light);
            background: var(--brand-white);
            color: var(--text-on-light);
            padding: 64px 0 28px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1.5fr) repeat(2, minmax(150px, 0.5fr));
            gap: 62px;
        }

        .footer-description {
            max-width: 460px;
            margin: 18px 0 0;
            color: var(--muted-on-light);
            font-size: 0.83rem;
            line-height: 1.65;
        }

        .footer-column h2 {
            margin: 0 0 16px;
            color: var(--brand-navy);
            font-size: 0.82rem;
            letter-spacing: 0;
        }

        .footer-links {
            display: grid;
            gap: 10px;
        }

        .footer-links a {
            width: fit-content;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            color: var(--muted-on-light);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .footer-links a:hover {
            color: var(--brand-navy);
            text-decoration: underline;
            text-decoration-color: var(--brand-orange);
            text-decoration-thickness: 2px;
            text-underline-offset: 4px;
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            margin-top: 48px;
            border-top: 1px solid var(--line-light);
            padding-top: 24px;
            color: #64718A;
            font-size: 0.72rem;
        }

        .footer-logo {
            width: min(340px, 100%);
            height: auto;
        }

        @media (max-width: 1120px) {
            .desktop-nav {
                gap: 18px;
            }

            .hero-grid {
                grid-template-columns: 1fr;
                gap: 58px;
            }

            .hero h1 {
                font-size: clamp(3.1rem, 6vw, 4.3rem);
            }

            .hero-copy {
                max-width: 720px;
            }

            .product-preview {
                width: 100%;
                max-width: 920px;
                margin-inline: auto;
            }

            .product-main {
                padding: 20px;
            }

            .problem-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .problem-card {
                min-height: 175px;
            }

            .journey {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .journey-stage {
                min-height: 370px;
            }

            .ai-capabilities {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .retention-grid {
                grid-template-columns: minmax(0, 0.8fr) minmax(470px, 1.2fr);
                gap: 44px;
            }

            .price-card {
                padding: 26px 22px;
            }
        }

        @media (max-width: 980px) {
            .container {
                width: min(100% - 40px, 900px);
            }

            .section {
                padding: 80px 0;
            }

            .desktop-nav,
            .nav-actions {
                display: none;
            }

            .menu-button {
                display: grid;
            }

            .mobile-nav {
                position: fixed;
                inset: 77px 0 0;
                z-index: 90;
                overflow-y: auto;
                background: rgba(13, 27, 61, 0.99);
                padding: 24px 20px 40px;
            }

            .mobile-nav.is-open {
                display: block;
            }

            .mobile-nav-inner {
                width: min(100%, 900px);
                display: grid;
                gap: 4px;
                margin-inline: auto;
            }

            .mobile-nav a {
                min-height: 52px;
                display: flex;
                align-items: center;
                border-bottom: 1px solid var(--line-dark);
                color: var(--brand-white);
                padding: 10px 4px;
                font-size: 1rem;
                font-weight: 600;
            }

            .mobile-nav .button {
                margin-top: 16px;
                border-bottom: 0;
                color: var(--brand-navy);
            }

            .hero {
                padding-top: 68px;
            }

            .hero-grid,
            .problem-layout,
            .retention-grid,
            .faq-layout,
            .audit-layout {
                grid-template-columns: 1fr;
            }

            .hero-grid {
                gap: 58px;
            }

            .hero-copy {
                max-width: 720px;
            }

            .product-preview {
                max-width: 820px;
            }

            .capability-list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .capability-list li:nth-child(3) {
                border-right: 1px solid var(--line-dark);
            }

            .problem-layout {
                gap: 12px;
            }

            .problem-layout .section-intro {
                max-width: 720px;
            }

            .journey {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .journey-stage {
                min-height: 360px;
                border-bottom: 1px solid var(--line-dark);
            }

            .journey-stage:nth-child(even) {
                border-right: 0;
            }

            .journey-stage:last-child {
                border-bottom: 0;
            }

            .journey-stage::after {
                display: none;
            }

            .retention-grid {
                gap: 44px;
            }

            .pricing-grid {
                gap: 13px;
            }

            .plan-positioning {
                min-height: 108px;
            }

            .faq-layout {
                gap: 30px;
            }

            .audit-layout {
                gap: 42px;
            }

            .audit-copy {
                position: static;
            }
        }

        @media (max-width: 820px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .price-card.recommended {
                order: -1;
                transform: none;
            }

            .plan-positioning {
                min-height: 0;
            }
        }

        @media (max-width: 760px) {
            body {
                font-size: 16px;
            }

            .container {
                width: min(100% - 32px, 680px);
            }

            .section {
                padding: 68px 0;
            }

            h2 {
                font-size: clamp(2.25rem, 10vw, 3.2rem);
            }

            .nav-shell {
                min-height: 70px;
            }

            .brand-logo-full {
                display: none;
            }

            .brand-logo-icon {
                display: block;
            }

            .mobile-nav {
                top: 71px;
            }

            .hero {
                padding: 58px 0 58px;
            }

            .hero h1 {
                font-size: clamp(2.05rem, 9.2vw, 3.1rem);
                line-height: 1.06;
            }

            .hero-actions .button {
                flex: 1 1 220px;
            }

            .product-shell {
                min-height: 0;
                grid-template-columns: 1fr;
            }

            .product-sidebar {
                border-right: 0;
                border-bottom: 1px solid var(--line-dark);
                padding: 14px;
            }

            .product-brand {
                margin-bottom: 12px;
            }

            .product-nav {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 4px;
            }

            .product-nav span {
                min-height: 38px;
                justify-content: center;
                padding: 6px 4px;
                font-size: 0.57rem;
                text-align: center;
            }

            .product-nav span::before {
                display: none;
            }

            .product-main {
                padding: 18px;
            }

            .workspace-grid {
                grid-template-columns: 1fr;
            }

            .capability-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .capability-list li {
                min-height: 72px;
                border-bottom: 1px solid var(--line-dark);
            }

            .problem-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 34px;
            }

            .section-heading-row {
                display: block;
            }

            .workflow-note {
                margin-top: 24px;
            }

            .journey {
                grid-template-columns: 1fr;
            }

            .journey-stage {
                min-height: 0;
                border-right: 0;
                border-bottom: 1px solid var(--line-dark) !important;
                padding: 26px 24px 30px;
            }

            .journey-stage:last-child {
                border-bottom: 0 !important;
            }

            .journey-stage:not(:last-child)::after {
                top: auto;
                right: auto;
                bottom: -14px;
                left: 50%;
                display: grid;
                content: "\2193";
            }

            .journey-stage:nth-child(2)::after {
                display: grid;
            }

            .stage-summary {
                min-height: 0;
            }

            .ai-capabilities,
            .automation-grid {
                grid-template-columns: 1fr;
            }

            .customer-record {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .price-card.recommended {
                order: -1;
                transform: none;
            }

            .plan-positioning {
                min-height: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-field.full {
                grid-column: auto;
            }

            .form-privacy a {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
            }

            .footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 36px;
            }

            .footer-brand {
                grid-column: 1 / -1;
            }

            .footer-bottom {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 460px) {
            .container {
                width: calc(100% - 28px);
            }

            .hero-actions {
                display: grid;
            }

            .hero-actions .button {
                width: 100%;
            }

            .hero-facts {
                display: grid;
                gap: 10px;
            }

            .product-preview {
                margin-inline: -4px;
                padding: 8px;
                border-radius: 20px;
            }

            .preview-caption {
                right: 14px;
            }

            .product-nav {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .pipeline-labels {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .problem-cards,
            .retention-list,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .automation-column ul {
                grid-template-columns: 1fr;
            }

            .problem-card {
                min-height: 142px;
            }

            .problem-card h3 {
                margin-top: 24px;
            }

            .audit-form {
                padding: 22px 18px;
            }

            .footer-brand {
                grid-column: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <div class="container nav-shell">
            <a class="brand" href="{{ route('public.home') }}" aria-label="SayaraForce home">
                <img class="brand-logo-full" src="/images/brand/sayaraforce-logo-horizontal.png" width="1153" height="326" alt="SayaraForce">
                <img class="brand-logo-icon" src="/images/brand/sayaraforce-icon.png" width="208" height="195" alt="" aria-hidden="true">
            </a>

            <nav class="desktop-nav" aria-label="Primary navigation">
                <a href="#problem">Problem</a>
                <a href="#solution">Solution</a>
                <a href="#retention">Retention</a>
                <a href="#pricing">Pricing</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="nav-actions">
                <a class="login-link" href="{{ route('login') }}">Login</a>
                <a class="button button-primary" href="#audit">Book Free Audit</a>
            </div>

            <button
                class="menu-button"
                type="button"
                aria-expanded="false"
                aria-controls="mobile-navigation"
                aria-label="Open navigation menu"
                data-menu-button
            >
                <span class="menu-lines" aria-hidden="true"></span>
            </button>
        </div>

        <nav class="mobile-nav" id="mobile-navigation" aria-label="Mobile navigation" data-mobile-nav>
            <div class="mobile-nav-inner">
                <a href="#problem">Problem</a>
                <a href="#solution">Solution</a>
                <a href="#retention">Retention</a>
                <a href="#pricing">Pricing</a>
                <a href="#faq">FAQ</a>
                <a href="{{ route('login') }}">Login</a>
                <a class="button button-primary" href="#audit">Book Free Audit</a>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <section class="hero dark-section" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div>
                    <p class="hero-eyebrow">INTELLIGENT COMMUNICATION FOR UAE GARAGES</p>
                    <h1 id="hero-title"><span class="hero-line">Understand every enquiry.</span><span class="hero-line hero-highlight">Reply faster.</span><span class="hero-line">Recover more bookings.</span></h1>
                    <p class="hero-copy">
                        SayaraForce combines lead management, WhatsApp communication, booking visibility and customer retention with AI-assisted tools that help your team understand conversations and prepare better replies.
                    </p>

                    <div class="hero-actions">
                        <a class="button button-primary" href="#audit">Book Free Lead-Recovery Audit</a>
                        <a class="button button-secondary" href="#solution">See How It Works</a>
                    </div>

                    <p class="hero-control-note">Reliable automation underneath. AI assistance on top. Your team stays in control.</p>
                </div>

                <div class="product-preview" aria-label="SayaraForce AI communication preview using generic demonstration content">
                    <span class="preview-caption">AI communication preview</span>
                    <div class="product-shell">
                        <aside class="product-sidebar" aria-label="Preview navigation">
                            <div class="product-brand">
                                <img src="/images/brand/sayaraforce-app-icon.png" width="168" height="164" alt="SayaraForce">
                            </div>
                            <div class="product-nav" aria-hidden="true">
                                <span class="active">Inbox</span>
                                <span>Leads</span>
                                <span>Insights</span>
                                <span>Bookings</span>
                                <span>Retention</span>
                            </div>
                        </aside>

                        <div class="product-main">
                            <div class="product-topbar">
                                <div>
                                    <div class="product-kicker">AI-assisted communication workspace</div>
                                    <div class="product-title">Enquiry understanding and reply review</div>
                                </div>
                                <span class="workspace-pill human-review">Human review required</span>
                            </div>

                            <div class="pipeline-labels" aria-label="AI-assisted communication stages">
                                <div class="pipeline-label is-current">
                                    <span>Captured</span>
                                    <small>WhatsApp enquiry</small>
                                </div>
                                <div class="pipeline-label">
                                    <span>Understood</span>
                                    <small>Intent and urgency</small>
                                </div>
                                <div class="pipeline-label">
                                    <span>Draft ready</span>
                                    <small>Suggested reply</small>
                                </div>
                                <div class="pipeline-label">
                                    <span>Staff review</span>
                                    <small>Edit or approve</small>
                                </div>
                            </div>

                            <div class="workspace-grid">
                                <div class="workspace-panel">
                                    <div class="panel-heading">
                                        <span>Conversation summary</span>
                                        <em>Generic demonstration</em>
                                    </div>
                                    <div class="demo-record">
                                        <div class="record-top">
                                            <strong>Demo enquiry</strong>
                                            <span class="status-tag">Needs review</span>
                                        </div>
                                        <div class="record-meta">
                                            <div>
                                                <small>Customer need</small>
                                                <span>Brake inspection</span>
                                            </div>
                                            <div>
                                                <small>Language</small>
                                                <span>English</span>
                                            </div>
                                            <div>
                                                <small>Context</small>
                                                <span>Vehicle model pending</span>
                                            </div>
                                            <div>
                                                <small>Suggested next step</small>
                                                <span>Ask for model and preferred time</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="workspace-panel">
                                    <div class="panel-heading">
                                        <span>AI Communication Insight</span>
                                    </div>
                                    <div class="ai-insight-list">
                                        <div class="ai-insight-row"><span>Intent</span><strong>Service enquiry</strong></div>
                                        <div class="ai-insight-row"><span>Urgency</span><strong>High</strong></div>
                                        <div class="ai-insight-row"><span>Language</span><strong>English</strong></div>
                                        <div class="ai-insight-row"><span>Control</span><strong>Staff approval</strong></div>
                                    </div>
                                    <div class="suggested-reply-preview">
                                        <strong>AI Suggested Reply</strong>
                                        <span>A context-aware draft is prepared for staff review. No message is sent automatically.</span>
                                        <div class="preview-actions" aria-label="Suggested reply review options">
                                            <span class="preview-action">Edit</span>
                                            <span class="preview-action">Regenerate</span>
                                            <span class="preview-action">Make Shorter</span>
                                            <span class="preview-action">Translate</span>
                                            <span class="preview-action primary">Approve and Send</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="capability-strip" aria-label="SayaraForce capabilities">
            <div class="container">
                <p class="capability-positioning">Built for the communication and growth needs of UAE garages.</p>
                <ul class="capability-list">
                    <li>Lead capture</li>
                    <li>WhatsApp inbox</li>
                    <li>AI-assisted replies</li>
                    <li>Booking visibility</li>
                    <li>Service reminders</li>
                    <li>Customer retention</li>
                </ul>
            </div>
        </section>

        <section class="section light-section" id="problem" aria-labelledby="problem-title">
            <div class="container">
                <div class="problem-layout">
                    <div>
                        <p class="section-label">The problem</p>
                        <h2 id="problem-title">Customers are lost when communication breaks down.</h2>
                    </div>
                    <p class="section-intro">SayaraForce gives your team one place to capture enquiries, understand customer needs, respond consistently and follow every opportunity through.</p>
                </div>

                <div class="problem-cards">
                    <article class="problem-card">
                        <span class="problem-number">01</span>
                        <h3>Enquiries are missed</h3>
                    </article>
                    <article class="problem-card">
                        <span class="problem-number">02</span>
                        <h3>Replies take too long</h3>
                    </article>
                    <article class="problem-card">
                        <span class="problem-number">03</span>
                        <h3>Staff do not know which lead is urgent</h3>
                    </article>
                    <article class="problem-card">
                        <span class="problem-number">04</span>
                        <h3>Customer conversations lack context</h3>
                    </article>
                    <article class="problem-card">
                        <span class="problem-number">05</span>
                        <h3>Quotations are not followed up</h3>
                    </article>
                    <article class="problem-card">
                        <span class="problem-number">06</span>
                        <h3>Old customers are forgotten</h3>
                    </article>
                </div>
            </div>
        </section>

        <section class="section dark-section solution-section" id="solution" aria-labelledby="solution-title">
            <div class="container">
                <div class="section-heading-row">
                    <div>
                        <p class="section-label">How it works</p>
                        <h2 id="solution-title">Everything your garage needs. In one place.</h2>
                        <p class="section-intro">Capture enquiries, understand customer needs, prepare better replies, manage bookings and continue the relationship after service.</p>
                    </div>
                    <p class="workflow-note">Capture &rarr; Understand &rarr; Respond &rarr; Book &rarr; Retain</p>
                </div>

                <div class="journey" aria-label="SayaraForce product journey">
                    <article class="journey-stage">
                        <div class="stage-top">
                            <span class="stage-index">01</span>
                            <span class="stage-status">Enquiry intake</span>
                        </div>
                        <h3>Capture</h3>
                        <p class="stage-summary">Bring WhatsApp, website, Meta and manually entered enquiries into one structured workflow.</p>
                        <ul class="stage-features">
                            <li>WhatsApp leads</li>
                            <li>Website leads</li>
                            <li>Meta lead capture where supported</li>
                            <li>Manual enquiry capture</li>
                        </ul>
                    </article>

                    <article class="journey-stage">
                        <div class="stage-top">
                            <span class="stage-index">02</span>
                            <span class="stage-status">AI-assisted insight</span>
                        </div>
                        <h3>Understand</h3>
                        <p class="stage-summary">Use AI-assisted insights to identify customer intent, urgency, language and conversation context.</p>
                        <ul class="stage-features">
                            <li>Conversation summaries</li>
                            <li>Intent and urgency</li>
                            <li>Language assistance</li>
                            <li>Human-controlled insights</li>
                        </ul>
                    </article>

                    <article class="journey-stage">
                        <div class="stage-top">
                            <span class="stage-index">03</span>
                            <span class="stage-status">Staff-controlled reply</span>
                        </div>
                        <h3>Respond</h3>
                        <p class="stage-summary">Prepare faster, more relevant replies while keeping staff approval in the loop.</p>
                        <ul class="stage-features">
                            <li>Suggested WhatsApp replies</li>
                            <li>Edit and regenerate controls</li>
                            <li>Translation assistance</li>
                            <li>Approval before sending</li>
                        </ul>
                    </article>

                    <article class="journey-stage">
                        <div class="stage-top">
                            <span class="stage-index">04</span>
                            <span class="stage-status">Operational workflow</span>
                        </div>
                        <h3>Book</h3>
                        <p class="stage-summary">Move qualified enquiries into the existing booking and garage operations workflow.</p>
                        <ul class="stage-features">
                            <li>Booking pipeline</li>
                            <li>Customer and vehicle details</li>
                            <li>Assignment</li>
                            <li>Booking confirmation</li>
                        </ul>
                    </article>

                    <article class="journey-stage">
                        <div class="stage-top">
                            <span class="stage-index">05</span>
                            <span class="stage-status">Repeat service</span>
                        </div>
                        <h3>Retain</h3>
                        <p class="stage-summary">Use reminders, service history and re-engagement tools to bring customers back.</p>
                        <ul class="stage-features">
                            <li>Service reminders</li>
                            <li>Customer history</li>
                            <li>Reactivation campaigns</li>
                            <li>Repeat-service follow-up</li>
                        </ul>
                    </article>
                </div>

                <p class="ai-safety-note">AI supports the communication stage. Existing automation controls operational workflows, and authorised staff remain responsible for approvals and customer commitments.</p>
            </div>
        </section>

        <section class="section dark-section ai-communication-section" id="ai-communication" aria-labelledby="ai-communication-title">
            <div class="container">
                <div class="ai-section-heading">
                    <p class="section-label">SAYARA AI COMMUNICATION COPILOT</p>
                    <h2 id="ai-communication-title">Turn every customer message into a clear next step.</h2>
                    <p class="section-intro">SayaraForce helps your team understand incoming enquiries, identify urgent conversations and prepare relevant responses&mdash;without giving up human control.</p>
                </div>

                <div class="ai-capabilities">
                    <article class="ai-capability-card">
                        <span class="ai-card-icon" aria-hidden="true">01</span>
                        <h3>Conversation Summary</h3>
                        <p>Understand the customer&rsquo;s request without reading the entire message history.</p>
                    </article>
                    <article class="ai-capability-card">
                        <span class="ai-card-icon" aria-hidden="true">02</span>
                        <h3>Intent and Urgency</h3>
                        <p>Identify whether the customer is asking about a service, quotation, booking, complaint or urgent repair.</p>
                    </article>
                    <article class="ai-capability-card">
                        <span class="ai-card-icon" aria-hidden="true">03</span>
                        <h3>Suggested Replies</h3>
                        <p>Prepare context-aware WhatsApp replies that your team can review, edit and approve.</p>
                    </article>
                    <article class="ai-capability-card">
                        <span class="ai-card-icon" aria-hidden="true">04</span>
                        <h3>Language Assistance</h3>
                        <p>Help staff respond clearly across the languages commonly used by garage customers.</p>
                    </article>
                </div>

                <p class="ai-safety-note">AI suggestions are reviewed by your team before they are sent.</p>
            </div>
        </section>

        <section class="section light-section automation-section" aria-labelledby="automation-title">
            <div class="container">
                <div class="automation-heading">
                    <p class="section-label">Automation plus AI assistance</p>
                    <h2 id="automation-title">Reliable automation. Intelligent assistance.</h2>
                </div>

                <div class="automation-grid">
                    <article class="automation-column">
                        <h3>Automation handles</h3>
                        <ul>
                            <li>Lead capture</li>
                            <li>Follow-up reminders</li>
                            <li>Booking workflow</li>
                            <li>Service reminders</li>
                            <li>Campaign scheduling</li>
                            <li>Permissions</li>
                            <li>Audit history</li>
                        </ul>
                    </article>
                    <article class="automation-column">
                        <h3>AI assists with</h3>
                        <ul>
                            <li>Conversation summaries</li>
                            <li>Intent detection</li>
                            <li>Urgency detection</li>
                            <li>Suggested replies</li>
                            <li>Translation assistance</li>
                            <li>Communication prioritisation</li>
                            <li>Manager insights</li>
                        </ul>
                    </article>
                </div>

                <p class="automation-statement">SayaraForce does not replace reliable workflows with unpredictable AI. It adds intelligence around the workflows your garage depends on.</p>
            </div>
        </section>

        <section class="section dark-section retention-section" id="retention" aria-labelledby="retention-title">
            <div class="container retention-grid">
                <div>
                    <p class="section-label">Customer retention</p>
                    <h2 id="retention-title">Better conversations should lead to lasting customers.</h2>
                    <p class="section-intro">
                        SayaraForce connects communication with booking history, service reminders and customer retention&mdash;helping your garage continue the relationship after the first enquiry.
                    </p>
                    <ul class="retention-list">
                        <li>Service reminders</li>
                        <li>Customer history</li>
                        <li>Reactivation campaigns</li>
                        <li>Repeat-service workflows</li>
                    </ul>
                </div>

                <div class="retention-preview" aria-label="Customer retention product preview with neutral demo data">
                    <div class="retention-preview-header">
                        <strong>Customer history</strong>
                        <span>Demo workspace</span>
                    </div>
                    <div class="customer-record">
                        <div class="record-card">
                            <small>Demo customer record</small>
                            <h3>Service relationship</h3>
                            <div class="record-details">
                                <div class="record-detail">
                                    <span>Vehicle details</span>
                                    <span>Available</span>
                                </div>
                                <div class="record-detail">
                                    <span>Service history</span>
                                    <span>Recorded</span>
                                </div>
                                <div class="record-detail">
                                    <span>Next action</span>
                                    <span>Review reminder</span>
                                </div>
                            </div>
                        </div>
                        <div class="record-card">
                            <small>Repeat-service workflow</small>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <span class="timeline-dot">01</span>
                                    <div>
                                        <strong>Service completed</strong>
                                        <span>Customer and vehicle history stays linked.</span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <span class="timeline-dot">02</span>
                                    <div>
                                        <strong>Reminder prepared</strong>
                                        <span>The team reviews the appropriate follow-up.</span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <span class="timeline-dot">03</span>
                                    <div>
                                        <strong>Re-engagement tracked</strong>
                                        <span>Status and next action remain visible.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section light-section pricing-section" id="pricing" aria-labelledby="pricing-title">
            <div class="container">
                <div class="pricing-heading">
                    <p class="section-label">Pricing</p>
                    <h2 id="pricing-title">Simple pricing. Powerful follow-up.</h2>
                    <p class="section-intro">Choose the plan that matches the way your garage manages communication, automation and customer retention.</p>
                </div>

                <div class="pricing-grid">
                    <article class="price-card">
                        <h3 class="plan-name">Starter</h3>
                        <p class="plan-positioning">Core lead management and structured garage communication.</p>
                        <div class="plan-price">
                            <strong>AED 999</strong>
                            <span>/month</span>
                        </div>
                        <ul class="plan-features">
                            <li>Lead capture</li>
                            <li>Basic client records</li>
                            <li>Manager inbox visibility</li>
                            <li>Booking tracking</li>
                            <li>Basic dashboard</li>
                        </ul>
                        <a class="button" href="#audit">Book Free Audit</a>
                    </article>

                    <article class="price-card recommended">
                        <span class="recommended-label">Recommended</span>
                        <h3 class="plan-name">Growth</h3>
                        <p class="plan-positioning">Advanced automation, reminders, campaigns and operational visibility.</p>
                        <div class="plan-price">
                            <strong>AED 1,499</strong>
                            <span>/month</span>
                        </div>
                        <ul class="plan-features">
                            <li>Everything in Starter</li>
                            <li>Opportunity pipeline</li>
                            <li>Booking and job workflow</li>
                            <li>Invoice tracking</li>
                            <li>Retention reminders and manager workflow</li>
                        </ul>
                        <a class="button" href="#audit">Book Free Audit</a>
                    </article>

                    <article class="price-card">
                        <h3 class="plan-name">Pro</h3>
                        <p class="plan-positioning">Advanced control with access to Sayara AI Communication Copilot features.</p>
                        <div class="plan-price">
                            <strong>AED 1,999</strong>
                            <span>/month</span>
                        </div>
                        <ul class="plan-features">
                            <li>AI conversation summaries</li>
                            <li>AI intent and urgency insights</li>
                            <li>AI suggested replies</li>
                            <li>Language assistance</li>
                            <li>Advanced communication intelligence</li>
                        </ul>
                        <p class="plan-pilot-note">AI Communication Copilot access is initially available to selected Pro pilot garages.</p>
                        <a class="button" href="#audit">Book Free Audit</a>
                    </article>
                </div>

                <p class="pricing-note">WhatsApp, Meta, AI usage and provider fees may be charged separately where applicable.</p>
            </div>
        </section>

        <section class="section light-section faq-section" id="faq" aria-labelledby="faq-title">
            <div class="container faq-layout">
                <div>
                    <p class="section-label">FAQ</p>
                    <h2 id="faq-title">Clear answers before you start.</h2>
                    <p class="section-intro">Practical information about how SayaraForce fits into a garage workflow.</p>
                </div>

                <div class="accordion" data-accordion>
                    @foreach($faqItems as $index => $item)
                        @php
                            $buttonId = 'faq-button-' . $index;
                            $panelId = 'faq-panel-' . $index;
                            $isOpen = $index === 0;
                        @endphp
                        <article class="faq-item">
                            <h3 style="margin: 0;">
                                <button
                                    class="faq-question"
                                    type="button"
                                    id="{{ $buttonId }}"
                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                    aria-controls="{{ $panelId }}"
                                >
                                    <span>{{ $item['question'] }}</span>
                                    <span class="faq-icon" aria-hidden="true">+</span>
                                </button>
                            </h3>
                            <div
                                class="faq-answer"
                                id="{{ $panelId }}"
                                role="region"
                                aria-labelledby="{{ $buttonId }}"
                                @if(!$isOpen) hidden @endif
                            >
                                <p>{{ $item['answer'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section dark-section audit-section" id="audit" aria-labelledby="audit-title">
            <div class="container audit-layout">
                <div class="audit-copy">
                    <p class="section-label">Free audit</p>
                    <h2 id="audit-title">See how intelligent communication can help your garage grow.</h2>
                    <p class="section-intro">We&rsquo;ll review how your garage currently handles enquiries, WhatsApp conversations, follow-ups and bookings&mdash;and show where SayaraForce can improve the process.</p>
                    <ul class="audit-benefits">
                        <li>Personalised audit report</li>
                        <li>Practical recommendations</li>
                        <li>No obligation</li>
                    </ul>
                    <p class="audit-pilot-note">AI Communication Copilot demonstrations are available for selected Pro pilot garages.</p>
                </div>

                <form class="audit-form" method="POST" action="{{ route('public.demo.store') }}">
                    @csrf

                    @if($errors->any())
                        <div class="form-alert" role="alert">
                            <strong>Please review the highlighted fields.</strong>
                            Your audit request has not been submitted yet.
                        </div>
                    @endif

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="garage_name">Garage name</label>
                            <input
                                id="garage_name"
                                name="garage_name"
                                type="text"
                                value="{{ old('garage_name') }}"
                                placeholder="Your garage name"
                                autocomplete="organization"
                                required
                                @error('garage_name') aria-invalid="true" aria-describedby="garage_name_error" @enderror
                            >
                            @error('garage_name') <span class="field-error" id="garage_name_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label for="name">Contact name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                placeholder="Your name"
                                autocomplete="name"
                                required
                                @error('name') aria-invalid="true" aria-describedby="name_error" @enderror
                            >
                            @error('name') <span class="field-error" id="name_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label for="phone">Phone number</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                placeholder="UAE contact number"
                                autocomplete="tel"
                                inputmode="tel"
                                required
                                @error('phone') aria-invalid="true" aria-describedby="phone_error" @enderror
                            >
                            @error('phone') <span class="field-error" id="phone_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label for="email">Email <span class="required-note">(optional)</span></label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="Work email"
                                autocomplete="email"
                                @error('email') aria-invalid="true" aria-describedby="email_error" @enderror
                            >
                            @error('email') <span class="field-error" id="email_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label for="current_management_system">Current management system <span class="required-note">(optional)</span></label>
                            <input
                                id="current_management_system"
                                name="current_management_system"
                                type="text"
                                value="{{ old('current_management_system') }}"
                                placeholder="System name or none"
                                @error('current_management_system') aria-invalid="true" aria-describedby="current_management_system_error" @enderror
                            >
                            @error('current_management_system') <span class="field-error" id="current_management_system_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label for="monthly_leads">Approximate monthly lead volume <span class="required-note">(optional)</span></label>
                            <select
                                id="monthly_leads"
                                name="monthly_leads"
                                @error('monthly_leads') aria-invalid="true" aria-describedby="monthly_leads_error" @enderror
                            >
                                <option value="">Select a range</option>
                                <option value="under_50" @selected(old('monthly_leads') === 'under_50')>Under 50</option>
                                <option value="50_100" @selected(old('monthly_leads') === '50_100')>50&ndash;100</option>
                                <option value="101_250" @selected(old('monthly_leads') === '101_250')>101&ndash;250</option>
                                <option value="250_plus" @selected(old('monthly_leads') === '250_plus')>More than 250</option>
                                <option value="not_sure" @selected(old('monthly_leads') === 'not_sure')>Not sure</option>
                            </select>
                            @error('monthly_leads') <span class="field-error" id="monthly_leads_error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field full">
                            <label for="main_challenge">Main challenge <span class="required-note">(optional)</span></label>
                            <textarea
                                id="main_challenge"
                                name="main_challenge"
                                placeholder="Tell us where leads, follow-ups or bookings are getting difficult."
                                @error('main_challenge') aria-invalid="true" aria-describedby="main_challenge_error" @enderror
                            >{{ old('main_challenge') }}</textarea>
                            @error('main_challenge') <span class="field-error" id="main_challenge_error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button class="button button-primary" type="submit">Book My Free Audit</button>
                    <p class="form-privacy">Your request is handled according to our <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.</p>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a class="brand" href="{{ route('public.home') }}" aria-label="SayaraForce home">
                        <img class="footer-logo" src="/images/brand/sayaraforce-logo-tagline.png" width="1153" height="326" alt="SayaraForce — Growth Engine for UAE Garages" loading="lazy">
                    </a>
                    <p class="footer-description">The intelligent growth engine for UAE garages. SayaraForce combines reliable garage automation with AI-assisted communication to help your team understand enquiries, reply faster, recover missed leads and bring customers back.</p>
                </div>

                <div class="footer-column">
                    <h2>Product</h2>
                    <nav class="footer-links" aria-label="Product links">
                        <a href="#problem">Problem</a>
                        <a href="#solution">Solution</a>
                        <a href="#retention">Retention</a>
                        <a href="#pricing">Pricing</a>
                    </nav>
                </div>

                <div class="footer-column">
                    <h2>Company</h2>
                    <nav class="footer-links" aria-label="Company links">
                        <a href="#faq">FAQ</a>
                        <a href="#audit">Contact</a>
                        <a href="{{ route('login') }}">Login</a>
                        <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
                        <a href="{{ route('terms') }}">Terms of Service</a>
                    </nav>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} SayaraForce. All rights reserved.</span>
                <span>Built for UAE garages.</span>
            </div>
        </div>
    </footer>

    <script>
        (() => {
            const menuButton = document.querySelector('[data-menu-button]');
            const mobileNav = document.querySelector('[data-mobile-nav]');

            const setMenuState = (open) => {
                if (!menuButton || !mobileNav) return;
                menuButton.setAttribute('aria-expanded', String(open));
                menuButton.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
                mobileNav.classList.toggle('is-open', open);
                document.body.classList.toggle('menu-open', open);
            };

            menuButton?.addEventListener('click', () => {
                setMenuState(menuButton.getAttribute('aria-expanded') !== 'true');
            });

            mobileNav?.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setMenuState(false));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setMenuState(false);
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 980) setMenuState(false);
            });

            document.querySelectorAll('.faq-question').forEach((button) => {
                button.addEventListener('click', () => {
                    const panel = document.getElementById(button.getAttribute('aria-controls'));
                    const willOpen = button.getAttribute('aria-expanded') !== 'true';

                    button.setAttribute('aria-expanded', String(willOpen));
                    if (panel) panel.hidden = !willOpen;
                });
            });
        })();
    </script>
</body>
</html>

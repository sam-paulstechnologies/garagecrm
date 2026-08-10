@extends('super_admin.layout')

@section('title', 'Commercial Metrics')

@section('super_admin_content')
    <section class="rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Platform-only commercial telemetry</p>
        <h1 class="mt-2 text-3xl font-black text-white">Commercial health</h1>
        <p class="mt-3 max-w-3xl text-sm font-semibold sa-muted">Counts come from internal subscription, billing, usage and privacy-minimized product-event records. No metric is estimated from customer contact data.</p>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($planCounts as $code => $count)
            <article class="rounded-3xl sa-card p-5">
                <p class="text-xs font-extrabold uppercase tracking-wide sa-label">{{ str($code)->replace('_', ' ')->title() }}</p>
                <p class="mt-3 text-4xl font-black text-white">{{ number_format($count) }}</p>
                <p class="mt-2 text-xs font-bold sa-muted">Active catalogue assignments</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Free → Service', $metrics['free_to_service'], 'Verified plan transitions'],
            ['Service → Growth', $metrics['service_to_growth'], 'Verified plan transitions'],
            ['Cancellation events', $metrics['cancelled'], 'Privacy-minimized events'],
            ['Failed payments', $metrics['failed_payments'], 'Provider-verified invoices'],
            ['Open/failed checkouts', $metrics['checkout_abandonment'], 'No inferred card activity'],
            ['AI analysis runs', $metrics['ai_analysis_runs'], 'Observed model runs'],
            ['AI input / output tokens', number_format($metrics['ai_input_tokens']).' / '.number_format($metrics['ai_output_tokens']), 'Provider telemetry when supplied'],
            ['Estimated AI cost', number_format($metrics['ai_estimated_cost'], 6), 'Recorded provider-cost units only'],
            ['WhatsApp provider events', number_format($metrics['whatsapp_provider_events']), 'Recorded status/usage telemetry'],
            ['WhatsApp recorded cost', number_format($metrics['whatsapp_recorded_cost'], 4), 'Provider-reported values only'],
            ['Average monitored customers', number_format($metrics['average_monitored_customers'], 2), 'Across recorded billing periods'],
            ['Entitlement limit pressure', $metrics['limit_pressure'], 'Usage at or above allowance'],
            ['Recorded paid revenue', 'AED '.number_format($metrics['paid_revenue_aed'], 2), 'Provider-verified paid invoices'],
            ['Payment fees', 'Not available', 'Never guessed without provider data'],
        ] as [$label, $value, $hint])
            <article class="rounded-3xl sa-card p-5">
                <p class="text-sm font-extrabold sa-label">{{ $label }}</p>
                <p class="mt-3 text-2xl font-black text-white">{{ $value }}</p>
                <p class="mt-2 text-xs font-bold sa-muted">{{ $hint }}</p>
            </article>
        @endforeach
    </section>
@endsection

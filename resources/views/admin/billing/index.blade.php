@extends('layouts.app')

@section('title', 'Plan & Billing — SayaraForce')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">Commercial account</span>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">Plan & Billing</h1>
        <p class="mt-2 text-sm font-medium text-slate-400">Your entitlements change only after a verified payment-provider event. SayaraForce never stores card details.</p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-300">{{ session('warning') }}</div>
    @endif

    <section class="rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Current subscription</div>
                <h2 class="mt-2 text-2xl font-black text-white">{{ $subscription->planVersion->plan->name }}</h2>
                <p class="mt-2 text-sm text-slate-400">Status: <span class="font-extrabold text-slate-200">{{ str_replace('_', ' ', ucfirst($subscription->status)) }}</span> · Payment: <span class="font-extrabold text-slate-200">{{ str_replace('_', ' ', ucfirst($subscription->payment_status ?? 'not configured')) }}</span></p>
                @if($subscription->current_period_end)
                    <p class="mt-1 text-sm text-slate-400">Current period ends {{ $subscription->current_period_end->format('d M Y') }}.</p>
                @endif
                @if($subscription->grace_ends_at)
                    <p class="mt-2 text-sm font-bold text-yellow-300">Payment grace ends {{ $subscription->grace_ends_at->format('d M Y') }}. Access is preserved during the configured grace period.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if($subscription->provider_customer_id)
                    <form method="POST" action="{{ route('admin.billing.portal') }}">@csrf<button class="rounded-xl border border-white/10 bg-slate-800 px-4 py-2 text-sm font-extrabold text-white">Billing portal</button></form>
                @endif
                @if($subscription->provider_subscription_id && !$subscription->cancellation_requested_at)
                    <form method="POST" action="{{ route('admin.billing.cancel') }}" onsubmit="return confirm('Request cancellation at the end of the billing period?')">@csrf<button class="rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-2 text-sm font-extrabold text-red-300">Cancel at period end</button></form>
                @elseif($subscription->cancellation_requested_at)
                    <span class="rounded-xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-2 text-sm font-extrabold text-yellow-300">Cancellation awaiting provider confirmation</span>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="mb-4">
            <h2 class="text-xl font-black text-white">Choose your next stage</h2>
            <p class="mt-1 text-sm text-slate-400">Launch pricing is represented by versioned price records for the first {{ config('commercial.pricing.promotion_duration_months') }} billing cycles, then the standard price applies.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach($prices as $price)
                @php($plan = $price->planVersion->plan)
                <article class="flex flex-col rounded-3xl border {{ $subscription->price_id === $price->id ? 'border-orange-400/40 bg-orange-500/10' : 'border-white/10 bg-slate-900/80' }} p-5">
                    <h3 class="text-lg font-black text-white">{{ $plan->name }}</h3>
                    <p class="mt-2 min-h-12 text-xs leading-5 text-slate-400">{{ $plan->description }}</p>
                    <div class="mt-5">
                        <span class="text-2xl font-black text-white">AED {{ number_format((float) ($price->promotional_amount ?? $price->list_amount), 0) }}</span>
                        <span class="text-xs font-bold text-slate-500">/{{ $price->interval }}</span>
                    </div>
                    @if($price->promotional_amount !== null && (float) $price->promotional_amount !== (float) $price->list_amount)
                        <p class="mt-1 text-xs font-semibold text-slate-500">Then AED {{ number_format((float) $price->list_amount, 0) }}/{{ $price->interval }} after the introductory period.</p>
                    @endif
                    <div class="mt-auto pt-5">
                        @if($subscription->price_id === $price->id)
                            <span class="block rounded-xl border border-orange-400/20 px-3 py-2 text-center text-sm font-extrabold text-orange-300">Current plan</span>
                        @elseif((float) $price->list_amount > 0)
                            <form method="POST" action="{{ route('admin.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="price_code" value="{{ $price->code }}">
                                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                <button class="w-full rounded-xl bg-orange-500 px-3 py-2 text-sm font-black text-white transition hover:bg-orange-400" {{ config('billing.checkout_enabled') ? '' : 'disabled' }}>Upgrade</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @unless(config('billing.checkout_enabled'))
            <p class="mt-4 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-300">Checkout is safely disabled in this environment. Plan and price records remain visible.</p>
        @endunless
    </section>

    <section class="rounded-3xl border border-white/10 bg-slate-900/80 p-6">
        <h2 class="text-xl font-black text-white">Billing history</h2>
        @if($invoices->isEmpty())
            <p class="mt-3 text-sm text-slate-500">No verified provider invoices have been recorded.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm"><thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Date</th><th>Provider</th><th>Plan</th><th>Status</th><th>Amount</th><th>Period</th></tr></thead><tbody class="divide-y divide-white/5 text-slate-300">
                    @foreach($invoices as $invoice)
                        <tr>
                            <td class="py-3">{{ ($invoice->paid_at ?? $invoice->created_at)->format('d M Y') }}</td>
                            <td>
                                @if($invoice->test_mode)
                                    <span class="mr-1 inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-blue-300">{{ app()->environment('staging') ? 'Staging / Test' : 'Test' }}</span>
                                @endif
                                <span class="font-bold uppercase">{{ $invoice->payment_provider }}</span>
                            </td>
                            <td>{{ $invoice->price?->planVersion?->plan?->name ?? 'Legacy plan' }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($invoice->status)) }}</td>
                            <td>{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                            <td>{{ $invoice->period_start?->format('d M Y') ?? '—' }} – {{ $invoice->period_end?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody></table>
            </div>
        @endif
    </section>
</div>
@endsection

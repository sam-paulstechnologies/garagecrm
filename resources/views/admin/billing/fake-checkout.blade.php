@extends('layouts.app')

@section('title', 'Sandbox Checkout — SayaraForce')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
    <div class="rounded-3xl border border-blue-400/20 bg-slate-900/90 p-8 shadow-2xl shadow-black/30">
        <span class="rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-blue-300">Staging sandbox — no charge</span>
        <h1 class="mt-5 text-3xl font-black text-white">Confirm {{ $checkout->requestedPrice->planVersion->plan->name }}</h1>
        @if($checkout->operation === 'plan_change')
            <p class="mt-2 text-xs font-extrabold uppercase tracking-wide text-blue-300">Verified sandbox plan change</p>
        @endif
        <p class="mt-3 text-sm leading-6 text-slate-400">This deterministic fake provider exercises the same signed, idempotent webhook path as a real gateway. It never collects payment details or creates a charge.</p>
        <dl class="mt-6 grid grid-cols-2 gap-4 rounded-2xl border border-white/10 bg-slate-950/60 p-5 text-sm">
            <div><dt class="text-slate-500">{{ $checkout->price_phase === 'launch' ? 'Launch price' : 'Standard price' }}</dt><dd class="mt-1 font-black text-white">AED {{ number_format((float) ($checkout->price_phase === 'launch' ? $checkout->requestedPrice->promotional_amount : $checkout->requestedPrice->list_amount), 2) }}</dd></div>
            <div><dt class="text-slate-500">Standard price</dt><dd class="mt-1 font-black text-white">AED {{ number_format((float) $checkout->requestedPrice->list_amount, 2) }}</dd></div>
        </dl>
        <form class="mt-7" method="POST" action="{{ route('admin.billing.fake.complete', $checkout) }}">
            @csrf
            <button class="w-full rounded-xl bg-blue-500 px-5 py-3 text-sm font-black text-white hover:bg-blue-400">Complete signed sandbox checkout</button>
        </form>
        <form class="mt-3" method="POST" action="{{ route('admin.billing.fake.cancel', $checkout) }}">
            @csrf
            <button class="w-full text-center text-sm font-bold text-slate-500 hover:text-white">Cancel sandbox checkout</button>
        </form>
    </div>
</div>
@endsection

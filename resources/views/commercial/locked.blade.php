@extends('layouts.app')

@section('title', 'Upgrade required')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <section class="rounded-3xl border border-orange-400/20 bg-slate-950/80 p-8 shadow-2xl">
        <p class="text-xs font-black uppercase tracking-[0.24em] text-orange-300">Plan feature</p>
        <h1 class="mt-3 text-3xl font-black text-white">This feature is not included yet</h1>
        <p class="mt-4 text-base leading-7 text-slate-300">{{ $upsell['message'] }}</p>
        @if($upsell['usage_message'])
            <p class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 text-slate-200">
                {{ $upsell['usage_message'] }}
            </p>
        @endif

        @if($upsell['next_plan'])
            <div class="mt-7 rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="font-bold text-white">Upgrade to {{ ucfirst(str_replace('_', ' ', $upsell['next_plan'])) }}</p>
                @if($upsell['launch_amount'] !== null)
                    <p class="mt-1 text-sm text-slate-300">
                        {{ $upsell['currency'] }} {{ number_format((float) $upsell['launch_amount'], 0) }}/month launch price
                        @if($upsell['standard_amount'] !== $upsell['launch_amount'])
                            · {{ $upsell['currency'] }} {{ number_format((float) $upsell['standard_amount'], 0) }} standard
                        @endif
                    </p>
                @endif
            </div>
        @endif

        <div class="mt-7 flex flex-wrap gap-3">
            @if(Route::has('admin.billing.index'))
                <a href="{{ route('admin.billing.index') }}" class="rounded-xl bg-orange-500 px-5 py-3 font-bold text-white hover:bg-orange-400">View plans</a>
            @endif
            <a href="{{ url()->previous() }}" class="rounded-xl border border-white/15 px-5 py-3 font-bold text-slate-200 hover:bg-white/5">Go back</a>
        </div>
    </section>
</div>
@endsection

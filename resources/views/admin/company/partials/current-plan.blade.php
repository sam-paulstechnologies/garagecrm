{{-- resources/views/admin/company/partials/current-plan.blade.php --}}

@php
    $plan = $company->plan ?? null;

    $features = [];

    if ($plan) {
        $features = is_string($plan->features)
            ? json_decode($plan->features, true)
            : ($plan->features ?? []);
    }

    $features = is_array($features) ? $features : [];

    $trialActive = method_exists($company, 'isTrialActive')
        ? $company->isTrialActive()
        : false;

    $trialEndsAt = !empty($company->trial_ends_at)
        ? \Illuminate\Support\Carbon::parse($company->trial_ends_at)
        : null;
@endphp

<div class="sf-card">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="sf-section-title">
                Current Plan
            </h2>

            <p class="sf-section-subtitle">
                Active subscription, usage limits, and plan features.
            </p>
        </div>

        @if($trialActive)
            <span class="sf-badge-green">
                Trial Active
            </span>
        @else
            <span class="sf-badge-orange">
                Growth Plan
            </span>
        @endif
    </div>

    <div class="sf-card-body space-y-5">

        @if($plan)
            {{-- Plan Cards --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                {{-- Plan Name --}}
                <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        Plan Name
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $plan->name ?? 'Growth Plan' }}
                    </div>
                </div>

                {{-- Price --}}
                <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        Price
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ number_format((float) ($plan->price ?? 0), 2) }}
                        {{ $plan->currency ?? 'AED' }}
                    </div>
                </div>

                {{-- WhatsApp Limit --}}
                <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-green-300">
                        WhatsApp Limit
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $plan->whatsapp_limit ?? '—' }}
                    </div>
                </div>

                {{-- User Limit --}}
                <div class="rounded-2xl border border-purple-400/20 bg-purple-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-purple-300">
                        User Limit
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $plan->user_limit ?? '—' }}
                    </div>
                </div>

            </div>

            {{-- Trial --}}
            <div class="rounded-3xl border {{ $trialActive ? 'border-green-400/20 bg-green-500/10' : 'border-white/10 bg-slate-950/60' }} p-5">
                @if($trialActive)
                    <div class="font-extrabold text-green-300">
                        Trial Active
                    </div>

                    <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                        Trial active until:
                        <span class="font-extrabold text-white">
                            {{ $trialEndsAt ? $trialEndsAt->format('d M Y') : '—' }}
                        </span>
                    </p>
                @else
                    <div class="font-extrabold text-slate-300">
                        No Trial Active
                    </div>

                    <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                        This company is not currently on an active trial.
                    </p>
                @endif
            </div>

            {{-- Features --}}
            @if(count($features))
                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                    <div class="mb-4">
                        <h3 class="font-extrabold text-white">
                            Plan Features
                        </h3>

                        <p class="mt-1 text-xs font-medium text-slate-500">
                            Features enabled for this subscription package.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach($features as $key => $feature)
                            @php
                                $featureLabel = is_string($key) && !is_numeric($key)
                                    ? $key
                                    : $feature;

                                $enabled = is_bool($feature)
                                    ? $feature
                                    : true;
                            @endphp

                            <span class="{{ $enabled ? 'sf-badge-green' : 'sf-badge-slate' }}">
                                {{ is_string($featureLabel) ? ucfirst(str_replace('_', ' ', $featureLabel)) : 'Feature' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

        @else
            <div class="sf-empty">
                No active plan is linked to this company.
            </div>
        @endif

    </div>
</div>
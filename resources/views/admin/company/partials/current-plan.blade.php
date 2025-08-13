<div class="space-y-5">
    <div class="p-4 bg-white shadow rounded">
        <h2 class="text-lg font-bold mb-2">Current Plan</h2>

        <div class="text-sm text-gray-700">
            <p><strong>Plan Name:</strong> {{ $company->plan->name }}</p>
            <p><strong>Price:</strong> {{ $company->plan->price }} {{ $company->plan->currency }}</p>
            <p><strong>WhatsApp Limit:</strong> {{ $company->plan->whatsapp_limit }}</p>
            <p><strong>User Limit:</strong> {{ $company->plan->user_limit }}</p>

            @if($company->isTrialActive())
                <p class="text-sm text-green-600"><strong>Trial Active until:</strong> {{ $company->trial_ends_at->format('d M Y') }}</p>
            @else
                <p class="text-sm text-gray-500 italic">No trial active</p>
            @endif
        </div>

        @php
            $features = is_string($company->plan->features)
                ? json_decode($company->plan->features, true)
                : $company->plan->features;
        @endphp
    </div>
</div>

@extends('layouts.app')

@section('title', 'Subscription Plans')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">Subscription Plans</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                <div class="bg-white shadow rounded-lg p-6 border 
                    @if(auth()->user()->company->plan_id === $plan->id) border-indigo-600 @else border-gray-200 @endif">
                    
                    <h2 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h2>

                    <p class="text-gray-600 mb-4 text-sm">WhatsApp Limit: <strong>{{ $plan->whatsapp_limit }}</strong></p>
                    <p class="text-gray-600 mb-4 text-sm">User Limit: <strong>{{ $plan->user_limit }}</strong></p>
                    <p class="text-2xl font-semibold text-indigo-600 mb-4">
                        {{ number_format($plan->price, 2) }} {{ $plan->currency }}
                    </p>

                    @if(auth()->user()->company->plan_id === $plan->id)
                        <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">
                            Current Plan
                        </span>
                    @else
                        <form action="{{ route('admin.plans.subscribe', $plan->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="mt-4 inline-block bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded hover:bg-indigo-700">
                                Switch to this Plan
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

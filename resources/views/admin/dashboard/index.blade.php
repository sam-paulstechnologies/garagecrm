@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-10">

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $cards = [
                [
                    'label' => 'Total Users',
                    'value' => $stats['total_users'],
                    'color' => 'blue',
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
                ],
                [
                    'label' => 'Total Clients',
                    'value' => $stats['total_clients'],
                    'color' => 'green',
                    'extra' => '+'.$stats['new_clients_this_month'].' this month',
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857...'
                ],
                [
                    'label' => 'Total Leads',
                    'value' => $stats['total_leads'],
                    'color' => 'yellow',
                    'extra' => '+'.$stats['new_leads_this_month'].' this month',
                    'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                ],
                [
                    'label' => 'Monthly Revenue',
                    'value' => 'AED ' . number_format($stats['revenue_this_month'], 2),
                    'color' => 'purple',
                    'extra' => $stats['bookings_this_month'] . ' bookings',
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2...'
                ]
            ];
        @endphp

        @foreach($cards as $card)
        <div class="bg-white rounded-lg shadow p-5 flex items-start gap-4">
            <div class="p-3 bg-{{ $card['color'] }}-100 text-{{ $card['color'] }}-600 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                </svg>
            </div>
            <div>
                <div class="text-sm text-gray-600">{{ $card['label'] }}</div>
                <div class="text-2xl font-semibold text-gray-900">{{ $card['value'] }}</div>
                @isset($card['extra'])
                    <div class="text-xs text-{{ $card['color'] }}-600 mt-1">{{ $card['extra'] }}</div>
                @endisset
            </div>
        </div>
        @endforeach
    </div>

    <!-- Smart KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Pending Bookings</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['pending_bookings'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Unpaid Invoices</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['unpaid_invoices'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Follow-ups This Week</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['followups_due'] }}</p>
        </div>
    </div>

    <!-- Revenue Trend Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend (Last 6 Months)</h3>
        @php
            $monthlyData = $monthlyRevenue->toArray();
            $maxRevenue = count($monthlyData) ? max(array_column($monthlyData, 'revenue')) : 0;
        @endphp
        <div class="h-64 flex items-end justify-between space-x-3">
            @foreach($monthlyRevenue as $data)
                @php $height = $maxRevenue > 0 ? ($data['revenue'] / $maxRevenue) * 200 : 0; @endphp
                <div class="flex flex-col items-center w-1/6">
                    <div class="bg-blue-500 w-6 rounded-t" style="height: {{ $height }}px;"></div>
                    <p class="text-xs text-gray-600 mt-2">{{ $data['month'] }}</p>
                    <p class="text-xs font-medium">AED {{ number_format($data['revenue'], 0) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Leads, Bookings, Opportunities -->
    @include('admin.dashboard.partials.recent-panels')

    <!-- Quick Actions -->
    @include('admin.dashboard.partials.quick-actions')

</div>
@endsection

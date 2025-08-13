@extends('layouts.app')

@section('content')
<div class="p-6">

    {{-- Top Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-gray-500 text-sm">Total Clients</div>
            <div class="text-2xl font-bold">0</div>
            <div class="text-sm text-gray-400">Active customers</div>
        </div>
        <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-gray-500 text-sm">Active Jobs</div>
            <div class="text-2xl font-bold">0</div>
            <div class="text-sm text-gray-400">Currently in progress</div>
        </div>
        <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-gray-500 text-sm">Monthly Revenue</div>
            <div class="text-2xl font-bold text-green-600">$0.00</div>
            <div class="text-sm text-gray-400">This month</div>
        </div>
        <div class="bg-white rounded-lg p-4 shadow">
            <div class="text-gray-500 text-sm">Completed Jobs</div>
            <div class="text-2xl font-bold">0</div>
            <div class="text-sm text-gray-400">This month</div>
        </div>
    </div>

    {{-- Bottom Panels --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Recent Activity --}}
        <div class="bg-white rounded-lg p-4 shadow">
            <h2 class="text-xl font-semibold mb-2">Recent Activity</h2>
            <p class="text-sm text-gray-500 mb-4">Latest updates from your garage</p>
            <ul class="space-y-4">
                <li class="flex items-start justify-between">
                    <div>
                        <span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                        <span class="font-semibold">Oil Change completed</span>
                        <div class="text-sm text-gray-500">John Smith - Toyota Camry</div>
                    </div>
                    <span class="text-sm text-gray-400">2 hours ago</span>
                </li>
                <li class="flex items-start justify-between">
                    <div>
                        <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                        <span class="font-semibold">Brake service in progress</span>
                        <div class="text-sm text-gray-500">Sarah Johnson - Honda Civic</div>
                    </div>
                    <span class="text-sm text-gray-400">4 hours ago</span>
                </li>
                <li class="flex items-start justify-between">
                    <div>
                        <span class="inline-block w-3 h-3 bg-orange-500 rounded-full mr-2"></span>
                        <span class="font-semibold">Payment received</span>
                        <div class="text-sm text-gray-500">Invoice INV-001 - $82.07</div>
                    </div>
                    <span class="text-sm text-gray-400">1 day ago</span>
                </li>
            </ul>
        </div>

        {{-- Upcoming Tasks --}}
        <div class="bg-white rounded-lg p-4 shadow">
            <h2 class="text-xl font-semibold mb-2">Upcoming Tasks</h2>
            <p class="text-sm text-gray-500 mb-4">Items requiring attention</p>
            <ul class="space-y-4">
                <li class="flex justify-between items-center">
                    <div>
                        <div class="font-semibold">Engine Diagnostic</div>
                        <div class="text-sm text-gray-500">Mike Davis - Ford F-150</div>
                    </div>
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">Pending</span>
                </li>
                <li class="flex justify-between items-center">
                    <div>
                        <div class="font-semibold">Follow up on invoice</div>
                        <div class="text-sm text-gray-500">INV-002 - Sarah Johnson</div>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Sent</span>
                </li>
                <li class="flex justify-between items-center">
                    <div>
                        <div class="font-semibold">Complete brake service</div>
                        <div class="text-sm text-gray-500">Honda Civic inspection</div>
                    </div>
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">In Progress</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

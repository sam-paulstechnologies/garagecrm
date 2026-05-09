@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create Lead</h1>
            <p class="text-sm text-gray-500 mt-1">
                Add a new lead manually. The system will check for existing active leads using phone/email.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                Back to Leads
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-100 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-800 px-4 py-3 text-sm">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Form --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">Lead Information</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Enter the customer details and assign the lead if required.
                </p>
            </div>

            <form action="{{ route('admin.leads.store') }}" method="POST" class="space-y-5">
                @csrf

                @include('admin.leads.partials.form', ['lead' => null])

                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Save Lead
                    </button>

                    <a href="{{ route('admin.leads.index') }}"
                       class="inline-flex items-center px-5 py-2.5 rounded-lg bg-gray-50 text-gray-700 text-sm font-medium border border-gray-200 hover:bg-gray-100">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Help Panel --}}
        <div class="space-y-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Manual Lead Rules</h2>

                <ul class="mt-4 text-sm text-gray-700 space-y-3">
                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">1</span>
                        <span>Name is required.</span>
                    </li>

                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">2</span>
                        <span>Phone or email is required.</span>
                    </li>

                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">3</span>
                        <span>Duplicate check will happen using phone/email.</span>
                    </li>

                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">4</span>
                        <span>New leads will be created as open leads.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-5">
                <h3 class="font-semibold text-yellow-900">WhatsApp trigger</h3>

                <p class="mt-2 text-sm text-yellow-800">
                    Manual lead WhatsApp template trigger is not enabled yet. We will add it after confirming the create flow.
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
                <h3 class="font-semibold text-blue-900">Recommended phone format</h3>

                <p class="mt-2 text-sm text-blue-800">
                    Use country code where possible. Example: 971586934377 instead of 0586934377.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection
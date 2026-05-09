@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Import Leads</h1>
            <p class="text-sm text-gray-500 mt-1">
                Upload leads using the CSV sample format. This import supports categorization, vehicle details, retention tags, and follow-up fields.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ asset('samples/sample_lead_import.csv') }}"
               download
               class="inline-flex items-center px-4 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-medium hover:bg-green-100">
                Download Sample CSV
            </a>

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

    @if(session('csv_errors') && count(session('csv_errors')))
        <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
            <div class="font-semibold mb-1">Some rows were skipped:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach(session('csv_errors') as $csvError)
                    <li>{{ $csvError }}</li>
                @endforeach
            </ul>
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

        {{-- Upload Card --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Upload CSV</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Accepted format: .csv. Maximum file size: 5 MB.
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl">
                    📊
                </div>
            </div>

            <form method="POST"
                  action="{{ route('admin.leads.import.process') }}"
                  enctype="multipart/form-data"
                  class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select CSV file
                    </label>

                    <input type="file"
                           name="csv_file"
                           accept=".csv,text/csv"
                           required
                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <p class="text-xs text-gray-500 mt-2">
                        Use the sample CSV exactly. Do not rename the column headers.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Upload Leads
                    </button>

                    <a href="{{ asset('samples/sample_lead_import.csv') }}"
                       download
                       class="inline-flex items-center px-5 py-2.5 rounded-lg bg-gray-50 text-gray-700 text-sm font-medium border border-gray-200 hover:bg-gray-100">
                        Download Sample CSV
                    </a>
                </div>
            </form>
        </div>

        {{-- Instructions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900">How to import</h2>

            <ol class="mt-4 space-y-4 text-sm text-gray-700">
                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">1</span>
                    <span>Download the sample CSV.</span>
                </li>

                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">2</span>
                    <span>Fill lead, service, vehicle, follow-up, and retention fields.</span>
                </li>

                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">3</span>
                    <span>Keep phone numbers as plain text, not scientific format.</span>
                </li>

                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">4</span>
                    <span>Upload the CSV file using the form.</span>
                </li>

                <li class="flex gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">5</span>
                    <span>The system will create leads, link clients, create vehicles, and check duplicates.</span>
                </li>
            </ol>
        </div>
    </div>

    {{-- Required Columns --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">CSV Sheet Format</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Column</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Required?</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Example</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Notes</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">name</td>
                        <td class="px-4 py-3 text-green-700">Yes</td>
                        <td class="px-4 py-3">Sam Abhishek</td>
                        <td class="px-4 py-3 text-gray-600">Customer or lead name.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">phone</td>
                        <td class="px-4 py-3 text-green-700">Yes</td>
                        <td class="px-4 py-3">971587000000</td>
                        <td class="px-4 py-3 text-gray-600">Use country code. Keep as plain text.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">email</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">sam@example.com</td>
                        <td class="px-4 py-3 text-gray-600">Optional.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">source</td>
                        <td class="px-4 py-3 text-green-700">Yes</td>
                        <td class="px-4 py-3">website</td>
                        <td class="px-4 py-3 text-gray-600">website, walk-in, whatsapp, meta, google.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">notes</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">Interested in service booking</td>
                        <td class="px-4 py-3 text-gray-600">Any extra lead information.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">preferred_channel</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">whatsapp</td>
                        <td class="px-4 py-3 text-gray-600">whatsapp, phone, email.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">service_category</td>
                        <td class="px-4 py-3 text-green-700">Yes</td>
                        <td class="px-4 py-3">service</td>
                        <td class="px-4 py-3 text-gray-600">service, repair, quote, complaint, emergency, enquiry.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">service_type</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">general service</td>
                        <td class="px-4 py-3 text-gray-600">Oil change, AC repair, tyres, detailing, etc.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">vehicle_make</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">Toyota</td>
                        <td class="px-4 py-3 text-gray-600">Vehicle brand.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">vehicle_model</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">Camry</td>
                        <td class="px-4 py-3 text-gray-600">Vehicle model.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">vehicle_year</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">2020</td>
                        <td class="px-4 py-3 text-gray-600">Manufacturing year.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">plate_number</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">Dubai A 12345</td>
                        <td class="px-4 py-3 text-gray-600">Useful for repeat customer matching.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">lead_temperature</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">warm</td>
                        <td class="px-4 py-3 text-gray-600">hot, warm, cold.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">lead_priority</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">medium</td>
                        <td class="px-4 py-3 text-gray-600">low, medium, high, urgent.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">customer_type</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">new</td>
                        <td class="px-4 py-3 text-gray-600">new, existing, fleet, corporate.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">follow_up_required</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">yes</td>
                        <td class="px-4 py-3 text-gray-600">yes or no.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">follow_up_date</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">2026-05-07</td>
                        <td class="px-4 py-3 text-gray-600">Use YYYY-MM-DD format.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">campaign_name</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">May Service Offer</td>
                        <td class="px-4 py-3 text-gray-600">Useful for campaign tracking.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">retention_tag</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">service_due</td>
                        <td class="px-4 py-3 text-gray-600">service_due, quote_followup, inactive, repeat_customer.</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">assigned_to</td>
                        <td class="px-4 py-3 text-gray-600">No</td>
                        <td class="px-4 py-3">manager@example.com</td>
                        <td class="px-4 py-3 text-gray-600">User email or manager reference.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Important Notes --}}
    <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-5">
        <h3 class="font-semibold text-yellow-900">Before uploading</h3>

        <ul class="mt-2 text-sm text-yellow-800 list-disc list-inside space-y-1">
            <li>Do not change the column names in the first row.</li>
            <li>Phone numbers should include country code where possible.</li>
            <li>Keep phone numbers as plain text to avoid Excel converting them to scientific notation.</li>
            <li>If the same phone number already exists, it may be marked as duplicate.</li>
            <li>Manual imported leads will not automatically send WhatsApp messages until the campaign/template trigger is enabled.</li>
        </ul>
    </div>

</div>
@endsection
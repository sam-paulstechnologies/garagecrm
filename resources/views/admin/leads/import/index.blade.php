@extends('layouts.app')

@section('title', 'Import Leads')

@section('content')
@include('admin.leads.import.partials._styles')

@php
    $campaignTypes = $campaignTypes ?? [
        'New Lead Campaign',
        'Service Offer Campaign',
        'Retention Campaign',
        'Lost Lead Revival Campaign',
        'WhatsApp Campaign',
        'Meta Lead Form Campaign',
        'Website Form Campaign',
        'Walk-in / Manual Entry',
        'Referral Campaign',
        'Fleet Campaign',
    ];
@endphp

<div class="sf-page sf-import-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Import
            </div>

            <h1 class="sf-page-title mt-3">
                Import Leads
            </h1>

            <p class="sf-page-subtitle">
                Upload recent or new leads only. Historic customer and vehicle data belongs under Client Import.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.import.preview') }}" class="sf-btn-soft-blue">
                Preview Instant ACK
            </a>

            <a href="{{ route('admin.leads.import.preview.batches.index') }}" class="sf-btn-soft-blue">
                Saved Previews
            </a>

            <a href="{{ asset('samples/sample_lead_import.csv') }}" download class="sf-btn-soft-blue">
                Download Sample CSV
            </a>

            <a href="{{ route('admin.leads.index') }}" class="sf-btn-primary">
                Back to Leads
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('csv_errors') && count(session('csv_errors')))
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Some rows were skipped:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach(session('csv_errors') as $csvError)
                    <li>{{ $csvError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Upload Card --}}
        <div class="lg:col-span-2">
            <div class="sf-card">
                <div class="sf-card-header flex items-start justify-between gap-4">
                    <div>
                        <h2 class="sf-section-title">
                            Upload CSV
                        </h2>

                        <p class="sf-section-subtitle">
                            Accepted format: .csv. Maximum file size: 5 MB.
                        </p>
                    </div>

                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl text-orange-300 ring-1 ring-orange-400/20">
                        📊
                    </div>
                </div>

                <div class="sf-card-body">
                    <form method="POST"
                          action="{{ route('admin.leads.import.preview.process') }}"
                          enctype="multipart/form-data"
                          class="space-y-5">
                        @csrf

                        <div>
                            <label for="campaign_type" class="sf-label">
                                Default Campaign Type
                            </label>

                            <select id="campaign_type"
                                    name="campaign_type"
                                    class="sf-import-select focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                @foreach($campaignTypes as $campaignType)
                                    <option value="{{ $campaignType }}" @selected(old('campaign_type', 'New Lead Campaign') === $campaignType)>
                                        {{ $campaignType }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="sf-help">
                                Used only when a CSV row does not contain campaign_type. If campaign_type is present in the file, the row value wins.
                            </p>

                            @error('campaign_type')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Select CSV file
                            </label>

                            <input type="file"
                                   id="lead_import_file"
                                   name="lead_file"
                                   accept=".csv,.txt,.xls,.xlsx,text/csv"
                                   required
                                   data-selected-file-target="lead_import_file_name"
                                   class="sf-import-field block file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                            <p class="sf-help">
                                The upload creates a preview batch first. Phone numbers should stay plain text, for example 971587000000.
                            </p>

                            <p id="lead_import_file_name" class="sf-help mt-2 font-extrabold text-orange-200" aria-live="polite">
                                No file selected yet.
                            </p>

                            @error('lead_file')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror

                            @error('file')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="sf-btn-primary">
                                Preview Upload
                            </button>

                            <a href="{{ asset('samples/sample_lead_import.csv') }}" download class="sf-btn-secondary">
                                Download Sample CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="space-y-6">

            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        How to import
                    </h2>

                    <p class="sf-section-subtitle">
                        Follow this sequence to avoid failed rows.
                    </p>
                </div>

                <div class="sf-card-body">
                    <ol class="space-y-4 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">1</span>
                            <span>Download the sample CSV.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">2</span>
                            <span>Fill customer, source, campaign type, service, vehicle, and preferred timing fields.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">3</span>
                            <span>Keep phone numbers as plain text, not scientific format.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">4</span>
                            <span>Upload the CSV file using the form.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">5</span>
                            <span>The system will create recent leads, link clients, create useful vehicles, and check duplicates without sending WhatsApp.</span>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="sf-import-info-card">
                <h3 class="font-extrabold text-blue-300">
                    Phone Format
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Use UAE country code format where possible, for example 971587000000.
                </p>
            </div>

            <div class="sf-import-info-card">
                <h3 class="font-extrabold text-orange-300">
                    Duplicate Check
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Existing phone numbers or emails may be flagged as potential duplicates. This page is for new/recent lead capture only.
                </p>
            </div>

        </div>
    </div>

    {{-- Required Columns --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                CSV Sheet Format
            </h2>

            <p class="sf-section-subtitle">
                Use these headers in the first row of your CSV file.
            </p>
        </div>

        <div class="sf-table-scroll">
            <table class="sf-table sf-import-table">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Required?</th>
                        <th>Example</th>
                        <th>Notes</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td class="font-extrabold text-white">customer_name</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>Sam Abhishek</td>
                        <td>Customer or lead name.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">phone</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>971587000000</td>
                        <td>Use country code. Keep as plain text.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">email</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>sam@example.com</td>
                        <td>Optional.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">lead_source</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>website</td>
                        <td>website, walk-in, whatsapp, meta, google.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">campaign_type</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>New Lead Campaign</td>
                        <td>CSV row value wins. If blank, the form campaign type is used.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">campaign_name</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>June Service Offer</td>
                        <td>Campaign or source reference.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">service_type</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>general service</td>
                        <td>Oil change, AC repair, tyres, detailing, etc.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">vehicle_make</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Toyota</td>
                        <td>Vehicle brand.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">vehicle_model</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Camry</td>
                        <td>Vehicle model.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">vehicle_year</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>2021</td>
                        <td>Vehicle manufacturing year.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">plate_number</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Dubai A 12345</td>
                        <td>Optional plate number.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">city</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Dubai</td>
                        <td>Stored in upload context for reporting/journey use.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">preferred_date</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>2026-06-20</td>
                        <td>Used as follow-up date when available.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">preferred_time</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>morning</td>
                        <td>Stored in upload context for future journey/booking use.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">notes</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Interested in service booking</td>
                        <td>Any extra lead information.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-selected-file-target]').forEach(function (input) {
                var target = document.getElementById(input.getAttribute('data-selected-file-target'));

                if (!target) {
                    return;
                }

                input.addEventListener('change', function () {
                    target.textContent = input.files && input.files.length
                        ? 'Selected file: ' + input.files[0].name
                        : 'No file selected yet.';
                });
            });
        });
    </script>
@endpush

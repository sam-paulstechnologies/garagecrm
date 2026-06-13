@extends('layouts.app')

@section('title', 'Import Leads')

@section('content')
@include('admin.leads.import.partials._styles')

<div class="sf-page sf-import-page space-y-6">

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
                Upload leads using the CSV sample format. This import supports categorization, vehicle details, retention tags, and follow-up fields.
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
                          action="{{ route('admin.leads.import.process') }}"
                          enctype="multipart/form-data"
                          class="space-y-5">
                        @csrf

                        <div>
                            <label for="import_type" class="sf-label">
                                Import Type
                            </label>

                            <select id="import_type"
                                    name="import_type"
                                    class="sf-import-select focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="standard" @selected(old('import_type', 'standard') === 'standard')>
                                    Standard Import - use source values from CSV
                                </option>
                                <option value="historic" @selected(old('import_type') === 'historic')>
                                    Historic Data Import - customer and vehicle history only
                                </option>
                                <option value="recent" @selected(old('import_type') === 'recent')>
                                    Recent Leads Import - needs manual follow-up
                                </option>
                            </select>

                            <p class="sf-help">
                                Historic imports are inactive history records. Recent imports become active follow-up leads without sending WhatsApp messages.
                            </p>

                            @error('import_type')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Select CSV file
                            </label>

                            <input type="file"
                                   name="csv_file"
                                   accept=".csv,text/csv"
                                   required
                                   class="sf-import-field block file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                            <p class="sf-help">
                                Use the sample CSV exactly. Do not rename the column headers.
                            </p>

                            @error('csv_file')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror

                            @error('file')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="sf-btn-primary">
                                Upload Leads
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
                            <span>Fill lead, service, vehicle, follow-up, and retention fields.</span>
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
                            <span>The system will create leads, link clients, create vehicles, and check duplicates.</span>
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
                    Existing phone numbers or emails may be flagged as potential duplicates after import.
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
                        <td class="font-extrabold text-white">name</td>
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
                        <td class="font-extrabold text-white">source</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>website</td>
                        <td>website, walk-in, whatsapp, meta, google.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">notes</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Interested in service booking</td>
                        <td>Any extra lead information.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">preferred_channel</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>whatsapp</td>
                        <td>whatsapp, phone, email.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">service_category</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>service</td>
                        <td>service, repair, quote, complaint, emergency, enquiry.</td>
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
                        <td class="font-extrabold text-white">lead_temperature</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>hot</td>
                        <td>hot, warm, cold.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">lead_priority</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>high</td>
                        <td>urgent, high, medium, low.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">follow_up_required</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>1</td>
                        <td>Use 1/yes/true if follow-up is required.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">follow_up_date</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>2026-05-20</td>
                        <td>Recommended format: YYYY-MM-DD.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">campaign_name</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>vehicle import test</td>
                        <td>Campaign or source reference.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">retention_tag</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>service due</td>
                        <td>Used for segmentation and retention buckets.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

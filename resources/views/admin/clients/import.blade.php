@extends('layouts.app')

@section('title', 'Import Clients')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Import
            </div>

            <h1 class="sf-page-title mt-3">
                Import Clients
            </h1>

            <p class="sf-page-subtitle">
                Upload client records using CSV or Excel. Required fields are name, phone, and email.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (file_exists(public_path('samples/sample_client_import.csv')))
                <a href="{{ asset('samples/sample_client_import.csv') }}"
                   class="sf-btn-soft-blue"
                   download>
                    Download Sample CSV
                </a>
            @endif

            @if (file_exists(public_path('samples/client_import_sample.xlsx')))
                <a href="{{ asset('samples/client_import_sample.xlsx') }}"
                   class="sf-btn-secondary"
                   download>
                    Download Sample Excel
                </a>
            @endif

            <a href="{{ route('admin.clients.index') }}" class="sf-btn-primary">
                ← Back to Clients
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

    @if(session('import_success'))
        <div class="sf-alert-success">
            <strong class="font-extrabold">Upload Successful!</strong>

            <span class="block sm:inline">
                {{ session('imported') }} out of {{ session('total') }} clients imported.
                @if(session('skipped') > 0)
                    {{ session('skipped') }} skipped due to duplicates or errors.
                @endif
            </span>
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

        {{-- Upload Form --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.clients.import') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="sf-card">
                @csrf

                <div class="sf-card-header flex items-start justify-between gap-4">
                    <div>
                        <h2 class="sf-section-title">
                            Upload Client File
                        </h2>

                        <p class="sf-section-subtitle">
                            Accepted formats: .xlsx, .xls, .csv. Use the sample file to avoid failed rows.
                        </p>
                    </div>

                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl text-orange-300 ring-1 ring-orange-400/20">
                        📤
                    </div>
                </div>

                <div class="sf-card-body space-y-5">

                    {{-- Sample Downloads --}}
                    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5">
                        <div class="font-extrabold text-blue-300">
                            Sample Files
                        </div>

                        <p class="mt-1 text-sm font-medium leading-6 text-blue-100/80">
                            Download the sample sheet, fill your client data, and upload it here.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if (file_exists(public_path('samples/sample_client_import.csv')))
                                <a href="{{ asset('samples/sample_client_import.csv') }}"
                                   class="sf-btn-soft-blue"
                                   download>
                                    📥 Download Sample CSV
                                </a>
                            @endif

                            @if (file_exists(public_path('samples/client_import_sample.xlsx')))
                                <a href="{{ asset('samples/client_import_sample.xlsx') }}"
                                   class="sf-btn-secondary"
                                   download>
                                    📥 Download Sample Excel
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- File Upload --}}
                    <div>
                        <label for="file" class="sf-label">
                            Upload File <span class="text-red-300">*</span>
                        </label>

                        <input type="file"
                               name="file"
                               id="file"
                               accept=".xlsx,.xls,.csv"
                               required
                               class="block w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                        <p class="sf-help">
                            Upload CSV or Excel file with required columns: name, phone, email.
                        </p>

                        @error('file')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap gap-2">
                        <button class="sf-btn-primary" type="submit">
                            Import Clients
                        </button>

                        <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Notes --}}
        <div class="space-y-6">

            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    Required Columns
                </h3>

                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="sf-badge-orange">name</span>
                    <span class="sf-badge-orange">phone</span>
                    <span class="sf-badge-orange">email</span>
                </div>
            </div>

            <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-green-300">
                    Optional Columns
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                    whatsapp, dob, gender, address, city, state, postal_code, country, source, status, notes, is_vip, preferred_channel.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Import Tip
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Dates should be MM/DD/YYYY. Phone and WhatsApp numbers should be digits only, without plus signs or spaces.
                </p>
            </div>

        </div>
    </div>

    {{-- Import Format --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Client Import Format
            </h2>

            <p class="sf-section-subtitle">
                Use these columns to keep client profiles clean and ready for CRM use.
            </p>
        </div>

        <div class="sf-table-scroll">
            <table class="sf-table">
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
                        <td>Client full name.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">phone</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>971586934377</td>
                        <td>Use digits only where possible.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">email</td>
                        <td><span class="sf-badge-green">Yes</span></td>
                        <td>sam@example.com</td>
                        <td>Used for contact and dedupe.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">whatsapp</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>971586934377</td>
                        <td>Preferred WhatsApp number.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">dob</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>02/16/1990</td>
                        <td>Use MM/DD/YYYY.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">gender</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>male</td>
                        <td>Optional profile data.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">address</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>JVC, Dubai</td>
                        <td>Client address.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">city</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>Dubai</td>
                        <td>City name.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">country</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>UAE</td>
                        <td>Country name.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">source</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>website</td>
                        <td>Lead/client source.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">status</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>active</td>
                        <td>Client status if supported.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">notes</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>VIP client</td>
                        <td>Internal notes.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">is_vip</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>1</td>
                        <td>Use 1/yes/true if VIP.</td>
                    </tr>

                    <tr>
                        <td class="font-extrabold text-white">preferred_channel</td>
                        <td><span class="sf-badge-slate">No</span></td>
                        <td>whatsapp</td>
                        <td>whatsapp, phone, email.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
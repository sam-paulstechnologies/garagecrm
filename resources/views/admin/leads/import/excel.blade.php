@extends('layouts.app')

@section('title', 'Upload Leads via Excel')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Import
            </div>

            <h1 class="sf-page-title mt-3">
                Upload Leads via Excel
            </h1>

            <p class="sf-page-subtitle">
                Upload an Excel or CSV file using the approved SayaraForce lead import format.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.import.sample') }}" class="sf-btn-soft-blue">
                Download Sample
            </a>

            <a href="{{ route('admin.leads.import.options') }}" class="sf-btn-secondary">
                Back to Import
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
                            Select File
                        </h2>

                        <p class="sf-section-subtitle">
                            Accepted formats: .xlsx, .xls, .csv. Maximum file size: 10 MB.
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
                            <label class="sf-label">
                                Excel / CSV File
                            </label>

                            <input type="file"
                                   id="lead_excel_file"
                                   name="file"
                                   accept=".xlsx,.xls,.csv"
                                   required
                                   data-selected-file-target="lead_excel_file_name"
                                   class="block w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                            <p class="sf-help">
                                Please use the sample sheet columns: name, phone, email, source, notes, preferred_channel.
                            </p>

                            <p id="lead_excel_file_name" class="sf-help mt-2 font-extrabold text-orange-200" aria-live="polite">
                                No file selected yet.
                            </p>

                            @error('file')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="sf-btn-primary">
                                Upload File
                            </button>

                            <a href="{{ route('admin.leads.import.sample') }}" class="sf-btn-secondary">
                                Download Sample Sheet
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            <div class="rounded-3xl border border-yellow-400/20 bg-yellow-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-yellow-300">
                    Controlled Launch Note
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                    This upload screen is ready. The backend Excel processing is currently disabled until we enable the final import parser.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Recommended Phone Format
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Use country code where possible. Example: 971586934377 instead of 0586934377.
                </p>
            </div>

            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    Duplicate Handling
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Existing phone numbers or emails may be marked as potential duplicates during import.
                </p>
            </div>

        </div>
    </div>

    {{-- Quick Checklist --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Before Uploading
            </h2>

            <p class="sf-section-subtitle">
                Check these items before importing leads into the CRM.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="font-extrabold text-white">
                        1. Use correct headers
                    </div>

                    <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                        Do not rename or remove the first-row column names.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="font-extrabold text-white">
                        2. Add phone or email
                    </div>

                    <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                        Each lead must have at least a phone number or email address.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="font-extrabold text-white">
                        3. Use country code
                    </div>

                    <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                        Example: 971586934377 instead of only 0586934377.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="font-extrabold text-white">
                        4. Check duplicates
                    </div>

                    <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                        Existing numbers may be marked as duplicate during import.
                    </p>
                </div>
            </div>
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

@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Upload Leads via Excel</h1>
            <p class="text-sm text-gray-500 mt-1">
                Upload an Excel or CSV file using the approved SayaraForce lead import format.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.import.sample') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-medium hover:bg-green-100">
                Download Sample
            </a>

            <a href="{{ route('admin.leads.import.options') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-50 text-gray-700 text-sm font-medium border border-gray-200 hover:bg-gray-100">
                Back to Import
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

    {{-- Upload Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Select File</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Accepted formats: .xlsx, .xls, .csv. Maximum file size: 10 MB.
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
                    Excel / CSV File
                </label>

                <input type="file"
                       name="file"
                       accept=".xlsx,.xls,.csv"
                       required
                       class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">

                <p class="text-xs text-gray-500 mt-2">
                    Please use the sample sheet columns: name, phone, email, source, notes, preferred_channel.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                    Upload File
                </button>

                <a href="{{ route('admin.leads.import.sample') }}"
                   class="inline-flex items-center px-5 py-2.5 rounded-lg bg-gray-50 text-gray-700 text-sm font-medium border border-gray-200 hover:bg-gray-100">
                    Download Sample Sheet
                </a>
            </div>
        </form>
    </div>

    {{-- Quick Checklist --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Before Uploading</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                <div class="font-semibold text-gray-900">1. Use correct headers</div>
                <p class="text-gray-600 mt-1">
                    Do not rename or remove the first-row column names.
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                <div class="font-semibold text-gray-900">2. Add phone or email</div>
                <p class="text-gray-600 mt-1">
                    Each lead must have at least a phone number or email address.
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                <div class="font-semibold text-gray-900">3. Use country code</div>
                <p class="text-gray-600 mt-1">
                    Example: 971586934377 instead of only 0586934377.
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                <div class="font-semibold text-gray-900">4. Check duplicates</div>
                <p class="text-gray-600 mt-1">
                    Existing numbers may be marked as duplicate during import.
                </p>
            </div>
        </div>
    </div>

    {{-- Controlled Launch Notice --}}
    <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-5">
        <h3 class="font-semibold text-yellow-900">Controlled launch note</h3>

        <p class="mt-2 text-sm text-yellow-800">
            This upload screen is ready. The backend Excel processing is currently disabled until we enable the final import parser.
        </p>
    </div>

</div>
@endsection
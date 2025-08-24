@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10 bg-white shadow p-6 rounded">
    <h2 class="text-xl font-semibold mb-4">Import Clients</h2>

    <!-- ✅ Sample File Download(s) -->
    <div class="mb-4 space-x-4">
        {{-- CSV (exists in your repo) --}}
        @if (file_exists(public_path('samples/sample_client_import.csv')))
            <a href="{{ asset('samples/sample_client_import.csv') }}"
               class="text-blue-600 hover:underline text-sm font-medium"
               download>
                📥 Download Sample CSV
            </a>
        @endif

        {{-- XLSX (optional; shown only if present to avoid 404) --}}
        @if (file_exists(public_path('samples/client_import_sample.xlsx')))
            <a href="{{ asset('samples/client_import_sample.xlsx') }}"
               class="text-blue-600 hover:underline text-sm font-medium"
               download>
                📥 Download Sample Excel
            </a>
        @endif
    </div>

    <!-- 📤 Upload Form -->
    <form action="{{ route('admin.clients.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block font-medium mb-1" for="file">Upload File (.xlsx or .csv)</label>
            <input type="file" name="file" class="border border-gray-300 rounded w-full py-2 px-3">
            @error('file')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded" type="submit">
            Import
        </button>
    </form>

    <!-- ℹ️ Import Instructions -->
    <div class="mt-6 text-sm text-gray-500">
        <p><strong>Required Columns:</strong> name, phone, email</p>
        <p><strong>Optional Columns:</strong> whatsapp, dob, gender, address, city, state, postal_code, country, source, status, notes, is_vip, preferred_channel</p>
        <p class="mt-2 italic text-xs">
            Tip: Dates should be MM/DD/YYYY. Phone/WhatsApp should be digits only (no + or spaces).
        </p>
    </div>
</div>
@endsection

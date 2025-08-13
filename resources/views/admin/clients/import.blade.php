@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10 bg-white shadow p-6 rounded">
    <h2 class="text-xl font-semibold mb-4">Import Clients</h2>

    <!-- ✅ Sample File Download -->
    <div class="mb-4">
        <a href="{{ asset('samples/client_import_sample.xlsx') }}"
           class="text-blue-600 hover:underline text-sm font-medium">
            📥 Download Sample Excel File
        </a>
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
        <p class="mt-2 italic text-xs">Tip: Dates must be in MM/DD/YYYY format. Phone/WhatsApp must be plain numbers (no + or formatting).</p>
    </div>
</div>
@endsection

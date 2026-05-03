@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="text-xl font-semibold mb-4">Import Leads</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.leads.import.upload') }}" class="card p-4">
            <h3 class="font-semibold">📊 Upload Excel</h3>
            <p class="text-sm text-muted">Import leads via Excel workbook</p>
        </a>

        <a href="#" class="card p-4">
            <h3 class="font-semibold">📱 WhatsApp</h3>
            <p class="text-sm text-muted">Configure business WhatsApp number</p>
        </a>

        <a href="#" class="card p-4">
            <h3 class="font-semibold">📢 Meta / Google / Snapchat</h3>
            <p class="text-sm text-muted">Configure paid lead sources</p>
        </a>

        <a href="{{ route('admin.leads.custom-form') }}" class="card p-4">
            <h3 class="font-semibold">🌐 Custom Website Form</h3>
            <p class="text-sm text-muted">Embed form snippet</p>
        </a>
    </div>
</div>
@endsection

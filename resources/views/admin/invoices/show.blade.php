@extends('layouts.app')

@section('content')
<div class="container max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-4">Invoice Details</h2>

    <div class="bg-white border p-4 rounded shadow">
        <p><strong>Client:</strong> {{ $invoice->client->name ?? 'N/A' }}</p>
        <p><strong>Job:</strong> {{ $invoice->job->description ?? 'N/A' }}</p>
        <p><strong>Amount:</strong> ${{ number_format($invoice->amount, 2) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        <p><strong>Due Date:</strong> {{ $invoice->due_date }}</p>
    </div>
</div>
@endsection

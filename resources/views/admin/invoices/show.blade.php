@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
  @if(session('success')) <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div> @endif
  <h2 class="text-xl font-semibold mb-2">Invoice #{{ $invoice->id }}</h2>
  <p class="text-sm text-gray-600 mb-4">
    {{ $invoice->client->name ?? 'N/A' }}
    @if($invoice->job_id) • Job #{{ $invoice->job_id }} @endif
    • {{ strtoupper($invoice->status) }}
    • AED {{ number_format($invoice->amount,2) }}
  </p>
  <dl class="space-y-1">
    <div><b>Due Date:</b> {{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</div>
    <div><b>File:</b>
      @if($invoice->file_path)
        <a class="underline" href="{{ route('admin.invoices.download',$invoice) }}">Download</a>
      @else — @endif
    </div>
  </dl>
  <div class="mt-6">
    <a href="{{ route('admin.invoices.edit',$invoice) }}" class="px-3 py-2 bg-blue-600 text-white rounded">Edit</a>
  </div>
</div>
@endsection

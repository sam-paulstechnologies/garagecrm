@extends('layouts.app')

@section('content')
<div class="container max-w-xl mx-auto">
    <h2 class="text-xl font-bold mb-4">Create Invoice</h2>

    {{-- Show validation errors --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 mb-4 rounded border border-red-300">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.invoices.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label for="client_id" class="block mb-1">Client</label>
            <select name="client_id" id="client_id" class="w-full border px-3 py-2 rounded">
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="job_id" class="block mb-1">Job</label>
            <select name="job_id" id="job_id" class="w-full border px-3 py-2 rounded">
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->description }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="amount" class="block mb-1">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="w-full border px-3 py-2 rounded" required>
        </div>

        <div class="mb-4">
            <label for="status" class="block mb-1">Status</label>
            <select name="status" id="status" class="w-full border px-3 py-2 rounded">
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>

        <div class="mb-4">
            <label for="due_date" class="block mb-1">Due Date</label>
            <input type="date" name="due_date" id="due_date" class="w-full border px-3 py-2 rounded" required>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create</button>
    </form>
</div>
@endsection

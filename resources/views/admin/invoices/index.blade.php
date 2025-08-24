@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Invoices</h2>
        <a href="{{ route('admin.invoices.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+ Create Invoice</a>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="border px-3 py-1 rounded w-1/3" />
        <select name="status" class="border px-2 py-1 rounded">
            <option value="">All</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
        </select>
        <button class="bg-gray-600 text-white px-3 py-1 rounded">Filter</button>
    </form>

    @if(session('success'))
        <div class="mb-4 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
    @endif

    <table class="table-auto w-full text-sm border">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 border">Client</th>
                <th class="px-4 py-2 border">Job</th>
                <th class="px-4 py-2 border">Amount</th>
                <th class="px-4 py-2 border">Status</th>
                <th class="px-4 py-2 border">Due Date</th>
                <th class="px-4 py-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td class="border px-4 py-2">{{ $invoice->client->name ?? 'N/A' }}</td>
                    <td class="border px-4 py-2">{{ $invoice->job->description ?? 'N/A' }}</td>
                    <td class="border px-4 py-2">{{ number_format($invoice->amount, 2) }}</td>
                    <td class="border px-4 py-2 capitalize">{{ $invoice->status }}</td>
                    <td class="border px-4 py-2">{{ $invoice->due_date }}</td>
                    <td class="border px-4 py-2 flex flex-wrap gap-3">
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-blue-600 hover:underline">View</a>
                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="text-indigo-600 hover:underline">Edit</a>

                        @if($invoice->file_path)
                            <a href="{{ route('admin.invoices.download', $invoice) }}" class="text-purple-700 hover:underline">Download</a>

                            @php
                                $isPdf = Str::startsWith($invoice->file_type ?? '', 'application/pdf')
                                    || Str::endsWith(strtolower($invoice->file_path), '.pdf');
                            @endphp
                            @if($isPdf)
                                <a href="{{ route('admin.invoices.view', $invoice) }}" class="text-gray-700 hover:underline">View PDF</a>
                            @endif
                        @else
                            <span class="text-gray-500">No file</span>
                        @endif

                        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Archive this invoice?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

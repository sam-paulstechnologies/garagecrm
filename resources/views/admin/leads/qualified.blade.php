@extends('layouts.app')

@section('content')
<div class="px-6 py-4">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">
            Qualified Leads
        </h2>

        <a href="{{ route('admin.leads.index') }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded shadow">
            ← Back to Inbox
        </a>
    </div>

    {{-- Leads Table --}}
    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Source</th>
                    <th class="px-4 py-2">Assigned To</th>
                    <th class="px-4 py-2">Created</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($leads as $index => $lead)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $index + 1 }}</td>

                        <td class="px-4 py-2 font-medium text-gray-800">
                            {{ $lead->name }}
                        </td>

                        <td class="px-4 py-2">{{ $lead->phone ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $lead->email ?? '—' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($lead->source ?? '—') }}</td>

                        <td class="px-4 py-2">
                            {{ optional($lead->assignee)->name ?? '—' }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $lead->created_at?->format('d M Y') }}
                        </td>

                        <td class="px-4 py-2">
                            <a href="{{ route('admin.leads.show', $lead) }}"
                               class="text-blue-600 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-400">
                            No qualified leads found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>

</div>
@endsection

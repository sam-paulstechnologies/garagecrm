@extends('layouts.app')

@section('content')
<div class="px-6 py-6 space-y-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Leads — Action Required
            </h1>
            <p class="text-sm text-gray-500">
                New and open leads that need follow-up or qualification
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.import.options') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm shadow">
                ⬆ Import Leads
            </a>

            <a href="{{ route('admin.leads.create') }}"
               class="inline-flex items-center gap-2 border border-gray-300 hover:bg-gray-100 px-4 py-2 rounded-md text-sm">
                + Add Lead
            </a>

            <a href="{{ route('admin.leads.duplicates.index') }}"
               class="inline-flex items-center gap-2 text-sm text-amber-700 hover:text-amber-900">
                View Duplicates
            </a>
        </div>
    </div>

    {{-- ================= SEARCH ================= --}}
    <form method="GET" class="max-w-sm">
        <input
            name="q"
            value="{{ $q }}"
            placeholder="Search name, phone, or email"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                   focus:ring-2 focus:ring-blue-500 focus:outline-none"
        />
    </form>

    {{-- ================= TABLE ================= --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Mode</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Source</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($leads as $lead)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-blue-600 font-medium">
                            <a href="{{ route('admin.leads.show', $lead) }}">
                                {{ $lead->name }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $lead->phone ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                {{ ucfirst(str_replace('_',' ', $lead->status)) }}
                            </span>
                        </td>

                        {{-- ✅ AUTO / MANAGER BADGE --}}
                        <td class="px-4 py-3">
                            @if($lead->status === 'converted')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                                    Auto
                                </span>
                            @elseif($lead->status === 'contact_on_hold')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                    Manager
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            {{ $lead->source ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-gray-500">
                            {{ $lead->created_at->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-gray-400">
                            🎉 No leads need action right now.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ================= PAGINATION ================= --}}
    <div>
        {{ $leads->links() }}
    </div>

</div>
@endsection

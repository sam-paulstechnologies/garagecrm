@extends('layouts.app')

@section('title', 'Lead Upload Preview Batches')

@section('content')
@include('admin.leads.import.partials._styles')

<div class="sf-page sf-import-page space-y-6">
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">Lead Upload Preview</div>

            <h1 class="sf-page-title mt-3">Saved Preview Batches</h1>

            <p class="sf-page-subtitle">
                Reopen parsed lead upload previews. These batches do not create CRM records or send WhatsApp messages.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.import.preview') }}" class="sf-btn-primary">
                New Preview
            </a>

            <a href="{{ route('admin.leads.import.upload') }}" class="sf-btn-secondary">
                Direct Import
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="sf-alert-success">{{ session('success') }}</div>
    @endif

    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Recent preview batches</h2>
            <p class="sf-section-subtitle">Company-scoped saved previews for later review.</p>
        </div>

        <div class="sf-table-scroll overflow-x-auto">
            <table class="sf-table sf-import-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th>Rows</th>
                        <th>Valid / Warning / Invalid</th>
                        <th>Duplicates</th>
                        <th>ACK Ready</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>
                                <div class="font-extrabold text-white">{{ $batch->original_filename }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $batch->uploadedBy?->name ?? 'Unknown user' }}
                                </div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-200">{{ optional($batch->created_at)->format('d M Y') }}</div>
                                <div class="text-xs text-slate-400">{{ optional($batch->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="inline-flex rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-extrabold text-blue-200 ring-1 ring-blue-400/20">
                                    {{ \Illuminate\Support\Str::headline($batch->status) }}
                                </span>
                            </td>
                            <td>{{ $batch->total_rows }}</td>
                            <td>{{ $batch->valid_rows }} / {{ $batch->warning_rows }} / {{ $batch->invalid_rows }}</td>
                            <td>
                                Clients {{ $batch->duplicate_client_rows }}
                                <span class="text-slate-500">|</span>
                                Leads {{ $batch->duplicate_lead_rows }}
                            </td>
                            <td>{{ $batch->ready_ack_rows }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.leads.import.preview.batches.show', $batch) }}"
                                   class="inline-flex rounded-lg border border-orange-400/25 bg-orange-500/10 px-3 py-2 text-xs font-extrabold text-orange-200 hover:text-orange-100">
                                    View Preview
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                No lead upload preview batches yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($batches, 'links'))
            <div class="border-t border-slate-800 px-5 py-4">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

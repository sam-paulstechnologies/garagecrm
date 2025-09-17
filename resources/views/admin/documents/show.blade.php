@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-6 space-y-6">
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Preview panel --}}
        <div class="lg:w-2/3 bg-white shadow rounded">
            <div class="px-4 py-3 border-b">
                <h2 class="text-lg font-semibold">Preview</h2>
            </div>
            <div class="p-4">
                @php $mime = strtolower((string) $doc->mime); @endphp

                @if($doc->public_url && str_starts_with($mime, 'image/'))
                    <img src="{{ $doc->public_url }}" alt="{{ $doc->original_name }}" class="max-w-full rounded">
                @elseif($doc->public_url && ($mime === 'application/pdf' || str_ends_with($doc->public_url, '.pdf')))
                    <iframe src="{{ $doc->public_url }}" class="w-full" style="min-height: 70vh;"></iframe>
                @elseif($doc->public_url)
                    <a href="{{ $doc->public_url }}" class="text-blue-600 underline" target="_blank">Open file</a>
                @else
                    <p class="text-gray-500">No preview available.</p>
                @endif
            </div>
        </div>

        {{-- Metadata + assign --}}
        <div class="lg:w-1/3 space-y-6">
            <div class="bg-white shadow rounded">
                <div class="px-4 py-3 border-b">
                    <h2 class="text-lg font-semibold">Metadata</h2>
                </div>
                <div class="p-4 text-sm">
                    <div><span class="font-medium">Status:</span> {{ $doc->status }}</div>
                    <div><span class="font-medium">Type:</span> {{ $doc->type }}</div>
                    <div><span class="font-medium">Source:</span> {{ $doc->source }}</div>
                    <div><span class="font-medium">Sender:</span>
                        {{ $doc->sender_email ?? $doc->sender_phone ?? '—' }}
                    </div>
                    <div><span class="font-medium">Original:</span> {{ $doc->original_name }}</div>
                    <div><span class="font-medium">MIME:</span> {{ $doc->mime ?? '—' }}</div>
                    <div><span class="font-medium">Size:</span> {{ number_format(($doc->size ?? 0)/1024,1) }} KB</div>
                    <div><span class="font-medium">Hash:</span> <code class="break-all">{{ $doc->hash }}</code></div>
                    <div><span class="font-medium">Received:</span> {{ $doc->received_at?->format('Y-m-d H:i') ?? $doc->created_at->format('Y-m-d H:i') }}</div>
                    @if($doc->public_url)
                        <div class="mt-2"><a href="{{ $doc->public_url }}" class="text-blue-600 underline" target="_blank">Open public URL</a></div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow rounded">
                <div class="px-4 py-3 border-b">
                    <h2 class="text-lg font-semibold">Assign to Client / Job</h2>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('admin.documents.assign', $doc) }}" class="space-y-3">
                        @csrf

                        <label class="block text-sm">Type</label>
                        <select name="type" class="border rounded w-full px-3 py-2">
                            @foreach(['invoice','job_card','other'] as $tp)
                                <option value="{{ $tp }}" @selected($doc->type === $tp)>{{ $tp }}</option>
                            @endforeach
                        </select>

                        <label class="block text-sm">Client</label>
                        <select name="client_id" class="border rounded w-full px-3 py-2" required>
                            <option value="">Select client...</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" @selected($doc->client_id === $c->id)>{{ $c->name }} ({{ $c->id }})</option>
                            @endforeach
                        </select>

                        <label class="block text-sm">Job (optional)</label>
                        <select name="job_id" class="border rounded w-full px-3 py-2">
                            <option value="">—</option>
                            @foreach($jobs as $j)
                                <option value="{{ $j->id }}" @selected($doc->job_id === $j->id)>{{ $j->id }} — {{ $j->job_code ?? 'Job' }}</option>
                            @endforeach
                        </select>

                        {{-- optional status tweak --}}
                        <label class="block text-sm">Status</label>
                        <select name="status" class="border rounded w-full px-3 py-2">
                            @foreach(['assigned','needs_review','matched'] as $s)
                                <option value="{{ $s }}" @selected($doc->status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>

                        <button class="bg-gray-900 text-white px-4 py-2 rounded">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div>
        <a href="{{ route('admin.documents.index') }}" class="text-blue-600 underline">← Back to Inbox</a>
    </div>
</div>
@endsection

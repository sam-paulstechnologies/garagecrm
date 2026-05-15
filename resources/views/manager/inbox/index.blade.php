@extends('layouts.manager')

@section('title', 'Manager Inbox')

@section('content')
@php
    $leadName = function ($lead) {
        return $lead->name
            ?? $lead->full_name
            ?? $lead->customer_name
            ?? $lead->client_name
            ?? 'Lead #' . $lead->id;
    };

    $leadPhone = function ($lead) {
        return $lead->phone
            ?? $lead->mobile
            ?? $lead->phone_number
            ?? $lead->whatsapp_number
            ?? '-';
    };

    $messageText = function ($message) {
        return $message->content
            ?? $message->message
            ?? $message->body
            ?? $message->text
            ?? '';
    };

    $messageDirection = function ($message) {
        return strtolower((string) ($message->direction ?? 'inbound'));
    };
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Manager Inbox</h1>
            <p class="text-muted mb-0">
                View escalated customer conversations and reply from one place.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary">
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the form below.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <form method="GET" action="{{ route('manager.inbox.index') }}" class="row g-2">
                        <div class="col-12">
                            <input
                                type="text"
                                name="q"
                                value="{{ $q ?? request('q') }}"
                                class="form-control"
                                placeholder="Search name, phone, vehicle, notes"
                            >
                        </div>

                        <div class="col-8">
                            <select name="status" class="form-select">
                                <option value="">All open statuses</option>
                                @foreach(['New', 'Assigned', 'Attempting Contact', 'Contacted', 'Qualified', 'On Hold'] as $item)
                                    <option value="{{ $item }}" @selected(($status ?? request('status')) === $item)>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-4 d-grid">
                            <button class="btn btn-primary">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    @if($leads->count())
                        <div class="list-group list-group-flush">
                            @foreach($leads as $lead)
                                @php
                                    $isActive = $selectedLead && (int) $selectedLead->id === (int) $lead->id;
                                @endphp

                                <a
                                    href="{{ route('manager.inbox.show', $lead) }}"
                                    class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}"
                                >
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $leadName($lead) }}
                                            </div>

                                            <small class="{{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                                {{ $leadPhone($lead) }}
                                            </small>
                                        </div>

                                        <span class="badge {{ $isActive ? 'bg-light text-dark' : 'bg-secondary' }}">
                                            {{ $lead->status ?? 'New' }}
                                        </span>
                                    </div>

                                    @if(!empty($lead->vehicle_make) || !empty($lead->vehicle_model))
                                        <div class="small mt-1 {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                            {{ trim(($lead->vehicle_make ?? '') . ' ' . ($lead->vehicle_model ?? '')) }}
                                        </div>
                                    @endif

                                    @if(!empty($lead->escalated_at))
                                        <div class="small mt-1 {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                            Escalated: {{ \Carbon\Carbon::parse($lead->escalated_at)->format('d M Y, h:i A') }}
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <h6 class="mb-1">No conversations found</h6>
                            <p class="text-muted mb-0 small">
                                Escalated/open customer conversations will appear here.
                            </p>
                        </div>
                    @endif
                </div>

                @if(method_exists($leads, 'links'))
                    <div class="card-footer bg-white">
                        {{ $leads->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                @if($selectedLead)
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h5 class="mb-1">
                                {{ $leadName($selectedLead) }}
                            </h5>

                            <div class="text-muted small">
                                {{ $leadPhone($selectedLead) }}

                                @if(!empty($selectedLead->email))
                                    · {{ $selectedLead->email }}
                                @endif

                                @if(!empty($selectedLead->source))
                                    · Source: {{ $selectedLead->source }}
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            @if(Route::has('manager.leads.index'))
                                <a href="{{ route('manager.leads.index', ['q' => $leadPhone($selectedLead)]) }}" class="btn btn-sm btn-outline-secondary">
                                    Open Lead
                                </a>
                            @endif

                            <form method="POST" action="{{ route('manager.inbox.resume', $selectedLead) }}">
                                @csrf
                                @method('PATCH')

                                <button class="btn btn-sm btn-outline-primary">
                                    Resume Bot
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-body bg-light" style="min-height: 460px;">
                        @if($messages->count())
                            <div class="d-flex flex-column gap-3">
                                @foreach($messages as $message)
                                    @php
                                        $direction = $messageDirection($message);
                                        $isOutbound = in_array($direction, ['outbound', 'out', 'sent'], true);
                                        $text = $messageText($message);
                                    @endphp

                                    <div class="d-flex {{ $isOutbound ? 'justify-content-end' : 'justify-content-start' }}">
                                        <div
                                            class="p-3 rounded shadow-sm {{ $isOutbound ? 'bg-primary text-white' : 'bg-white' }}"
                                            style="max-width: 75%;"
                                        >
                                            <div style="white-space: pre-wrap;">{{ $text ?: '-' }}</div>

                                            <div class="small mt-2 {{ $isOutbound ? 'text-white-50' : 'text-muted' }}">
                                                {{ ucfirst($direction ?: 'inbound') }}

                                                @if(!empty($message->status))
                                                    · {{ ucfirst($message->status) }}
                                                @endif

                                                @if(!empty($message->created_at))
                                                    · {{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, h:i A') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="h-100 d-flex align-items-center justify-content-center text-center">
                                <div>
                                    <h5 class="mb-2">No messages found</h5>
                                    <p class="text-muted mb-0">
                                        Once inbound or outbound messages are logged, they will appear here.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer bg-white">
                        <form method="POST" action="{{ route('manager.inbox.reply', $selectedLead) }}">
                            @csrf

                            <label class="form-label">Reply</label>

                            <textarea
                                name="message"
                                rows="3"
                                class="form-control mb-3"
                                placeholder="Type manager reply..."
                                required
                            >{{ old('message') }}</textarea>

                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <small class="text-muted">
                                    This saves the manager reply in the conversation log.
                                </small>

                                <button class="btn btn-primary">
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="card-body d-flex align-items-center justify-content-center text-center" style="min-height: 520px;">
                        <div>
                            <h5 class="mb-2">Select a conversation</h5>
                            <p class="text-muted mb-0">
                                Choose a customer conversation from the left to view and reply.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Demo Center</h3>
            <div class="text-muted small">Unified Inbox • AI Suggestions • Audiences • Journeys</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.chat.index') }}" class="btn btn-outline-primary btn-sm">Open Inbox</a>
            <a href="{{ route('admin.ai.edit') }}" class="btn btn-outline-secondary btn-sm">AI Settings</a>
            <a href="{{ route('admin.audiences.index') }}" class="btn btn-outline-success btn-sm">Audiences</a>
        </div>
    </div>

    <div
        id="admin-demo"
        data-endpoint-metrics="{{ route('admin.demo.metrics') }}"
        data-endpoint-audiences="{{ route('admin.demo.audiences_summary') }}"
        data-endpoint-enrollments="{{ route('admin.demo.recent_enrollments') }}"

        data-endpoint-conv-list="{{ url('/admin/chat/json/list') }}"
        data-chat-messages="{{ url('/admin/chat') }}/__CID__/messages"
        data-chat-send="{{ url('/admin/chat') }}/__CID__/send"
        data-chat-smart="{{ url('/admin/chat') }}/__CID__/smart-replies"
        data-chat-markread="{{ url('/admin/chat') }}/__CID__/mark-read"
    ></div>
</div>
@endsection

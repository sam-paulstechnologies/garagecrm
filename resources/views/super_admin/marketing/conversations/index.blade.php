@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Platform Marketing Inbox',
        'subtitle' => 'Separate prospect conversations with AI, human takeover, campaign, delivery, and qualification context.'
    ])
    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">Prospect</th><th>State</th><th>Qualification</th><th>AI</th><th>Unread</th><th></th></tr></thead>
            <tbody>
                @forelse($conversations as $conversation)
                    <tr>
                        <td class="p-4">{{ $conversation->prospect?->business_name ?? $conversation->prospect?->contact_name ?? 'Prospect' }}</td>
                        <td>{{ str($conversation->state)->headline() }}</td>
                        <td>{{ str($conversation->qualification_status)->headline() }}</td>
                        <td>{{ $conversation->ai_enabled ? 'Enabled' : 'Paused' }}</td>
                        <td>{{ $conversation->unread_count }}</td>
                        <td><a class="font-bold text-emerald-300" href="{{ route('super-admin.marketing.conversations.show', $conversation) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center sa-muted">No platform conversations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $conversations->links() }}</div>
@endsection

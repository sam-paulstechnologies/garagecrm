@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => $prospect->business_name ?? 'Prospect Detail',
        'subtitle' => ($prospect->contact_name ?? 'Unknown contact').' · '.$prospect->whatsapp_number,
        'action' => new Illuminate\Support\HtmlString('<a href="'.route('super-admin.marketing.prospects.edit', $prospect).'" class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">Edit</a>')
    ])

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="sa-card rounded-3xl p-5 xl:col-span-2">
            <h2 class="text-lg font-black">Qualification</h2>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                @foreach([
                    'Status' => str($prospect->status)->headline(),
                    'Consent' => str($prospect->consent_status)->headline(),
                    'Product' => $prospect->interested_product,
                    'Business type' => $prospect->business_type,
                    'Branches' => $prospect->branches_count,
                    'Employees' => $prospect->employees_count,
                    'Current tools' => $prospect->current_software,
                    'Lead score' => $prospect->lead_score.'/100',
                ] as $label => $value)
                    <div class="sa-soft rounded-2xl p-3"><dt class="sa-label text-xs font-black uppercase">{{ $label }}</dt><dd class="mt-1 font-bold">{{ $value ?: 'Not captured' }}</dd></div>
                @endforeach
            </dl>
            <div class="sa-soft mt-4 rounded-2xl p-4 text-sm">
                <p class="sa-label text-xs font-black uppercase">Pain points</p>
                <p class="mt-2">{{ $prospect->pain_points ?: 'No pain points captured yet.' }}</p>
            </div>
        </div>
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Activity</h2>
            <div class="mt-4 space-y-3 text-sm">
                @forelse($prospect->campaignRecipients as $recipient)
                    <div class="sa-soft rounded-2xl p-3">{{ $recipient->campaign?->name }}<span class="sa-label block">{{ str($recipient->status)->headline() }}</span></div>
                @empty
                    <p class="sa-muted">No campaign history yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="sa-card mt-5 rounded-3xl p-5">
        <h2 class="text-lg font-black">Conversation History</h2>
        <div class="mt-4 grid gap-3">
            @forelse($prospect->conversations as $conversation)
                <a href="{{ route('super-admin.marketing.conversations.show', $conversation) }}" class="sa-soft rounded-2xl p-4 text-sm font-bold">{{ str($conversation->state)->headline() }} · {{ $conversation->messages->count() }} messages</a>
            @empty
                <p class="sa-muted text-sm">No conversations yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@extends(auth()->user()->role === 'manager' ? 'layouts.manager' : 'layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="sf-page notification-center">
    <div class="sf-card notification-heading">
        <div>
            <span class="sf-eyebrow">Service manager</span>
            <h1>Notifications</h1>
            <p>New enquiries, follow-ups, bookings, service reminders, and billing attention in one tenant-safe feed.</p>
        </div>
    </div>

    <div class="notification-list" aria-live="polite">
        @forelse($intents as $intent)
            <article class="sf-card notification-item" data-state="{{ $intent->state }}">
                <div>
                    <span class="sf-badge">{{ str($intent->type)->replace('_', ' ')->headline() }}</span>
                    <h2>{{ $intent->title }}</h2>
                    <p>{{ $intent->body }}</p>
                    <small>{{ $intent->created_at?->diffForHumans() }} · {{ str($intent->state)->headline() }}</small>
                </div>
                @if($intent->action_url)
                    <a class="sf-btn sf-btn-primary" href="{{ $intent->action_url }}">Open</a>
                @endif
            </article>
        @empty
            <div class="sf-card notification-empty">No notifications yet.</div>
        @endforelse
    </div>

    {{ $intents->links() }}
</div>

<style>
.notification-center{max-width:920px;margin:0 auto;padding:clamp(16px,3vw,32px)}
.notification-heading{padding:clamp(18px,4vw,32px);margin-bottom:18px}
.notification-heading h1{font-size:clamp(1.65rem,5vw,2.4rem);margin:.25rem 0}
.notification-list{display:grid;gap:12px}
.notification-item{display:flex;justify-content:space-between;gap:18px;align-items:center;padding:18px}
.notification-item h2{font-size:1.05rem;margin:.55rem 0 .25rem}
.notification-item p{margin:0 0 .5rem}.notification-empty{padding:28px;text-align:center}
@media(max-width:640px){.notification-item{align-items:stretch;flex-direction:column}.notification-item .sf-btn{width:100%;text-align:center;min-height:44px}}
</style>
@endsection

@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h5 class="mb-0">Feedback</h5>
    <a href="{{ route('admin.feedback.create') }}" class="btn btn-primary btn-sm">Add Feedback</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Context</th>
            <th>Rating</th>
            <th>Sentiment</th>
            <th>Comment</th>
            <th>Client/Lead</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                @if($r->booking_id) Booking #{{ $r->booking_id }} ({{ $r->booking_date }} / {{ $r->slot }})
                @elseif($r->opportunity_id) Opp #{{ $r->opportunity_id }} ({{ $r->opp_title }})
                @elseif($r->lead_id) Lead #{{ $r->lead_id }}
                @else - @endif
              </td>
              <td>{{ $r->rating }}</td>
              <td>{{ $r->sentiment }}</td>
              <td style="max-width:320px">{{ Str::limit($r->comment, 140) }}</td>
              <td>
                {{ $r->client_name ?? '—' }}
                @if($r->client_wa) <small class="text-muted d-block">{{ $r->client_wa }}</small> @endif
                @if(!$r->client_wa && $r->lead_phone_norm) <small class="text-muted d-block">{{ $r->lead_phone_norm }}</small> @endif
              </td>
              <td>{{ $r->created_at }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center p-4">No feedback yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $rows->links() }}</div>
</div>
@endsection

@extends('layouts.app')
@section('title','WhatsApp Trigger Mappings')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Trigger → Template</h1>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2" method="post" action="{{ route('admin.whatsapp.mappings.store') }}">
        @csrf
        <div class="col-md-5">
          <label class="form-label">Event</label>
          <select name="event_key" class="form-select" required>
            @foreach($eventKeys as $e)
              <option value="{{ $e }}">{{ $e }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Template</label>
          <select name="template_id" class="form-select">
            <option value="">— none —</option>
            @foreach($templates as $t)
              <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary w-100">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Event</th>
            <th>Template</th>
            <th>Status</th>
            <th>Updated</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($mappings as $m)
            <tr>
              <td><code>{{ $m->event_key }}</code></td>
              <td>{{ optional($m->template)->name ?? '—' }}</td>
              <td>
                <span class="badge bg-{{ $m->is_active ? 'success' : 'secondary' }}">
                  {{ $m->is_active ? 'Active' : 'Disabled' }}
                </span>
              </td>
              <td>{{ $m->updated_at?->diffForHumans() }}</td>
              <td class="text-end">
                <form action="{{ route('admin.whatsapp.mappings.toggle',$m) }}" method="post" class="d-inline">
                  @csrf <button class="btn btn-sm btn-outline-warning">Toggle</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No mappings yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

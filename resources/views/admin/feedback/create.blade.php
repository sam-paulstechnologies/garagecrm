@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Add Feedback</h5>
  </div>
  <form method="post" action="{{ route('admin.feedback.store') }}">
    @csrf
    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Booking ID</label>
          <input type="number" name="booking_id" class="form-control"
                 value="{{ old('booking_id', $prefill['booking_id'] ?? '') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Opportunity ID</label>
          <input type="number" name="opportunity_id" class="form-control"
                 value="{{ old('opportunity_id', $prefill['opportunity_id'] ?? '') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Lead ID</label>
          <input type="number" name="lead_id" class="form-control"
                 value="{{ old('lead_id', $prefill['lead_id'] ?? '') }}">
        </div>

        <div class="col-md-3">
          <label class="form-label">Rating (1–5)</label>
          <input type="number" min="1" max="5" name="rating" class="form-control"
                 value="{{ old('rating', $prefill['rating'] ?? 5) }}" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Source</label>
          <select name="source" class="form-select">
            <option value="admin" {{ old('source')==='admin' ? 'selected' : '' }}>admin</option>
            <option value="whatsapp" {{ old('source')==='whatsapp' ? 'selected' : '' }}>whatsapp</option>
            <option value="link" {{ old('source')==='link' ? 'selected' : '' }}>link</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Comment</label>
          <textarea name="comment" class="form-control" rows="3">{{ old('comment') }}</textarea>
        </div>
      </div>
    </div>
    <div class="card-footer d-flex gap-2">
      <a href="{{ route('admin.feedback.index') }}" class="btn btn-light">Cancel</a>
      <button class="btn btn-primary">Save Feedback</button>
    </div>
  </form>
</div>
@endsection

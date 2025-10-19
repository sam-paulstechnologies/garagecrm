@extends('admin.layouts.app')
@section('title','WhatsApp Settings')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-3">WhatsApp Settings</h1>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <div class="card">
    <div class="card-body">
      <form method="post" action="{{ route('admin.whatsapp.settings.save') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Manager Phone (E.164)</label>
          <input name="manager_phone" class="form-control" value="{{ old('manager_phone',$set->manager_phone) }}" placeholder="+9715...">
        </div>
        <div class="mb-3">
          <label class="form-label">Google Review Link</label>
          <input name="google_review_link" class="form-control" value="{{ old('google_review_link',$set->google_review_link) }}" placeholder="https://g.page/r/...">
        </div>
        <button class="btn btn-primary">Save</button>
      </form>
    </div>
  </div>
</div>
@endsection

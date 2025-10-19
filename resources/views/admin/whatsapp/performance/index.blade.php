@extends('admin.layouts.app')
@section('title','Messaging Performance')

@section('content')
<div class="container-fluid">
  <h1 class="h3 mb-3">Messaging Performance</h1>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
      <div class="text-muted">Sent (7d)</div><div class="display-6">{{ $stats['sent_7d'] }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
      <div class="text-muted">Delivered (7d)</div><div class="display-6">{{ $stats['delivered_7d'] }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
      <div class="text-muted">Failed (7d)</div><div class="display-6 text-danger">{{ $stats['failed_7d'] }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
      <div class="text-muted">Sent (30d)</div><div class="display-6">{{ $stats['sent_30d'] }}</div>
    </div></div></div>
  </div>

  <div class="card">
    <div class="card-header">Top Templates</div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr><th>Template</th><th>Count</th></tr>
        </thead>
        <tbody>
          @forelse($stats['top_templates'] as $row)
            <tr><td>{{ $row->template }}</td><td>{{ $row->c }}</td></tr>
          @empty
            <tr><td colspan="2" class="text-center text-muted py-3">No data yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

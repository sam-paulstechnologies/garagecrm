@extends('layouts.app')

@section('content')
<div class="container mx-auto">
  <h1 class="text-2xl font-bold mb-4">Monitoring & Insights</h1>

  <div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="p-4 border rounded">
      <div class="text-sm text-gray-500">AI vs Template vs Human (Today)</div>
      <div class="text-xl mt-2">{{ (int)($today->ai_count ?? 0) }} AI</div>
      <div>{{ (int)($today->template_count ?? 0) }} Template</div>
      <div>{{ (int)($today->human_count ?? 0) }} Human</div>
    </div>
    <div class="p-4 border rounded">
      <div class="text-sm text-gray-500">Avg Confidence (Today)</div>
      <div class="text-2xl mt-2">{{ number_format((float)($today->avg_confidence ?? 0),2) }}</div>
    </div>
    <div class="p-4 border rounded">
      <div class="text-sm text-gray-500">Manager Alerts (Today)</div>
      <div class="text-2xl mt-2">{{ (int)($today->alerts_count ?? 0) }}</div>
    </div>
  </div>

  <div class="p-4 border rounded">
    <div class="text-sm text-gray-500 mb-2">Daily trend</div>
    <table class="min-w-full text-sm">
      <thead><tr><th class="text-left p-2">Date</th><th class="p-2">AI</th><th class="p-2">Template</th><th class="p-2">Human</th><th class="p-2">Avg conf</th><th class="p-2">Alerts</th></tr></thead>
      <tbody>
        @foreach($daily as $d)
          <tr class="border-t">
            <td class="p-2 text-left">{{ $d->report_date }}</td>
            <td class="p-2 text-center">{{ $d->ai_count }}</td>
            <td class="p-2 text-center">{{ $d->template_count }}</td>
            <td class="p-2 text-center">{{ $d->human_count }}</td>
            <td class="p-2 text-center">{{ number_format((float)$d->avg_confidence,2) }}</td>
            <td class="p-2 text-center">{{ $d->alerts_count }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

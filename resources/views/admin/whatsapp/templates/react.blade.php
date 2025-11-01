@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <h1 class="text-xl font-semibold mb-4">WhatsApp Template Editor (React)</h1>

  <div id="wa-template-editor"
       data-initial='@json($template ?? [])'></div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/app.jsx')
  <script>
    // These listeners receive events dispatched from the React app.
    window.addEventListener('wa:save', async (e) => {
      const body = e.detail;
      const res = await fetch(@json(route('api.whatsapp.templates.store') ?? '/api/whatsapp/templates'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(body),
      });
      alert(res.ok ? 'Saved' : 'Save failed');
    });

    window.addEventListener('wa:preview', async (e) => {
      alert('Local preview is shown on the right.');
    });
  </script>
@endpush

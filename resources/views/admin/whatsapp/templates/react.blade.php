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
    // Example: hook save/preview to your API
    window.addEventListener('wa:save', async (e) => {
      const body = e.detail;
      const res = await fetch('/api/whatsapp/templates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(body),
      });
      alert(res.ok ? 'Saved' : 'Save failed');
    });

    window.addEventListener('wa:preview', async (e) => {
      // Call your preview API or just show toast
      alert('Local preview already visible on the right.');
    });
  </script>
@endpush

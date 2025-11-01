@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">New WhatsApp Template</h1>
        <a href="{{ route('admin.whatsapp.templates.index') }}" class="border rounded px-3 py-2">Back</a>
    </div>

    @include('admin.whatsapp.templates.form', [
        'mode'      => 'create',
        'action'    => route('admin.whatsapp.templates.store'),
        'template'  => null,
        'variables' => old('variables', []),
    ])
</div>
@endsection

@push('scripts')
  @vite(['resources/js/app.jsx'])
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('wa-template-editor');
      if (el && window.mountWaTemplateEditor) {
        // pass initial state for create
        const initial = {
          language: 'en',
          status: 'active',
          buttons: []
        };
        el.dataset.initial = JSON.stringify(initial);
        window.mountWaTemplateEditor();
      }
    });
  </script>
@endpush

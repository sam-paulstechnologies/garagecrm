@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Edit Template: {{ $template->name }}</h1>
        <a href="{{ route('admin.whatsapp.templates.index') }}" class="border rounded px-3 py-2">Back</a>
    </div>

    @include('admin.whatsapp.templates.form', [
        'mode'      => 'edit',
        'action'    => route('admin.whatsapp.templates.update', $template),
        'template'  => $template,
        'variables' => $template->variables ?? [],
    ])
</div>
@endsection

@push('scripts')
  @vite(['resources/js/app.jsx'])
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('wa-template-editor');
      if (el && window.mountWaTemplateEditor) {
        // seed React editor with full template json
        el.dataset.initial = @json($template->toArray());
        window.mountWaTemplateEditor();
      }
    });
  </script>
@endpush

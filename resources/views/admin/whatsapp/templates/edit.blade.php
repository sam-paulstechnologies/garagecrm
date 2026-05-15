@extends('layouts.app')

@section('title', 'Edit Template: ' . $template->name)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Edit Template
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Edit {{ $template->name }}
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Update the WhatsApp template content, provider name, language, status, and button JSON used by customer journey automations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.whatsapp.templates.show', $template) }}"
               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                Preview
            </a>

            <a href="{{ route('admin.whatsapp.templates.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Templates
            </a>
        </div>
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
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('wa-template-editor');

            if (el && window.mountWaTemplateEditor) {
                el.dataset.initial = @json($template->toArray());
                window.mountWaTemplateEditor();
            }
        });
    </script>
@endpush
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">New WhatsApp Template</h1>
        <a href="{{ route('admin.whatsapp.templates.index') }}" class="border rounded px-3 py-2">Back</a>
    </div>

    @include('admin.whatsapp.templates.form', [
        'mode' => 'create',
        'template' => null,
        'variables' => old('variables', []),
    ])
</div>
@endsection

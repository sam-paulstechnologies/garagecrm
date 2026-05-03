@php use Illuminate\Support\Str; @endphp

@extends('layouts.app')

@section('content')
<div class="px-6 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Website Forms</h1>
            <p class="text-sm text-gray-500 mt-1">
                Manage lead capture forms embedded on your website
            </p>
        </div>

        <a href="{{ route('admin.lead-sources.index') }}"
           class="px-4 py-2 rounded border bg-white hover:bg-gray-50">
            Back to Lead Sources
        </a>
    </div>

    @if (session('success'))
        <div class="p-3 rounded bg-green-50 text-green-800 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border rounded-lg p-5">
        <form method="POST"
              action="{{ route('admin.lead-sources.website.store') }}"
              class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Form name</label>
                <input name="form_name"
                       class="mt-1 w-full border rounded px-3 py-2"
                       required>
            </div>

            <div class="flex items-end">
                <button class="w-full px-4 py-2 rounded bg-gray-900 text-white">
                    Save Form
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-lg">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left">Form</th>
                    <th class="px-4 py-3 text-left">Token</th>
                    <th class="px-4 py-3 text-left">Last Lead</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($forms as $form)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $form->config['form_name'] ?? $form->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ Str::limit($form->form_token, 16) }}
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $form->last_received_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.lead-sources.website.show', $form) }}"
                               class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs">
                                View Embed
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No website forms created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

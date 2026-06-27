@extends('layouts.app')

@section('content')
<div class="sf-page w-full px-4 py-6 sm:px-6 lg:px-8">
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-wide text-orange-600 dark:text-orange-300">Lead Import</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">Upload Leads</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
          Upload an Excel or CSV file. The file name stays visible after selection so the demo flow is easy to follow.
        </p>
      </div>

      @if(\Illuminate\Support\Facades\Route::has('admin.leads.index'))
        <a href="{{ route('admin.leads.index') }}" class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-extrabold text-white hover:bg-orange-700">
          Back to Leads
        </a>
      @endif
    </div>

    <form method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
      @csrf

      <div>
        <label for="lead_upload_file" class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Lead file
        </label>
        <input
          id="lead_upload_file"
          type="file"
          name="file"
          required
          data-selected-file-target="lead_upload_file_name"
          class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-600 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-700 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
        >
        <p id="lead_upload_file_name" class="mt-2 text-sm font-extrabold text-orange-700 dark:text-orange-200" aria-live="polite">
          No file selected yet.
        </p>
      </div>

      <button class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-orange-700">
        Upload
      </button>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-selected-file-target]').forEach(function (input) {
        var target = document.getElementById(input.getAttribute('data-selected-file-target'));

        if (!target) {
          return;
        }

        input.addEventListener('change', function () {
          target.textContent = input.files && input.files.length
            ? 'Selected file: ' + input.files[0].name
            : 'No file selected yet.';
        });
      });
    });
  </script>
@endpush

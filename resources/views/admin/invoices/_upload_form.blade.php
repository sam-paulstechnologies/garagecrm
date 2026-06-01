{{-- resources/views/admin/invoices/partials/form.blade.php --}}

@once
    @push('styles')
        @include('admin.invoices.index-partials._styles')
    @endpush
@endonce

<div class="sf-invoices-page">
    <form method="POST"
          action="{{ $action }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        @include('admin.invoices.upload-partials._upload')
        @include('admin.invoices.upload-partials._metadata')
        @include('admin.invoices.upload-partials._job_primary')
        @include('admin.invoices.upload-partials._client_attachment')
        @include('admin.invoices.upload-partials._actions')
    </form>
</div>

{{-- resources/views/admin/opportunities/partials/errors.blade.php --}}

@if ($errors->any())
    <div class="sf-alert-danger">
        <div class="mb-2 font-extrabold">
            Validation Errors
        </div>

        <ul class="list-inside list-disc space-y-1 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
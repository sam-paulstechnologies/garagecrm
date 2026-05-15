{{-- resources/views/admin/bookings/partials/errors.blade.php --}}

@if ($errors->any())
    <div class="sf-alert-danger" role="alert">
        <div class="mb-2 font-extrabold">
            Whoops! There were some problems with your input.
        </div>

        <ul class="list-inside list-disc space-y-1 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
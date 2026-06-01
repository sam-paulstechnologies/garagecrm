{{-- resources/views/admin/leads/edit-partials/_alerts.blade.php --}}

@if(session('success'))
    <div class="sf-alert-success">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="sf-alert-warning">{{ session('warning') }}</div>
@endif

@if(session('error'))
    <div class="sf-alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="sf-alert-danger">
        <div class="mb-2 font-extrabold">Please fix the following:</div>

        <ul class="list-inside list-disc space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

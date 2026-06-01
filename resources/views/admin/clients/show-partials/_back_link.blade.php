{{-- resources/views/admin/clients/show-partials/_back_link.blade.php --}}

<div>
    @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
        <a href="{{ route('admin.clients.index') }}" class="sf-client-show-link">
            ← Back to Clients
        </a>
    @endif
</div>
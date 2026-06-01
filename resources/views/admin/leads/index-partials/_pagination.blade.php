{{-- resources/views/admin/leads/index-partials/_pagination.blade.php --}}

@if($leads->hasPages())
    <div class="sf-leads-panel rounded-2xl border p-4 shadow-sm">
        {{ $leads->links() }}
    </div>
@endif

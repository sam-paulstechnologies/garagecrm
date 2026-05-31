@if(method_exists($opportunities, 'links') && method_exists($opportunities, 'hasPages') && $opportunities->hasPages())
    <div class="sf-opportunity-panel rounded-2xl border p-4 shadow-sm">
        {{ $opportunities->links() }}
    </div>
@endif

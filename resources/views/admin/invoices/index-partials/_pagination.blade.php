{{-- resources/views/admin/invoices/index-partials/_pagination.blade.php --}}

@if($invoices->hasPages())
    <div class="sf-invoice-pagination">
        {{ $invoices->links() }}
    </div>
@endif
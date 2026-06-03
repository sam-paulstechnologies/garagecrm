{{-- resources/views/admin/invoices/index-partials/_empty_state.blade.php --}}

<tr>
    <td colspan="7">
        <div class="sf-empty">
            <div class="text-lg font-extrabold sf-invoice-title">
                No invoices found
            </div>

            <p class="sf-invoice-muted mt-2 text-sm font-medium">
                Invoices will appear here after jobs are closed or invoices are created manually.
            </p>

            <a href="{{ route('admin.invoices.create') }}" class="sf-btn-primary mt-4">
                + Create Invoice
            </a>
        </div>
    </td>
</tr>
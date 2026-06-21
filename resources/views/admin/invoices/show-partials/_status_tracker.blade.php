<div class="sf-card sf-invoice-status-panel">
    <div class="sf-card-body">
        <div class="sf-invoice-status-grid">
            @foreach(['pending', 'overdue', 'paid'] as $stage)
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">
                    @csrf
                    @method('PUT')

                    @foreach($statusFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="{{ $stage }}">

                    <button type="submit"
                            class="sf-invoice-status-button {{ $statusValue === $stage ? 'is-active' : '' }}"
                            title="{{ $statusHelp[$stage] }}">
                        {{ $statusLabels[$stage] }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>

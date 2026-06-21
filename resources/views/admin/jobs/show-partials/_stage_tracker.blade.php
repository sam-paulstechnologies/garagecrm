<div class="sf-card sf-job-stage-panel">
    <div class="sf-card-body">
        <div class="sf-job-stage-grid">
            @foreach(['pending', 'in_progress'] as $stage)
                <form method="POST" action="{{ route('admin.jobs.update', $job) }}">
                    @csrf
                    @method('PUT')

                    @foreach($stageFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="{{ $stage }}">

                    <button type="submit"
                            class="sf-job-stage-button {{ $status === $stage ? 'is-active' : '' }}"
                            title="{{ $stageHelp[$stage] }}">
                        {{ $stageLabels[$stage] }}
                    </button>
                </form>
            @endforeach

            <details class="sf-job-complete-details">
                <summary class="sf-job-stage-button {{ $status === 'completed' ? 'is-active' : '' }}"
                         title="{{ $stageHelp['completed'] }}">
                    Completed
                </summary>

                <form method="POST" action="{{ route('admin.jobs.update', $job) }}" class="sf-job-complete-form">
                    @csrf
                    @method('PUT')

                    @foreach($stageFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="completed">

                    <label for="stage_invoice_number" class="sf-job-mini-label">Invoice Number</label>
                    <input id="stage_invoice_number"
                           name="invoice_number"
                           value="{{ old('invoice_number', $invoiceNumber) }}"
                           class="sf-job-mini-input"
                           required>

                    <label for="stage_invoice_amount" class="sf-job-mini-label mt-3">Invoice Amount</label>
                    <input id="stage_invoice_amount"
                           name="invoice_amount"
                           type="number"
                           min="1"
                           step="0.01"
                           value="{{ old('invoice_amount', $invoiceAmount) }}"
                           class="sf-job-mini-input"
                           required>

                    <button type="submit" class="sf-job-stage-submit">
                        Complete Job
                    </button>
                </form>
            </details>
        </div>
    </div>
</div>

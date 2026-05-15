@php
    $panelId   = 'job-invoices-'.$job->id;
    $modalId   = $panelId.'-upload-modal';
    $openBtnId = $panelId.'-open';
    $closeBtnId= $panelId.'-close';
    $advId     = $panelId.'-adv';
    $invoices  = $job->invoices()->latest('id')->get();
@endphp

<div class="sf-card">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="sf-section-title">
                Invoices
            </h2>

            <p class="sf-section-subtitle">
                Upload and manage invoices linked to this job.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.index') }}?job_id={{ $job->id }}" class="sf-btn-secondary">
                View All
            </a>

            <button id="{{ $openBtnId }}" type="button" class="sf-btn-primary">
                + Upload Invoice
            </button>
        </div>
    </div>

    <div class="sf-card-body">
        @if($invoices->isEmpty())
            <div class="sf-empty">
                <p>No invoices uploaded yet.</p>

                <button id="{{ $openBtnId }}-empty" type="button" class="sf-btn-primary mt-4">
                    Upload Invoice
                </button>
            </div>
        @else
            <div class="sf-table-wrap shadow-none">
                <div class="sf-table-scroll">
                    <table class="sf-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Ver.</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if($inv->is_primary)
                                                <span class="sf-badge-green">
                                                    Primary
                                                </span>
                                            @endif

                                            @if($inv->file_path)
                                                <a href="{{ route('admin.invoices.view', $inv) }}"
                                                   target="_blank"
                                                   class="font-extrabold text-white hover:text-orange-300 hover:underline">
                                                    {{ $inv->number ?? basename($inv->file_path) ?? ('Invoice #'.$inv->id) }}
                                                </a>
                                            @else
                                                <span class="font-extrabold text-white">
                                                    {{ $inv->number ?? ('Invoice #'.$inv->id) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="font-bold text-slate-200">
                                            {{ $inv->invoice_date?->toDateString() ?? '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="font-extrabold text-orange-300">
                                            {{ $inv->amount ? number_format((float)$inv->amount, 2).' '.$inv->currency : '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="sf-badge-slate">
                                            {{ ucfirst($inv->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="sf-badge-blue">
                                            {{ ucfirst($inv->source ?? 'upload') }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="font-bold text-slate-200">
                                            v{{ $inv->version ?? 1 }}
                                        </span>
                                    </td>

                                    <td class="text-right">
                                        <div class="flex justify-end gap-3 whitespace-nowrap">
                                            @if($inv->file_path)
                                                <a class="sf-link" href="{{ route('admin.invoices.download', $inv) }}">
                                                    Download
                                                </a>

                                                <a class="sf-link" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">
                                                    View
                                                </a>
                                            @endif

                                            @if(!$inv->is_primary)
                                                <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                                                    @csrf

                                                    <button class="sf-link">
                                                        Make Primary
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <button id="{{ $openBtnId }}-below" type="button" class="sf-btn-primary">
                    + Upload Another
                </button>
            </div>
        @endif
    </div>
</div>

{{-- Modal --}}
<div id="{{ $modalId }}"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4 backdrop-blur-sm">

    <div class="w-full max-w-2xl rounded-3xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/50">

        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <div>
                <h4 class="text-lg font-extrabold text-white">
                    Upload Invoice
                </h4>

                <p class="mt-1 text-xs font-medium text-slate-500">
                    Upload invoice file and optional invoice metadata.
                </p>
            </div>

            <button type="button"
                    id="{{ $closeBtnId }}"
                    class="flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-red-500/10 hover:text-red-300">
                ✕
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.jobs.invoices.upload', $job) }}"
              enctype="multipart/form-data"
              class="space-y-5 p-6">
            @csrf

            <div>
                <label class="sf-label">
                    Invoice file <span class="text-red-300">*</span>
                </label>

                <input type="file"
                       name="invoice_file"
                       required
                       class="block w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-2 text-sm text-slate-300 shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-orange-600">

                <p class="sf-help">
                    pdf, jpg, jpeg, png, webp • max 5MB
                </p>
            </div>

            <details id="{{ $advId }}" class="rounded-2xl border border-white/10 bg-slate-950/60">
                <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-slate-300">
                    Advanced optional metadata
                </summary>

                <div class="grid grid-cols-1 gap-3 border-t border-white/10 p-4 md:grid-cols-3">
                    <input type="text"
                           name="number"
                           placeholder="Invoice #"
                           class="sf-input">

                    <input type="date"
                           name="invoice_date"
                           class="sf-input">

                    <input type="date"
                           name="due_date"
                           class="sf-input">

                    <input type="text"
                           name="currency"
                           placeholder="Currency (AED)"
                           class="sf-input">

                    <input type="number"
                           step="0.01"
                           name="amount"
                           placeholder="Amount"
                           class="sf-input">
                </div>
            </details>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="{{ $closeBtnId }}-2" class="sf-btn-secondary">
                    Cancel
                </button>

                <button class="sf-btn-primary">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>

{{-- tiny JS for modal (no external deps) --}}
<script>
(function(){
    const openers = [
        document.getElementById('{{ $openBtnId }}'),
        document.getElementById('{{ $openBtnId }}-empty'),
        document.getElementById('{{ $openBtnId }}-below')
    ].filter(Boolean);

    const modal = document.getElementById('{{ $modalId }}');

    const closers = [
        document.getElementById('{{ $closeBtnId }}'),
        document.getElementById('{{ $closeBtnId }}-2')
    ].filter(Boolean);

    openers.forEach(btn => btn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }));

    closers.forEach(btn => btn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }));

    modal?.addEventListener('click', (e) => {
        if(e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
})();
</script>
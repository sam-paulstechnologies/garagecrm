@php
    $panelId   = 'job-invoices-'.$job->id;
    $modalId   = $panelId.'-upload-modal';
    $openBtnId = $panelId.'-open';
    $closeBtnId= $panelId.'-close';
    $advId     = $panelId.'-adv';
    $invoices  = $job->invoices()->latest('id')->get();
@endphp

<div class="rounded border p-4 bg-white">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold">Invoices</h3>
    <div class="flex items-center gap-3">
      <a href="{{ route('admin.invoices.index') }}?job_id={{ $job->id }}" class="text-sm text-blue-600 underline">View all</a>
      <button id="{{ $openBtnId }}" type="button" class="text-sm px-3 py-1.5 bg-gray-900 text-white rounded">+ Upload Invoice</button>
    </div>
  </div>

  @if($invoices->isEmpty())
    <div class="rounded border border-dashed p-6 text-center text-gray-500">
      <p class="mb-3">No invoices uploaded yet.</p>
      <button id="{{ $openBtnId }}-empty" type="button" class="px-3 py-2 bg-gray-900 text-white rounded">Upload Invoice</button>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left">Invoice</th>
            <th class="px-3 py-2 text-left">Date</th>
            <th class="px-3 py-2 text-left">Amount</th>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Source</th>
            <th class="px-3 py-2 text-left">Ver.</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
            <tr class="border-t">
              <td class="px-3 py-2">
                @if($inv->is_primary)
                  <span class="px-1.5 py-0.5 text-[11px] bg-green-100 text-green-700 rounded mr-2 align-middle">Primary</span>
                @endif
                @if($inv->file_path)
                  <a href="{{ route('admin.invoices.view', $inv) }}" target="_blank" class="text-blue-700 underline">
                    {{ $inv->number ?? basename($inv->file_path) ?? ('Invoice #'.$inv->id) }}
                  </a>
                @else
                  <span class="text-gray-800">{{ $inv->number ?? ('Invoice #'.$inv->id) }}</span>
                @endif
              </td>
              <td class="px-3 py-2">{{ $inv->invoice_date?->toDateString() ?? '—' }}</td>
              <td class="px-3 py-2">{{ $inv->amount ? number_format((float)$inv->amount, 2).' '.$inv->currency : '—' }}</td>
              <td class="px-3 py-2">{{ ucfirst($inv->status) }}</td>
              <td class="px-3 py-2">{{ ucfirst($inv->source ?? 'upload') }}</td>
              <td class="px-3 py-2">v{{ $inv->version ?? 1 }}</td>
              <td class="px-3 py-2">
                <div class="flex justify-end gap-3">
                  @if($inv->file_path)
                    <a class="text-blue-600" href="{{ route('admin.invoices.download', $inv) }}">Download</a>
                    <a class="text-blue-600" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">View</a>
                  @endif
                  @if(!$inv->is_primary)
                    <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                      @csrf
                      <button class="text-blue-700">Make Primary</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <button id="{{ $openBtnId }}-below" type="button" class="text-sm px-3 py-1.5 bg-gray-900 text-white rounded">+ Upload Another</button>
    </div>
  @endif
</div>

{{-- Modal --}}
<div id="{{ $modalId }}" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
    <div class="flex items-center justify-between px-5 py-3 border-b">
      <h4 class="font-semibold">Upload Invoice</h4>
      <button type="button" id="{{ $closeBtnId }}" class="text-gray-500 hover:text-gray-700">✕</button>
    </div>

    <form method="POST" action="{{ route('admin.jobs.invoices.upload', $job) }}" enctype="multipart/form-data" class="p-5 space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Invoice file <span class="text-red-600">*</span></label>
        <input type="file" name="invoice_file" required class="block w-full">
        <p class="text-xs text-gray-500 mt-1">pdf, jpg, jpeg, png, webp • max 5MB</p>
      </div>

      <details id="{{ $advId }}" class="rounded border bg-gray-50">
        <summary class="cursor-pointer px-3 py-2 text-sm text-gray-700">Advanced (optional metadata)</summary>
        <div class="p-3 grid grid-cols-2 md:grid-cols-3 gap-2">
          <input type="text"  name="number"       placeholder="Invoice #" class="border p-2 rounded">
          <input type="date"  name="invoice_date" class="border p-2 rounded">
          <input type="date"  name="due_date"     class="border p-2 rounded">
          <input type="text"  name="currency"     placeholder="Currency (AED)" class="border p-2 rounded">
          <input type="number" step="0.01" name="amount" placeholder="Amount" class="border p-2 rounded">
        </div>
      </details>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" id="{{ $closeBtnId }}-2" class="px-3 py-1.5 rounded border">Cancel</button>
        <button class="px-3 py-1.5 bg-gray-900 text-white rounded">Upload</button>
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
    const modal  = document.getElementById('{{ $modalId }}');
    const closers= [document.getElementById('{{ $closeBtnId }}'), document.getElementById('{{ $closeBtnId }}-2')].filter(Boolean);

    openers.forEach(btn => btn.addEventListener('click', () => modal.classList.remove('hidden')));
    closers.forEach(btn => btn.addEventListener('click', () => modal.classList.add('hidden')));
    modal?.addEventListener('click', (e) => { if(e.target === modal) modal.classList.add('hidden'); });
  })();
</script>

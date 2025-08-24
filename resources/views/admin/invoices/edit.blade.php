@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">Edit Invoice #{{ $invoice->id }}</h2>

  @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf @method('PUT')

    <div>
      <label class="block text-sm font-medium">Client *</label>
      <select id="client_id" name="client_id" class="w-full border rounded p-2" required>
        <option value="">-- Select Client --</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}" @selected($invoice->client_id==$c->id)>{{ $c->name }} — {{ $c->phone }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Job (optional)</label>
      <select id="job_id" name="job_id" class="w-full border rounded p-2">
        <option value="">-- Select Job (optional) --</option>
        @foreach($jobs as $j)
          <option value="{{ $j->id }}" @selected($invoice->job_id==$j->id)">
            #{{ $j->id }} • {{ $j->job_code }} • {{ $j->status }}
          </option>
        @endforeach
      </select>
      <p class="text-xs text-gray-500 mt-1">Leave blank to keep invoice standalone.</p>
    </div>

    <div>
      <label class="block text-sm font-medium">Amount *</label>
      <input type="number" name="amount" step="0.01" class="w-full border rounded p-2" value="{{ $invoice->amount }}" required>
    </div>

    <div>
      <label class="block text-sm font-medium">Status *</label>
      <select name="status" class="w-full border rounded p-2" required>
        <option value="pending" @selected($invoice->status==='pending')>Pending</option>
        <option value="paid" @selected($invoice->status==='paid')>Paid</option>
        <option value="overdue" @selected($invoice->status==='overdue')>Overdue</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Due Date</label>
      <input type="date" name="due_date" class="w-full border rounded p-2" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
    </div>

    <div>
      <label class="block text-sm font-medium">Replace Invoice File (optional)</label>
      <input type="file" name="invoice_file" class="w-full border rounded p-2" accept=".pdf,.jpg,.jpeg,.png,.webp">
      @if($invoice->file_path)
        <p class="text-xs mt-1">Current: <a class="underline" href="{{ route('admin.invoices.download',$invoice) }}">Download</a></p>
      @endif
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
  </form>
</div>

<script>
(function(){
  const clientSelect = document.getElementById('client_id');
  const jobSelect = document.getElementById('job_id');
  const urlTemplate = @json(route('admin.ajax.jobs-by-client', ['client' => 'CLIENT_ID']));

  async function loadJobs(clientId, selectedId) {
    jobSelect.innerHTML = '<option value="">Loading…</option>';
    if (!clientId) { jobSelect.innerHTML = '<option value="">-- Select Job (optional) --</option>'; return; }
    try {
      const res = await fetch(urlTemplate.replace('CLIENT_ID', clientId), { headers: {'X-Requested-With': 'XMLHttpRequest'} });
      const jobs = await res.json();
      jobSelect.innerHTML = '<option value="">-- Select Job (optional) --</option>';
      jobs.forEach(j => {
        const opt = new Option(`#${j.id} • ${(j.job_code||'')} • ${j.status}`, j.id);
        if (String(selectedId) === String(j.id)) opt.selected = true;
        jobSelect.add(opt);
      });
    } catch (e) {
      jobSelect.innerHTML = '<option value="">Failed to load jobs</option>';
    }
  }

  clientSelect.addEventListener('change', e => loadJobs(e.target.value, null));
})();
</script>
@endsection

@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
  <h2 class="text-xl font-semibold mb-4">Create Invoice</h2>

  @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.invoices.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium">Client *</label>
      <select id="client_id" name="client_id" class="w-full border rounded p-2" required>
        <option value="">-- Select Client --</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}">{{ $c->name }} — {{ $c->phone }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Job (optional)</label>
      <select id="job_id" name="job_id" class="w-full border rounded p-2">
        <option value="">-- Select Job (optional) --</option>
        {{-- will populate via JS after client pick --}}
      </select>
      <p class="text-xs text-gray-500 mt-1">No jobs? Leave blank to create a standalone invoice.</p>
    </div>

    <div>
      <label class="block text-sm font-medium">Amount *</label>
      <input type="number" name="amount" step="0.01" class="w-full border rounded p-2" required>
    </div>

    <div>
      <label class="block text-sm font-medium">Status *</label>
      <select name="status" class="w-full border rounded p-2" required>
        <option value="pending" selected>Pending</option>
        <option value="paid">Paid</option>
        <option value="overdue">Overdue</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium">Due Date</label>
      <input type="date" name="due_date" class="w-full border rounded p-2">
    </div>

    <div>
      <label class="block text-sm font-medium">Invoice File (PDF/Image)</label>
      <input type="file" name="invoice_file" class="w-full border rounded p-2" accept=".pdf,.jpg,.jpeg,.png,.webp">
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">Create</button>
  </form>
</div>

<script>
(function(){
  const clientSelect = document.getElementById('client_id');
  const jobSelect = document.getElementById('job_id');

  const urlTemplate = @json(route('admin.ajax.jobs-by-client', ['client' => 'CLIENT_ID']));

  async function loadJobs(clientId) {
    jobSelect.innerHTML = '<option value="">Loading…</option>';
    if (!clientId) { jobSelect.innerHTML = '<option value="">-- Select Job (optional) --</option>'; return; }
    try {
      const res = await fetch(urlTemplate.replace('CLIENT_ID', clientId), { headers: {'X-Requested-With': 'XMLHttpRequest'} });
      const jobs = await res.json();
      if (!Array.isArray(jobs) || jobs.length === 0) {
        jobSelect.innerHTML = '<option value="">No jobs found</option>';
        return;
      }
      jobSelect.innerHTML = '<option value="">-- Select Job (optional) --</option>';
      jobs.forEach(j => {
        const label = `#${j.id} • ${(j.job_code||'')} • ${j.status}`;
        jobSelect.add(new Option(label, j.id));
      });
    } catch (e) {
      jobSelect.innerHTML = '<option value="">Failed to load jobs</option>';
    }
  }

  clientSelect.addEventListener('change', e => loadJobs(e.target.value));
})();
</script>
@endsection

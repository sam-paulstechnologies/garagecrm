<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-3">
  @csrf

  <div>
    <label class="block text-sm font-medium mb-1">Invoice file</label>
    <input type="file" name="invoice_file" required class="block w-full">
    <p class="text-xs text-gray-500 mt-1">pdf, jpg, jpeg, png, webp • max 5MB</p>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
    <input type="text"  name="number"       placeholder="Invoice #" class="border p-2 rounded">
    <input type="date"  name="invoice_date" class="border p-2 rounded">
    <input type="date"  name="due_date"     class="border p-2 rounded">
    <input type="text"  name="currency"     placeholder="Currency (AED)" class="border p-2 rounded">
    <input type="number" step="0.01" name="amount" placeholder="Amount" class="border p-2 rounded">
  </div>

  @isset($job)
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_primary" value="1">
      <span>Set as Primary</span>
    </label>
  @endisset

  @isset($client)
    <div class="grid grid-cols-2 gap-2">
      <input type="number" name="job_id" placeholder="Attach to Job ID (optional)" class="border p-2 rounded" value="{{ $jobId ?? '' }}">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_primary" value="1">
        <span>Set as Primary (if Job selected)</span>
      </label>
    </div>
  @endisset

  <button class="px-3 py-2 bg-gray-900 text-white rounded">Upload</button>
</form>

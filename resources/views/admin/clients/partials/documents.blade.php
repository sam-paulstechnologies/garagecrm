<h2 class="text-lg font-semibold mb-2 flex justify-between">
    <span>Documents</span>
</h2>

{{-- Upload --}}
<form method="POST"
      action="{{ route('admin.clients.documents.store', $client) }}"
      enctype="multipart/form-data"
      class="flex items-center gap-2 mb-4">
    @csrf

    <input type="file" name="document" required
           class="border rounded px-3 py-2 text-sm">

    <select name="type" class="border rounded px-3 py-2 text-sm">
        <option value="invoice">Invoice</option>
        <option value="job_card">Job Card</option>
        <option value="insurance">Insurance</option>
        <option value="insurance">Mulkia</option>
        <option value="other">Other</option>
    </select>

    <button class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">
        Upload
    </button>
</form>

{{-- List --}}
@if($client->documents->isEmpty())
    <p class="text-sm text-gray-500">No documents uploaded yet.</p>
@else
    <ul class="space-y-2">
        @foreach($client->documents as $doc)
            <li class="flex justify-between border rounded px-3 py-2 text-sm">
                <span>{{ $doc->document_name }}</span>

                <a href="{{ asset('storage/'.$doc->document_path) }}"
                   target="_blank"
                   class="text-blue-600 underline">
                    View
                </a>
            </li>
        @endforeach
    </ul>
@endif

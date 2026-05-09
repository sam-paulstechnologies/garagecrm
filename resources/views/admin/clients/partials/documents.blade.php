<h2 class="text-lg font-semibold mb-4 flex justify-between">
    <span>Documents</span>
</h2>

{{-- Upload --}}
<form method="POST"
      action="{{ route('admin.clients.documents.store', $client) }}"
      enctype="multipart/form-data"
      class="space-y-3 mb-4">
    @csrf

    <div>
        <input type="file"
               name="document"
               required
               class="block w-full border rounded px-3 py-2 text-sm bg-white">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
        <select name="type"
                class="w-full border rounded px-3 py-2 text-sm bg-white">
            <option value="invoice">Invoice</option>
            <option value="job_card">Job Card</option>
            <option value="insurance">Insurance</option>
            <option value="mulkia">Mulkia</option>
            <option value="other">Other</option>
        </select>

        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm">
            Upload
        </button>
    </div>
</form>

{{-- List --}}
@if($client->documents->isEmpty())
    <p class="text-sm text-gray-500">No documents uploaded yet.</p>
@else
    <ul class="space-y-2">
        @foreach($client->documents as $doc)
            <li class="flex items-center justify-between gap-3 border rounded px-3 py-2 text-sm">
                <div class="min-w-0">
                    <div class="font-medium text-gray-800 truncate">
                        {{ $doc->document_name }}
                    </div>

                    <div class="text-xs text-gray-500">
                        {{ ucfirst(str_replace('_', ' ', $doc->type ?? 'document')) }}
                    </div>
                </div>

                <a href="{{ asset('storage/'.$doc->document_path) }}"
                   target="_blank"
                   class="text-blue-600 underline shrink-0">
                    View
                </a>
            </li>
        @endforeach
    </ul>
@endif
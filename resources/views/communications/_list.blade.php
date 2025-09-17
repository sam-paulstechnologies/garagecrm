<div class="space-y-3">
  @forelse($communications as $row)
    <div class="bg-white p-4 rounded shadow">
      <div class="text-sm text-gray-500">
        {{ optional($row->communication_date)->format('Y-m-d H:i') ?? '—' }} • {{ ucfirst($row->type) }}
        @if($row->follow_up_required)
          <span class="ml-2 inline-block px-2 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800">Follow-up</span>
        @endif
      </div>
      <div class="mt-2">{{ \Illuminate\Support\Str::limit($row->content, 200) }}</div>
      <div class="mt-2">
        <a class="text-blue-600 hover:underline" href="{{ route('communications.show', $row) }}">Open</a>
      </div>
    </div>
  @empty
    <p class="text-gray-500">No communications yet.</p>
  @endforelse

  <div>{{ $communications->links() }}</div>
</div>

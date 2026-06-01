{{-- resources/views/admin/leads/show-partials/_communications.blade.php --}}

<div class="sf-leads-show-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-show-title text-lg font-extrabold tracking-tight">Communications</h2>
    </div>

    <div class="p-5">
        @if($communications->count())
            <div class="overflow-x-auto">
                <table class="sf-leads-show-table min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Content</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($communications as $c)
                            <tr>
                                <td class="px-4 py-3">{{ $c->communication_date ? \Carbon\Carbon::parse($c->communication_date)->format('d M Y, h:i A') : '-' }}</td>
                                <td class="px-4 py-3">{{ $c->communication_type ?? '-' }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($c->content ?? '', 120) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $communications->links() }}</div>
        @else
            <div class="sf-leads-show-soft rounded-2xl border p-8 text-center">
                <div class="sf-leads-show-title font-extrabold">No communications yet.</div>
            </div>
        @endif
    </div>
</div>

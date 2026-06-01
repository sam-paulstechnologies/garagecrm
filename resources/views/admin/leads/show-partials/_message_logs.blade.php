{{-- resources/views/admin/leads/show-partials/_message_logs.blade.php --}}

<div class="sf-leads-show-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-show-title text-lg font-extrabold tracking-tight">Message Logs</h2>
    </div>

    <div class="p-5">
        @if($messageLogs->count())
            <div class="overflow-x-auto">
                <table class="sf-leads-show-table min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Channel</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Direction</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">AI</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($messageLogs as $log)
                            <tr>
                                <td class="px-4 py-3">{{ $log->created_at?->format('d M Y, h:i A') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $log->channel ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $log->direction ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $log->provider_status ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if((bool) ($log->is_ai ?? false))
                                        <span class="{{ $badgeBase }} bg-blue-500/10 text-blue-300 ring-blue-400/20">AI</span>
                                    @else
                                        <span class="sf-leads-show-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($log->message ?? $log->body ?? '', 120) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $messageLogs->links() }}</div>
        @else
            <div class="sf-leads-show-soft rounded-2xl border p-8 text-center">
                <div class="sf-leads-show-title font-extrabold">No message logs yet.</div>
            </div>
        @endif
    </div>
</div>

@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Suppression List', 'subtitle' => 'Opt-outs and blocked numbers are enforced before campaign recipient preparation.'])
    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm"><thead><tr><th class="p-4">Phone</th><th>Reason</th><th>Source</th><th>Opted out</th></tr></thead><tbody>
            @forelse($optOuts as $optOut)
                <tr><td class="p-4">{{ $optOut->normalized_phone }}</td><td>{{ $optOut->reason }}</td><td>{{ $optOut->source }}</td><td>{{ $optOut->opted_out_at?->format('d M Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center sa-muted">No suppressed numbers yet.</td></tr>
            @endforelse
        </tbody></table>
    </div>
@endsection

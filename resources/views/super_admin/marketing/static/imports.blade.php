@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Imports', 'subtitle' => 'CSV/XLSX import audit area. Imports validate phone normalization, duplicates, and suppression before apply.'])
    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm"><thead><tr><th class="p-4">File</th><th>Status</th><th>Total</th><th>Valid</th><th>Duplicates</th><th>Invalid</th></tr></thead><tbody>
            @forelse($imports as $import)
                <tr><td class="p-4">{{ $import->filename }}</td><td>{{ str($import->status)->headline() }}</td><td>{{ $import->total_rows }}</td><td>{{ $import->valid_rows }}</td><td>{{ $import->duplicate_rows }}</td><td>{{ $import->invalid_rows }}</td></tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center sa-muted">No imports recorded yet.</td></tr>
            @endforelse
        </tbody></table>
    </div>
@endsection

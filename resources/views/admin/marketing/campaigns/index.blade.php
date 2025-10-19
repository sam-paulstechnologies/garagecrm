@extends('layouts.app')

@section('title','Campaigns')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Campaigns</h1>
        <a href="{{ route('admin.marketing.campaigns.create') }}"
           class="px-4 py-2 rounded bg-gray-900 text-white hover:bg-black">New Campaign</a>
    </div>

    @if(session('ok'))
        <div class="mb-4 rounded bg-green-50 text-green-700 px-3 py-2">{{ session('ok') }}</div>
    @endif

    <div class="bg-white rounded shadow">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Scheduled</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $c)
                    <tr>
                        <td class="px-4 py-2">{{ $c->name }}</td>
                        <td class="px-4 py-2"><span class="uppercase text-xs">{{ $c->type }}</span></td>
                        <td class="px-4 py-2">{{ ucfirst($c->status) }}</td>
                        <td class="px-4 py-2">{{ $c->scheduled_at }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('admin.marketing.campaigns.edit', $c) }}" class="px-3 py-1 rounded border">Edit</a>
                            @if($c->status !== 'active')
                                <form class="inline" method="POST" action="{{ route('admin.marketing.campaigns.activate', $c) }}">
                                    @csrf
                                    <button class="px-3 py-1 rounded bg-green-600 text-white">Activate</button>
                                </form>
                            @else
                                <form class="inline" method="POST" action="{{ route('admin.marketing.campaigns.pause', $c) }}">
                                    @csrf
                                    <button class="px-3 py-1 rounded bg-yellow-600 text-white">Pause</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection

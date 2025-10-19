@extends('layouts.app')
@section('title','Edit Trigger')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-semibold mb-6">Edit Trigger</h1>

    @if(session('ok')) <div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('ok') }}</div> @endif

    <form method="POST" action="{{ route('admin.marketing.triggers.update',$trigger) }}" class="space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" required value="{{ $trigger->name }}">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Event</label>
            <select name="event" class="w-full border rounded px-3 py-2" required>
                @foreach($events as $k=>$v)
                    <option value="{{ $k }}" @selected($trigger->event === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Conditions (optional)</label>
            <div class="text-xs text-gray-500 mb-1">Edit key–value pairs below.</div>
            <div class="space-y-2" id="cond-wrap">
                @php $conds = $trigger->conditions ?? []; @endphp
                @forelse($conds as $k=>$v)
                    <div class="flex gap-2">
                        <input class="w-1/2 border rounded px-2 py-1" value="{{ $k }}" data-k>
                        <input class="w-1/2 border rounded px-2 py-1" value="{{ $v }}" data-v>
                    </div>
                @empty
                    <div class="flex gap-2">
                        <input class="w-1/2 border rounded px-2 py-1" placeholder="key" data-k>
                        <input class="w-1/2 border rounded px-2 py-1" placeholder="value" data-v>
                    </div>
                @endforelse
            </div>
            <button type="button" class="mt-2 text-indigo-600" onclick="addCond()">+ Add</button>
            <input type="hidden" name="conditions" id="conditions">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Campaign to run</label>
            <select name="campaign_id" class="w-full border rounded px-3 py-2" required>
                @foreach($campaigns as $c)
                    <option value="{{ $c->id }}" @selected($trigger->campaign_id === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                @foreach(['active','paused','archived'] as $s)
                    <option value="{{ $s }}" @selected($trigger->status===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">Save</button>
            <a href="{{ route('admin.marketing.triggers.index') }}" class="border rounded px-4 py-2">Back</a>
        </div>
    </form>
</div>

<script>
function addCond(){
    const wrap = document.getElementById('cond-wrap');
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `<input class="w-1/2 border rounded px-2 py-1" placeholder="key" data-k>
                     <input class="w-1/2 border rounded px-2 py-1" placeholder="value" data-v>`;
    wrap.appendChild(row);
}
document.querySelector('form').addEventListener('submit', function(){
    const rows = document.querySelectorAll('#cond-wrap .flex');
    const obj = {};
    rows.forEach(r=>{
        const k = r.querySelector('[data-k]').value.trim();
        const v = r.querySelector('[data-v]').value.trim();
        if(k) obj[k] = v;
    });
    document.getElementById('conditions').value = JSON.stringify(obj);
});
</script>
@endsection

@extends('layouts.app')
@section('title','New Trigger')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-semibold mb-6">New Trigger</h1>

    <form method="POST" action="{{ route('admin.marketing.triggers.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" required placeholder="e.g., New Leads → Welcome">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Event</label>
            <select name="event" class="w-full border rounded px-3 py-2" required>
                @foreach($events as $k=>$v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Pick the source event that should start this trigger.</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Conditions (optional)</label>
            <div class="text-xs text-gray-500 mb-1">Key–Value pairs (we’ll store as JSON). Leave blank to match all.</div>
            <div class="space-y-2" id="cond-wrap">
                <div class="flex gap-2">
                    <input class="w-1/2 border rounded px-2 py-1" placeholder="key e.g., lead_status" data-k>
                    <input class="w-1/2 border rounded px-2 py-1" placeholder="value e.g., new" data-v>
                </div>
            </div>
            <button type="button" class="mt-2 text-indigo-600" onclick="addCond()">+ Add</button>
            <input type="hidden" name="conditions" id="conditions">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Campaign to run</label>
            <select name="campaign_id" class="w-full border rounded px-3 py-2" required>
                @foreach($campaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">Save</button>
            <a href="{{ route('admin.marketing.triggers.index') }}" class="border rounded px-4 py-2">Cancel</a>
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

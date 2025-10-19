@extends('layouts.app')
@section('title','Edit Campaign')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Edit Campaign</h1>
        <a href="{{ route('admin.marketing.campaigns.index') }}" class="text-indigo-600 text-sm">← Back</a>
    </div>

    @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
            <ul class="list-disc ml-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form id="campaign-edit" method="POST" action="{{ route('admin.marketing.campaigns.update',$campaign) }}" class="space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" required class="w-full border rounded px-3 py-2"
                   value="{{ old('name',$campaign->name) }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Type</label>
                <select name="type" class="w-full border rounded px-3 py-2">
                    <option value="automation" @selected($campaign->type==='automation')>automation</option>
                    <option value="broadcast" @selected($campaign->type==='broadcast')>broadcast</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full border rounded px-3 py-2">
                    @foreach(['draft','active','paused','archived'] as $s)
                        <option value="{{ $s }}" @selected($campaign->status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Schedule at (optional)</label>
            <input type="datetime-local" name="scheduled_at" class="w-full border rounded px-3 py-2"
                   value="{{ old('scheduled_at', optional($campaign->scheduled_at)->format('Y-m-d\TH:i')) }}">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description (internal)</label>
            <textarea name="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description',$campaign->description) }}</textarea>
        </div>

        {{-- Steps (simple: show first step template only) --}}
        <div class="p-3 bg-gray-50 border rounded">
            <div class="font-medium mb-2">Step 1: send_template</div>
            @php $first = $campaign->steps->sortBy('step_order')->first(); @endphp
            <select name="steps[0][template_id]" class="w-full border rounded px-3 py-2">
                <option value="">— Select —</option>
                @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}" @selected(optional($first)->template_id===$tpl->id)>
                        {{ $tpl->name }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="steps[0][action]" value="send_template">
        </div>

        {{-- Audience note (lightweight) --}}
        @php $aud = $campaign->audiences->first(); $q = data_get($aud,'filters.q'); @endphp
        <div>
            <label class="block text-sm font-medium mb-1">Audience (segment/filter)</label>
            <input type="text" id="aud" class="w-full border rounded px-3 py-2" value="{{ old('aud',$q) }}">
            <p class="text-xs text-gray-500 mt-1">Saved into <code>campaign_audiences.filters.q</code>.</p>
        </div>

        <div class="flex items-center gap-3">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">Save</button>
            <a href="{{ route('admin.marketing.campaigns.index') }}" class="border rounded px-4 py-2">Cancel</a>
        </div>
    </form>
</div>

<script>
(function(){
    const form = document.getElementById('campaign-edit');
    const aud  = document.getElementById('aud');

    function clearInjected(){
        [...form.querySelectorAll('[data-injected]')].forEach(n=>n.remove());
    }
    function inject(name, value){
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = name;
        i.value = value;
        i.setAttribute('data-injected','1');
        form.appendChild(i);
    }

    form.addEventListener('submit', function(){
        clearInjected();
        if (aud.value && aud.value.trim().length) {
            inject('audiences[0][filters][q]', aud.value.trim());
        } else {
            inject('audiences', '[]'); // clear audiences if empty
        }
    });
})();
</script>
@endsection

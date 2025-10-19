@extends('layouts.app')
@section('title','New WhatsApp Campaign')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-semibold mb-6">New WhatsApp Campaign</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="campaign-create" method="POST" action="{{ route('admin.marketing.campaigns.store') }}" class="space-y-5">
        @csrf

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium mb-1">Name</label>
            <input
                id="name"
                type="text"
                name="name"
                required
                class="w-full border rounded px-3 py-2"
                placeholder="e.g., Lead Welcome"
                value="{{ old('name') }}"
            >
        </div>

        {{-- Template (creates step[0]=send_template) --}}
        <div>
            <label for="tpl" class="block text-sm font-medium mb-1">Template</label>
            <select id="tpl" class="w-full border rounded px-3 py-2">
                <option value="">— Select later —</option>
                @php
                    $companyId = auth()->user()->company_id ?? 1;
                    $templates = \App\Models\WhatsApp\WhatsAppTemplate::where('company_id',$companyId)
                        ->orderBy('name')->get(['id','name']);
                @endphp
                @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">
                This will create Step 1: <code>send_template</code>.
            </p>
        </div>

        {{-- Audience (optional) --}}
        <div>
            <label for="aud" class="block text-sm font-medium mb-1">Audience (segment/filter)</label>
            <input
                type="text"
                id="aud"
                class="w-full border rounded px-3 py-2"
                placeholder="e.g., All Leads: New"
                value="{{ old('aud') }}"
            >
            <p class="text-xs text-gray-500 mt-1">
                Saved as a lightweight filter note.
            </p>
        </div>

        {{-- Schedule (optional) --}}
        <div>
            <label for="scheduled" class="block text-sm font-medium mb-1">Schedule at (optional)</label>
            <input
                type="datetime-local"
                id="scheduled"
                name="scheduled_at"
                class="w-full border rounded px-3 py-2"
                value="{{ old('scheduled_at') }}"
            >
            <p class="text-xs text-gray-500 mt-1">
                If set, campaign type will be <code>broadcast</code>, otherwise <code>automation</code>.
            </p>
        </div>

        {{-- Hidden fields the controller expects --}}
        <input type="hidden" name="type" value="automation" id="type">
        <input type="hidden" name="status" value="draft">

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">Save</button>
            <a href="{{ route('admin.marketing.campaigns.index') }}" class="border rounded px-4 py-2">Cancel</a>
        </div>
    </form>
</div>

<script>
(function(){
    const form = document.getElementById('campaign-create');
    const tpl  = document.getElementById('tpl');
    const aud  = document.getElementById('aud');
    const sch  = document.getElementById('scheduled');
    const type = document.getElementById('type');

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

    function syncType(){
        type.value = sch.value ? 'broadcast' : 'automation';
    }
    sch.addEventListener('change', syncType);

    form.addEventListener('submit', function(e){
        clearInjected();
        syncType();

        if (tpl.value) {
            inject('steps[0][action]', 'send_template');
            inject('steps[0][template_id]', String(tpl.value));
        }

        if (aud.value && aud.value.trim().length) {
            inject('audiences[0][filters][q]', aud.value.trim());
        }
    });
})();
</script>
@endsection

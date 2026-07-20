@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Create Campaign',
        'subtitle' => 'Draft campaigns first, prepare recipients, then approve before queueing any send jobs.'
    ])

    <form method="POST" action="{{ route('super-admin.marketing.campaigns.store') }}" class="sa-card grid gap-4 rounded-3xl p-6 lg:grid-cols-2">
        @csrf
        <label class="text-sm font-bold">Name<input name="name" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm" required></label>
        <label class="text-sm font-bold">Objective<input name="objective" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Product<input name="product" value="SayaraForce" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Segment
            <select name="segment_id" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">
                <option value="">Select segment</option>
                @foreach($segments as $segment)
                    <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-bold">Approved template name<input name="template_name" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Template language<input name="template_language" value="en_US" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Batch size<input name="batch_size" value="25" type="number" min="1" max="100" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Delay between batches (seconds)<input name="delay_between_batches" value="300" type="number" min="30" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Daily cap<input name="daily_cap" value="100" type="number" min="1" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <label class="text-sm font-bold">Schedule time<input name="scheduled_at" type="datetime-local" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm"></label>
        <div class="sa-soft rounded-2xl p-4 text-sm lg:col-span-2">
            <p class="font-black text-orange-300">Compliance warning</p>
            <p class="sa-muted mt-1">Campaigns only queue prospects with opted-in consent, no opt-out record, no blocked/invalid status, and no duplicate campaign recipient.</p>
        </div>
        <div class="lg:col-span-2"><button class="rounded-2xl bg-emerald-500 px-6 py-3 text-sm font-black text-white">Create Draft</button></div>
    </form>
@endsection

<form method="POST" action="{{ $action }}" class="space-y-6">
@csrf
@if(!empty($isEdit)) @method('PUT') @endif

@if ($errors->any())
<div class="p-4 rounded bg-red-50 text-red-700">
<ul class="list-disc list-inside">
@foreach ($errors->all() as $error)
<li>— {{ $error }}</li>
@endforeach
</ul>
</div>
@endif

@php
$bk = $booking ?? null;

$oldOr = function($k,$d=null) use ($bk) {
return old($k, $bk->$k ?? $d);
};

$statusKey = old('status', $bk->status ?? 'pending');
@endphp


{{-- Opportunity / Client --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>
<label class="block text-sm font-medium text-gray-700">Opportunity</label>
<select id="opportunity_id" name="opportunity_id" class="mt-1 block w-full rounded border-gray-300">

<option value="">— None —</option>

@foreach($opportunities as $o)

<option value="{{ $o->id }}"
data-client-id="{{ $o->client_id }}"
@selected($oldOr('opportunity_id') == $o->id)>

#{{ $o->id }} — {{ $o->title ?? 'Opportunity' }}

</option>

@endforeach

</select>
</div>


<div>
<label class="block text-sm font-medium text-gray-700">Client</label>

<select id="client_id" name="client_id" class="mt-1 block w-full rounded border-gray-300">

<option value="">— Walk-in / New Client —</option>

@foreach($clients as $c)

<option value="{{ $c->id }}"
@selected($oldOr('client_id') == $c->id)>

{{ $c->name }} {{ $c->phone ? '— '.$c->phone : '' }}

</option>

@endforeach

</select>

<p class="text-xs text-gray-500 mt-1">
Leave empty for walk-in; fill the fields below to add a new client.
</p>

</div>

</div>


{{-- Walk-in fields --}}
<div id="new_client_fields" class="grid grid-cols-1 md:grid-cols-3 gap-6 hidden">

<div>
<label class="block text-sm font-medium text-gray-700">New Client Name *</label>
<input type="text" name="new_client_name"
value="{{ old('new_client_name') }}"
class="mt-1 block w-full rounded border-gray-300">
</div>

<div>
<label class="block text-sm font-medium text-gray-700">New Client Phone</label>
<input type="text" name="new_client_phone"
value="{{ old('new_client_phone') }}"
class="mt-1 block w-full rounded border-gray-300">
</div>

<div>
<label class="block text-sm font-medium text-gray-700">New Client Email</label>
<input type="email" name="new_client_email"
value="{{ old('new_client_email') }}"
class="mt-1 block w-full rounded border-gray-300">
</div>

</div>


{{-- Booking Name / Priority --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>
<label class="block text-sm font-medium text-gray-700">Booking Name *</label>

<input type="text"
name="name"
value="{{ $oldOr('name') }}"
class="mt-1 block w-full rounded border-gray-300"
required>
</div>

<div>

<label class="block text-sm font-medium text-gray-700">Priority</label>

@php $prio = $oldOr('priority','medium'); @endphp

<select name="priority" class="mt-1 block w-full rounded border-gray-300">

<option value="low" @selected($prio=='low')>Low</option>
<option value="medium" @selected($prio=='medium')>Medium</option>
<option value="high" @selected($prio=='high')>High</option>

</select>

</div>

</div>


{{-- Date / Slot --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium text-gray-700">Date *</label>

<input type="date"
name="booking_date"
value="{{ $oldOr('booking_date') }}"
class="mt-1 block w-full rounded border-gray-300"
required>

</div>

<div>

<label class="block text-sm font-medium text-gray-700">Slot *</label>

@php $slot = $oldOr('slot','morning'); @endphp

<select name="slot"
class="mt-1 block w-full rounded border-gray-300">

<option value="morning" @selected($slot=='morning')>Morning</option>
<option value="afternoon" @selected($slot=='afternoon')>Afternoon</option>
<option value="evening" @selected($slot=='evening')>Evening</option>
<option value="full_day" @selected($slot=='full_day')>Full Day</option>

</select>

</div>

</div>


{{-- Expected Duration / Close Date --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium text-gray-700">
Expected Duration (in days)
</label>

<input type="number"
name="expected_duration"
value="{{ $oldOr('expected_duration') }}"
min="1"
class="mt-1 block w-full rounded border-gray-300">

</div>


<div>

<label class="block text-sm font-medium text-gray-700">
Expected Close Date
</label>

<input type="date"
name="expected_close_date"
value="{{ $oldOr('expected_close_date') }}"
class="mt-1 block w-full rounded border-gray-300">

</div>

</div>


{{-- Service Type / Status --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium text-gray-700">
Service Type
</label>

<input type="text"
name="service_type"
value="{{ $oldOr('service_type') }}"
class="mt-1 block w-full rounded border-gray-300"
placeholder="Tinting, Detailing, Wheel Alignment">

</div>


<div>

<label class="block text-sm font-medium text-gray-700">
Status
</label>

<select name="status"
class="mt-1 block w-full rounded border-gray-300">

<option value="pending"
@selected($statusKey=='pending')>Pending</option>

<option value="scheduled"
@selected($statusKey=='scheduled')>Scheduled</option>

<option value="vehicle_received"
@selected($statusKey=='vehicle_received')>
Vehicle Received (creates Job)
</option>

<option value="in_progress"
@selected($statusKey=='in_progress')>
In Progress
</option>

<option value="completed"
@selected($statusKey=='completed')>
Completed
</option>

<option value="canceled"
@selected($statusKey=='canceled')>
Canceled
</option>

</select>

</div>

</div>


{{-- Assigned / Vehicle --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium text-gray-700">
Assigned To
</label>

<select name="assigned_to"
class="mt-1 block w-full rounded border-gray-300">

<option value="">— Unassigned —</option>

@foreach($users as $u)

<option value="{{ $u->id }}"
@selected($oldOr('assigned_to') == $u->id)>

{{ $u->name }}

</option>

@endforeach

</select>

</div>


<div>

<label class="block text-sm font-medium text-gray-700">
Vehicle
</label>

<select name="vehicle_id"
class="mt-1 block w-full rounded border-gray-300">

<option value="">— None —</option>

@foreach($vehicles as $v)

<option value="{{ $v->id }}"
@selected($oldOr('vehicle_id') == $v->id)>

#{{ $v->id }}
— {{ $v->make?->name ?? '' }}
{{ $v->model?->name ?? '' }}
{{ $v->plate ? '— '.$v->plate : '' }}

</option>

@endforeach

</select>

</div>

</div>


{{-- Pickup / Notes --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium text-gray-700">
Pickup Required?
</label>

@php $pk = old('pickup_required', $bk->pickup_required ?? false) ? '1' : '0'; @endphp

<select name="pickup_required"
class="mt-1 block w-full rounded border-gray-300">

<option value="0" @selected($pk=='0')>No</option>
<option value="1" @selected($pk=='1')>Yes</option>

</select>


<label class="block mt-4 text-sm font-medium text-gray-700">
Pickup Address
</label>

<input type="text"
name="pickup_address"
value="{{ $oldOr('pickup_address') }}"
class="mt-1 block w-full rounded border-gray-300">


<label class="block mt-4 text-sm font-medium text-gray-700">
Pickup Contact Number
</label>

<input type="text"
name="pickup_contact_number"
value="{{ $oldOr('pickup_contact_number') }}"
class="mt-1 block w-full rounded border-gray-300">

</div>


<div>

<label class="block text-sm font-medium text-gray-700">
Notes
</label>

<textarea name="notes"
rows="10"
class="mt-1 block w-full rounded border-gray-300">

{{ $oldOr('notes') }}

</textarea>

</div>

</div>


<div>

<button type="submit"
class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">

{{ !empty($isEdit) ? 'Update' : 'Create' }} Booking

</button>

</div>

</form>
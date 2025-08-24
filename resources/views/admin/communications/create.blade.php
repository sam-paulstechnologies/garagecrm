@extends('layouts.app')

@section('title', 'Log Communication')

@section('content')
<h1 class="text-2xl font-bold mb-6">Log Communication</h1>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.communications.store') }}" class="bg-white p-6 rounded shadow space-y-6">
    @csrf

    <div>
        <label class="block font-medium text-sm text-gray-700">Client</label>
        <select name="client_id" class="form-input w-full" required>
            <option value="">-- Select Client --</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                    {{ $client->name }} ({{ $client->email }})
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Type</label>
        <select name="type" class="form-input w-full" required>
            <option value="">-- Select Type --</option>
            <option value="Email"     @selected(old('type') == 'Email')>Email</option>
            <option value="Call"      @selected(old('type') == 'Call')>Call</option>
            <option value="WhatsApp"  @selected(old('type') == 'WhatsApp')>WhatsApp</option>
        </select>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Communication Date</label>
        <input type="datetime-local" name="communication_date" value="{{ old('communication_date') }}" class="form-input w-full" required>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Content</label>
        <textarea name="content" class="form-input w-full" rows="4" required>{{ old('content') }}</textarea>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="follow_up_required" class="form-checkbox" @checked(old('follow_up_required'))>
        <label class="ml-2 text-sm text-gray-700">Follow-up Required</label>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</form>
@endsection

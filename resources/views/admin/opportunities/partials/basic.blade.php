@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-xl font-bold mb-6">Create Opportunity</h2>

    <form action="{{ route('admin.opportunities.store') }}" method="POST" id="opportunityForm">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Client -->
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                <div class="flex gap-2">
                    <select id="client_id" name="client_id" class="client-select w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                {{ $client->name }} - {{ $client->phone }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" onclick="openClientModal()" class="bg-blue-600 text-white px-3 rounded hover:bg-blue-700">+</button>
                </div>
            </div>

            <!-- Lead -->
            <div>
                <label for="lead_id" class="block text-sm font-medium text-gray-700">Lead (optional)</label>
                <select name="lead_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- None --</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" @selected(old('lead_id') == $lead->id)>
                            {{ $lead->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Stage -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Stage *</label>
                <select name="stage" required class="w-full border-gray-300 rounded-md shadow-sm">
                    @foreach(['new','attempting_contact','appointment','offer','closed_won','closed_lost'] as $stage)
                        <option value="{{ $stage }}" @selected(old('stage') == $stage)>
                            {{ ucfirst(str_replace('_', ' ', $stage)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Priority</label>
                <select name="priority" class="w-full border-gray-300 rounded-md shadow-sm">
                    @foreach(['low','medium','high'] as $priority)
                        <option value="{{ $priority }}" @selected(old('priority') == $priority)>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Assigned To -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Assigned To (User ID)</label>
                <input type="number" name="assigned_to" value="{{ old('assigned_to') }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Vehicle Make -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Vehicle Make</label>
                <select name="vehicle_make_id" id="vehicle_make_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Make --</option>
                    @foreach($makes as $make)
                        <option value="{{ $make->id }}" @selected(old('vehicle_make_id') == $make->id)>
                            {{ $make->name }}
                        </option>
                    @endforeach
                </select>
                <input type="text" name="vehicle_make_other" placeholder="Other make" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Vehicle Model -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Vehicle Model</label>
                <select name="vehicle_model_id" id="vehicle_model_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Model --</option>
                    @foreach($models as $model)
                        <option value="{{ $model->id }}" @selected(old('vehicle_model_id') == $model->id)>
                            {{ $model->name }}
                        </option>
                    @endforeach
                </select>
                <input type="text" name="vehicle_model_other" placeholder="Other model" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Estimated Value -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Estimated Value (AED)</label>
                <input type="number" name="estimated_value" value="{{ old('estimated_value') }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Expected Duration -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Expected Duration (Days)</label>
                <input type="number" name="expected_duration" value="{{ old('expected_duration') }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Next Follow-Up -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Next Follow-Up</label>
                <input type="date" name="next_follow_up" value="{{ old('next_follow_up') }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>
        </div>

        <!-- Services Opted -->
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Services Opted</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                @foreach(['Oil Change', 'Battery Check', 'Transmission Service', 'Car Wash', 'Polishing', 'Emissions Test', 'AC Repair', 'Detailing', 'Interior Cleaning', 'Registration Renewal', 'Suspension Work', 'Tinting', 'Vehicle Inspection', 'Other'] as $service)
                    <label><input type="checkbox" name="services[]" value="{{ $service }}"> {{ $service }}</label>
                @endforeach
            </div>
        </div>

        <!-- Notes & Close Reason -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Close Reason</label>
            <textarea name="close_reason" rows="2" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('close_reason') }}</textarea>
        </div>

        <!-- Score & Converted -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Opportunity Score</label>
                <input type="number" name="opportunity_score" value="{{ old('opportunity_score') }}" class="w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="flex items-center mt-6">
                <input type="checkbox" name="converted_to_job" value="1" class="mr-2" @checked(old('converted_to_job'))>
                <label>Converted to Job/Booking</label>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                Create Opportunity
            </button>
        </div>
    </form>
</div>

<!-- Add Client Modal -->
<div id="clientModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-xl relative">
        <button onclick="closeClientModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">✕</button>
        <h2 class="text-lg font-semibold mb-4">Add New Client</h2>

        <form id="newClientForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="name" placeholder="Name" required class="border rounded px-3 py-2">
                <input type="text" name="phone" placeholder="Phone" required class="border rounded px-3 py-2">
                <input type="email" name="email" placeholder="Email" class="border rounded px-3 py-2">
                <input type="text" name="whatsapp" placeholder="WhatsApp" class="border rounded px-3 py-2">
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Save Client</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<!-- jQuery + Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(function () {
        $('#client_id').select2({ width: '100%' });

        $('#vehicle_make_id').on('change', function () {
            let makeId = $(this).val();
            $('#vehicle_model_id').html('<option value="">Loading...</option>');
            fetch(`/admin/models/by-make/${makeId}`)
                .then(res => res.json())
                .then(data => {
                    let options = '<option value="">-- Select Model --</option>';
                    data.forEach(model => {
                        options += `<option value="${model.id}">${model.name}</option>`;
                    });
                    $('#vehicle_model_id').html(options);
                });
        });

        $('#newClientForm').on('submit', function (e) {
            e.preventDefault();
            const data = new FormData(this);
            fetch("{{ route('admin.clients.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: data
            })
            .then(res => res.json())
            .then(response => {
                if (response.id) {
                    const select = $('#client_id');
                    const newOption = new Option(response.name + ' - ' + response.phone, response.id, true, true);
                    select.append(newOption).trigger('change.select2');
                    closeClientModal();
                    this.reset();
                } else {
                    alert('Client creation failed.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong.');
            });
        });
    });

    function openClientModal() {
        $('#clientModal').removeClass('hidden');
    }

    function closeClientModal() {
        $('#clientModal').addClass('hidden');
    }
</script>
@endpush

@endsection

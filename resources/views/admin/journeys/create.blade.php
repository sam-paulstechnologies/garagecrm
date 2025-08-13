@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white shadow rounded-lg">
    <h1 class="text-2xl font-bold mb-6">Create New Journey</h1>

    <form action="{{ route('admin.journeys.store') }}" method="POST">
        @csrf

        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block font-medium">Name</label>
            <input type="text" name="name" id="name" required
                class="w-full mt-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label for="description" class="block font-medium">Description</label>
            <textarea name="description" id="description"
                class="w-full mt-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Triggers -->
        <div class="mb-4">
            <label for="triggers" class="block font-medium">Triggers (JSON format)</label>
            <textarea name="triggers" id="triggers" placeholder='e.g. ["birthday", "followup"]'
                class="w-full mt-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Rules -->
        <div class="mb-4">
            <label for="rules" class="block font-medium">Rules (JSON format)</label>
            <textarea name="rules" id="rules" placeholder='e.g. {"day_gap": 2, "channel": "email"}'
                class="w-full mt-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Journey Builder Section -->
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Journey Steps</h2>
            <div id="journey-steps" class="space-y-3"></div>

            <div class="flex space-x-2 mt-4">
                <button type="button" onclick="addStep()"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">+ Add Step</button>
                <button type="button" onclick="addCondition()"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">+ Add Condition</button>
            </div>
        </div>

        <!-- Submit -->
        <div class="mt-6">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded">Create Journey</button>
        </div>
    </form>
</div>

<script>
    function addStep() {
        const steps = document.getElementById('journey-steps');
        const step = document.createElement('div');
        step.className = 'p-4 bg-gray-100 border border-gray-300 rounded';
        step.innerHTML = `
            <label class="block mb-1 font-medium">Step Action</label>
            <input type="text" name="steps[]" placeholder="e.g. Send Email"
                class="w-full border px-3 py-2 rounded">
        `;
        steps.appendChild(step);
    }

    function addCondition() {
        const steps = document.getElementById('journey-steps');
        const condition = document.createElement('div');
        condition.className = 'p-4 bg-yellow-100 border border-yellow-300 rounded';
        condition.innerHTML = `
            <label class="block mb-1 font-medium">Condition</label>
            <input type="text" name="conditions[]" placeholder="e.g. Wait 2 days"
                class="w-full border px-3 py-2 rounded">
        `;
        steps.appendChild(condition);
    }
</script>
@endsection

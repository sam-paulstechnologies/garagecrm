@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Automation Rules</h1>
    <div class="mb-5 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-950">
        <strong>Active custom workflows:</strong>
        {{ $workflowUsage['used'] }} / {{ $workflowUsage['limit'] ?? 'Fair use' }}
    </div>
    <table class="min-w-full bg-white shadow-md rounded">
        <thead>
            <tr>
                <th class="py-2 px-4 border-b">Rule Name</th>
                <th class="py-2 px-4 border-b">Description</th>
                <th class="py-2 px-4 border-b">Status</th>
                <th class="py-2 px-4 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($automationRules as $rule)
            <tr>
                <td class="py-2 px-4 border-b">{{ $rule->name }}</td>
                <td class="py-2 px-4 border-b">{{ $rule->description }}</td>
                <td class="py-2 px-4 border-b">
                    <form action="{{ route('automation_rules.toggle', $rule->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="focus:outline-none">
                            <span class="{{ $rule->active ? 'text-green-500' : 'text-red-500' }}">
                                {{ $rule->active ? 'Active' : 'Inactive' }}
                            </span>
                        </button>
                    </form>
                </td>
                <td class="py-2 px-4 border-b">
                    <a href="{{ route('automation_rules.edit', $rule->id) }}" class="text-blue-500 hover:underline">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

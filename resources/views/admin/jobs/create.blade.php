@extends('layouts.app')

@section('title', 'Create Job')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Job Management
            </div>

            <h1 class="sf-page-title mt-3">
                Create Job
            </h1>

            <p class="sf-page-subtitle">
                Capture only what you need now; you can update later.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Back to Jobs
            </a>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.jobs.store') }}" class="space-y-6">
        @csrf

        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Job Information
                </h2>

                <p class="sf-section-subtitle">
                    Add client, owner, timing, description, issues, parts, and current job status.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- Client --}}
                    <div>
                        <label class="sf-label">
                            Client <span class="text-red-300">*</span>
                        </label>

                        <select name="client_id" class="sf-select" required>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('client_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Assign To --}}
                    <div>
                        <label class="sf-label">
                            Assign To
                        </label>

                        <select name="assigned_to" class="sf-select">
                            <option value="">Unassigned</option>

                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('assigned_to')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Start Time --}}
                    <div>
                        <label class="sf-label">
                            Start Time
                        </label>

                        <input type="datetime-local"
                               name="start_time"
                               class="sf-input">

                        @error('start_time')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- End Time --}}
                    <div>
                        <label class="sf-label">
                            End Time
                        </label>

                        <input type="datetime-local"
                               name="end_time"
                               class="sf-input">

                        @error('end_time')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="sf-label">
                            Description <span class="text-red-300">*</span>
                        </label>

                        <textarea name="description"
                                  class="sf-textarea"
                                  rows="3"
                                  required>{{ old('description') }}</textarea>

                        @error('description')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Issues Found --}}
                    <div>
                        <label class="sf-label">
                            Issues Found
                        </label>

                        <textarea name="issues_found"
                                  class="sf-textarea"
                                  rows="3">{{ old('issues_found') }}</textarea>

                        @error('issues_found')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Parts Used --}}
                    <div>
                        <label class="sf-label">
                            Parts Used
                        </label>

                        <textarea name="parts_used"
                                  class="sf-textarea"
                                  rows="3">{{ old('parts_used') }}</textarea>

                        @error('parts_used')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="sf-label">
                            Status
                        </label>

                        <select name="status" class="sf-select">
                            <option value="pending" @selected(old('status') === 'pending')>
                                Pending
                            </option>

                            <option value="in_progress" @selected(old('status') === 'in_progress')>
                                In Progress
                            </option>

                            <option value="completed" @selected(old('status') === 'completed')>
                                Completed
                            </option>
                        </select>

                        @error('status')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Total Time --}}
                    <div>
                        <label class="sf-label">
                            Total Time (minutes)
                        </label>

                        <input type="number"
                               min="0"
                               name="total_time_minutes"
                               value="{{ old('total_time_minutes') }}"
                               class="sf-input">

                        @error('total_time_minutes')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Info Note --}}
        <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
            <div class="font-extrabold text-blue-300">
                Job workflow note
            </div>

            <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                You can create a job with minimum details now and update work summary, invoice details, and completion status later.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="sf-btn-primary">
                Create Job
            </button>

            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Cancel
            </a>
        </div>
    </form>

</div>
@endsection
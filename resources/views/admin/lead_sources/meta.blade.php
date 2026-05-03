@extends('layouts.app')

@section('content')
<div class="px-6 py-6 space-y-6 max-w-3xl mx-auto">

    <div>
        <h1 class="text-2xl font-semibold">
            Meta (Facebook / Instagram Lead Forms)
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Connect your Facebook Page and sync lead forms
        </p>
    </div>

    <div class="bg-white border rounded-lg p-6 space-y-4">

        @if(!$meta)
            <p class="text-gray-600">
                No Facebook Page connected yet.
            </p>

            {{-- ✅ FIXED: correct route name --}}
            <a href="{{ route('admin.lead-sources.meta.connect') }}"
               class="inline-block px-4 py-2 bg-indigo-600 text-white rounded">
                Connect Facebook
            </a>

        @else
            <div>
                <p>
                    <strong>Connected Page:</strong> {{ $meta->page_name }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    Lead forms are synced from this page.
                </p>
            </div>

            <div class="flex gap-2 mt-4">

                {{-- ✅ FIXED: refresh route --}}
                <form method="POST" action="{{ route('admin.lead-sources.meta.refresh') }}">
                    @csrf
                    <button class="px-4 py-2 bg-gray-800 text-white rounded">
                        Refresh Forms
                    </button>
                </form>

                {{-- ✅ FIXED: disconnect route --}}
                <form method="POST" action="{{ route('admin.lead-sources.meta.disconnect') }}">
                    @csrf
                    <button class="px-4 py-2 border rounded text-red-600">
                        Disconnect
                    </button>
                </form>

            </div>
        @endif

    </div>

    <a href="{{ route('admin.lead-sources.index') }}"
       class="text-sm text-gray-500 underline">
        ← Back to Lead Sources
    </a>

</div>
@endsection

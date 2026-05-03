<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Garage CRM — Admin</title>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="bg-gray-100 min-h-screen">

    {{-- ========================================= --}}
    {{-- Top Bar --}}
    {{-- ========================================= --}}
    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-gray-900 font-semibold">
                Garage CRM
            </a>

            <nav class="flex items-center gap-4 text-sm">
                @php($hasDocs = \Illuminate\Support\Facades\Route::has('admin.documents.index'))

                @if($hasDocs)
                    <a href="{{ route('admin.documents.index') }}"
                       class="text-gray-700 hover:text-gray-900 {{ request()->routeIs('admin.documents.*') ? 'font-semibold' : '' }}">
                        Documents
                    </a>
                @endif

                @auth
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600">{{ auth()->user()->name }}</span>
                @endauth
            </nav>
        </div>
    </header>


    {{-- ========================================= --}}
    {{-- Main Content --}}
    {{-- ========================================= --}}
    <main class="py-6">
        @yield('content')
    </main>


    {{-- ========================================= --}}
    {{-- Alpine (Required for WhatsApp popup) --}}
    {{-- ========================================= --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>


    {{-- ========================================= --}}
    {{-- WhatsApp Floating Popup --}}
    {{-- ========================================= --}}
    @auth
        @include('admin.partials.whatsapp-popup')
    @endauth

</body>
</html>
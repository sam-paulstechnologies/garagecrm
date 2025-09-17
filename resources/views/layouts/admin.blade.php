<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage CRM — Admin</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-100">

    {{-- Minimal top bar with quick links (safe even if your full nav is disabled) --}}
    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-gray-900 font-semibold">Garage CRM</a>
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

    {{-- ✅ TEMP: Remove sidebar/nav while testing --}}
    {{-- @include('admin.navigation') --}}

    {{-- ✅ Page content --}}
    <main class="py-6">
        @yield('content')
    </main>

</body>
</html>

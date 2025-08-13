<!-- partials/sidebar.blade.php -->
<aside class="w-64 bg-white shadow-md hidden lg:block">
    <nav class="p-4 space-y-2">
        <a href="{{ route('admin.templates.index') }}" class="block text-sm font-medium hover:text-blue-600">Templates</a>
        <a href="{{ route('admin.journeys.index') }}" class="block text-sm font-medium hover:text-blue-600">Journeys</a>
    </nav>
</aside>
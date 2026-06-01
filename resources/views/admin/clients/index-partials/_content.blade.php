{{-- resources/views/admin/clients/index-partials/_content.blade.php --}}

@php
    $q = $q ?? request('q', '');
@endphp

{{-- Search / Filters --}}
@include('admin.clients.index-partials._filters')

{{-- Alphabet Jump --}}
@include('admin.clients.index-partials._alphabet_nav')

{{-- Client Cards --}}
@include('admin.clients.index-partials._client_grid')
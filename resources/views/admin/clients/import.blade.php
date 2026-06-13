{{-- resources/views/admin/clients/import.blade.php --}}

@extends('layouts.app')

@section('title', 'Import Clients')

@section('content')
    @include('admin.clients.import-partials._styles')

    <div class="sf-page sf-client-import-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">

        {{-- Alerts --}}
        @include('admin.clients.import-partials._alerts')

        {{-- Hero --}}
        @include('admin.clients.import-partials._hero')

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- Upload Form --}}
            @include('admin.clients.import-partials._upload_form')

            {{-- Side Notes --}}
            @include('admin.clients.import-partials._side_notes')

        </div>

        {{-- Import Format --}}
        @include('admin.clients.import-partials._format_table')

        {{-- Recent Batches --}}
        @include('admin.clients.import-partials._recent_batches')

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-client-import-collapsible]').forEach(function (root) {
                var body = root.querySelector('[data-client-import-collapsible-body]');
                var toggle = root.querySelector('[data-client-import-collapsible-toggle]');
                var storageKey = root.getAttribute('data-storage-key');
                var defaultCollapsed = root.getAttribute('data-default-collapsed') !== 'false';
                var stored = storageKey ? window.localStorage.getItem(storageKey) : null;
                var collapsed = stored === null ? defaultCollapsed : stored === 'true';

                if (!body || !toggle) {
                    return;
                }

                function labels() {
                    var text = toggle.textContent.trim().toLowerCase();

                    if (text.indexOf('format') !== -1) {
                        return ['Show format guide', 'Hide format guide'];
                    }

                    return ['Show rules', 'Hide rules'];
                }

                function applyState() {
                    var labelSet = labels();
                    body.classList.toggle('hidden', collapsed);
                    toggle.textContent = collapsed ? labelSet[0] : labelSet[1];
                    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

                    if (storageKey) {
                        window.localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
                    }
                }

                toggle.addEventListener('click', function () {
                    collapsed = !collapsed;
                    applyState();
                });

                applyState();
            });
        });
    </script>
@endsection

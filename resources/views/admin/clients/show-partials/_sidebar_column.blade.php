{{-- resources/views/admin/clients/show-partials/_sidebar_column.blade.php --}}

<aside class="space-y-6 lg:col-span-4">

    @include('admin.clients.show-partials.sections._details_section')

    @include('admin.clients.show-partials.sections._documents_section')

    @include('admin.clients.show-partials.sections._invoices_section')

    @include('admin.clients.show-partials.sections._notes_section')

    @include('admin.clients.show-partials.sections._activity_section')

</aside>
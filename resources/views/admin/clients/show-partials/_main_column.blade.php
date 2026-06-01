{{-- resources/views/admin/clients/show-partials/_main_column.blade.php --}}

<div class="space-y-6 lg:col-span-8">

    @include('admin.clients.show-partials.sections._vehicles_section')

    @include('admin.clients.show-partials.sections._service_history_section')

    @include('admin.clients.show-partials.sections._leads_section')

    @include('admin.clients.show-partials.sections._opportunities_section')

    @include('admin.clients.show-partials.sections._bookings_section')

    @include('admin.clients.show-partials.sections._communications_section')

</div>
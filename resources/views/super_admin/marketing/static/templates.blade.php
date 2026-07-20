@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Approved Templates', 'subtitle' => 'Campaigns must use approved Meta WhatsApp templates. Template sync metadata lives on the platform channel.'])
    <div class="sa-card rounded-3xl p-8 text-center sa-muted">Template sync UI is ready for Meta credentials. No raw token data is rendered here.</div>
@endsection

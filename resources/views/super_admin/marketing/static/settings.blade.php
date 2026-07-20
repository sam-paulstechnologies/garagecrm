@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Marketing Settings', 'subtitle' => 'Configure demo hours, follow-up limits, product knowledge, and safety controls without touching tenant settings.'])
    <div class="sa-card rounded-3xl p-8 text-center sa-muted">Settings storage is isolated in `platform_marketing_settings`.</div>
@endsection

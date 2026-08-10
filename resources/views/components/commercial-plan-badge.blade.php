@props(['company' => null])
@php($identity = $company
    ? app(\App\Commercial\EntitlementService::class)->planIdentity($company)
    : ['code' => null, 'label' => 'UNASSIGNED', 'state' => 'unsubscribed'])
<span {{ $attributes->merge([
    'data-commercial-plan-badge' => '',
    'data-commercial-plan-code' => $identity['code'] ?? 'unassigned',
    'data-commercial-subscription-state' => $identity['state'],
]) }}>{{ $identity['label'] }}</span>

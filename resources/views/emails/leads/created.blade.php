@php($lead = $lead ?? $lead ?? null)
<h2>Hi {{ $lead->name }},</h2>
<p>Thanks for contacting {{ config('app.name') }}. Your lead ID is <strong>#{{ $lead->id }}</strong>.</p>
<p>We’ll reach out shortly. Reply to this email or WhatsApp for quicker updates.</p>

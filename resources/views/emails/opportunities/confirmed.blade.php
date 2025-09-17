<h2>Booking Confirmed</h2>
<p>Hi {{ $opp->client->name }}, your booking/opportunity #{{ $opp->id }} is confirmed.</p>
@if($opp->expected_meeting_at)
<p><strong>When:</strong> {{ \Carbon\Carbon::parse($opp->expected_meeting_at)->format('D, d M Y H:i') }}</p>
@endif
<p>We look forward to serving you!</p>

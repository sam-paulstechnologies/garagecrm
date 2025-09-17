<h2>Service Completed</h2>
<p>Hi {{ $job->client->name }}, your service (Job #{{ $job->id }}) is complete.</p>
@if($job->invoice_id)
<p>Invoice: <a href="{{ route('invoices.show',$job->invoice_id) }}">View/Pay</a></p>
@endif
<p>Thank you for choosing {{ config('app.name') }}.</p>

@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-4">
  <h2 class="text-2xl font-semibold">Custom Website Lead Form</h2>

  <p>Embed this snippet on your website:</p>

  <pre class="bg-gray-900 text-white p-4 rounded text-sm">
&lt;script src="https://sayaraforce.com/embed/lead-form.js"&gt;&lt;/script&gt;
&lt;div data-sayaraforce-form="{{ auth()->user()->company_id }}"&gt;&lt;/div&gt;
  </pre>
</div>
@endsection

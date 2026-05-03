@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
  <h2 class="text-2xl font-semibold">Lead Import Options</h2>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="{{ route('admin.leads.import.meta.form') }}" class="card">
      Meta (Facebook / Instagram)
    </a>

    <a href="{{ route('admin.whatsapp.settings.edit') }}" class="card">
      WhatsApp Number
    </a>

    <div class="card opacity-60">
      Google (Coming Soon)
    </div>

    <div class="card opacity-60">
      Snapchat (Coming Soon)
    </div>
  </div>
</div>
@endsection

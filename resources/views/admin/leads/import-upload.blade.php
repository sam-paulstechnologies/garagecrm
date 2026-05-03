@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-6 space-y-4">
  <h2 class="text-xl font-semibold">Upload Leads (Excel / CSV)</h2>

  <form method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" required class="border p-2 w-full">
    <button class="btn btn-primary mt-4">Upload</button>
  </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Template</h2>
    <form method="POST" action="{{ route('admin.templates.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Type</label>
            <select name="type" class="form-control" required>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Content</label>
            <textarea name="content" class="form-control" rows="6" required></textarea>
        </div>

        <button class="btn btn-success">Save Template</button>
    </form>
</div>
@endsection

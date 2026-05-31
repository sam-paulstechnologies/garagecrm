@if(session('success'))
    <div class="sf-alert-success">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="sf-alert-warning">{{ session('warning') }}</div>
@endif

@if(session('error'))
    <div class="sf-alert-danger">{{ session('error') }}</div>
@endif

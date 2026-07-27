@if (session('status'))
    <div class="dz-flash-success">{{ session('status') }}</div>
@endif
@if (session('status_warning'))
    <div class="dz-flash-warning">{{ session('status_warning') }}</div>
@endif

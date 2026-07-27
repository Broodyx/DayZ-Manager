@if (auth()->check())
    <div class="dz-sidebar-user">
        <x-filament-panels::user-menu />
        <span class="dz-sidebar-user-name">{{ filament()->getUserName(auth()->user()) }}</span>
    </div>
@endif

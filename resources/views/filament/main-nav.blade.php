@php
    // The active project persists in session (App\Support\ActiveProject) instead of only
    // living in the current page's own ?project= query string — a project picked once (via
    // the switcher below, or any page's own project select) stays active across every page,
    // including ones that never pass ?project= themselves (FTP prohlížeč, Log analyzátor,
    // Dashboard…). A project route-parameter (e.g. /admin/projects/{record}/edit) counts as
    // an explicit request too, same priority as an explicit ?project=.
    $routeRecord = request()->route('record');
    $routeProjectId = $routeRecord instanceof \App\Models\Project ? $routeRecord->id : (is_numeric($routeRecord) ? (int) $routeRecord : null);
    $explicitProjectId = request()->integer('project') ?: $routeProjectId;
    $isAdmin = (bool) auth()->user()?->is_admin;
    $projectOptions = \App\Models\Project::query()
        ->when(! $isAdmin, fn ($query) => $query->where('user_id', auth()->id()))
        ->orderBy('name')
        ->pluck('name', 'id');
    $projectId = \App\Support\ActiveProject::resolve($explicitProjectId ?: null, $projectOptions->keys()->all());
    $project = $projectId
        ? \App\Models\Project::query()
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', auth()->id()))
            ->withCount(['imports', 'revisions'])
            ->find($projectId)
        : null;
@endphp
<div class="dz-main-nav">
    <a href="{{ url('/admin') }}" class="dz-brand" aria-label="DayZ Manager · Dashboard">
        <x-filament-panels::logo />
    </a>
    @if ($project)
        <div class="dz-workspace-identity">
            <span class="dz-server-pulse"></span>
            <span>
                <small>AKTIVNÍ SERVER</small>
                <strong>{{ $project->name }}</strong>
            </span>
            <span class="dz-workspace-platform">{{ strtoupper($project->platform === 'steam' ? 'PC / STEAM' : $project->platform) }}</span>
        </div>
        <nav aria-label="Navigace aktivního serveru">
            <a href="{{ url('/admin/map-editor?project='.$project->id) }}" @class(['active' => request()->routeIs('filament.admin.pages.map-editor')])>
                <x-filament::icon icon="heroicon-o-map" /> Mapa
            </a>
            <a href="{{ url('/admin/projects/'.$project->id.'/configuration') }}" @class(['active' => request()->routeIs('filament.admin.resources.projects.configuration')])>
                <x-filament::icon icon="heroicon-o-adjustments-horizontal" /> Soubory a editory
            </a>
            <a href="{{ url('/admin/configuration-wizard?project='.$project->id) }}" @class(['active' => request()->routeIs('filament.admin.pages.configuration-wizard')])>
                <x-filament::icon icon="heroicon-o-list-bullet" /> Checklist
            </a>
            <a href="{{ url('/admin/configuration-import?project='.$project->id) }}" @class(['active' => request()->routeIs('filament.admin.pages.configuration-import')])>
                <x-filament::icon icon="heroicon-o-arrow-up-tray" /> Přidat soubor
            </a>
            <a href="{{ url('/admin/projects/'.$project->id.'/edit') }}" @class(['active' => request()->routeIs('filament.admin.resources.projects.edit')])>
                <x-filament::icon icon="heroicon-o-cog-6-tooth" /> Nastavení
            </a>
        </nav>
        <div class="dz-workspace-meta">
            <span>{{ $project->imports_count }} souborů</span>
            <span>{{ $project->revisions_count }} revizí</span>
        </div>
    @else
        <div class="dz-workspace-identity dz-workspace-identity-global">
            <span class="dz-server-pulse"></span>
            <span>
                <small>DAYZ MANAGER</small>
                <strong>Přehled</strong>
            </span>
        </div>
        <nav aria-label="Hlavní navigace">
            <a href="{{ url('/admin') }}" @class(['active' => request()->routeIs('filament.admin.pages.dashboard')])>
                <x-filament::icon icon="heroicon-o-home" /> Dashboard
            </a>
            <a href="{{ url('/admin/projects') }}" @class(['active' => request()->routeIs('filament.admin.resources.projects.*')])>
                <x-filament::icon icon="heroicon-o-server-stack" /> Servery
            </a>
        </nav>
    @endif
    <details class="dz-nav-tools">
        <summary><x-filament::icon icon="heroicon-o-ellipsis-horizontal-circle" /> Nástroje</summary>
        <div class="dz-nav-tools-menu">
            <a href="{{ \App\Filament\Pages\LogAnalyzer::getUrl(array_filter(['project' => $project?->id])) }}">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" /> Log analyzátor
            </a>
            <a href="{{ \App\Filament\Pages\FtpExplorer::getUrl(array_filter(['project' => $project?->id])) }}">
                <x-filament::icon icon="heroicon-o-folder-open" /> FTP prohlížeč
            </a>
            <a href="{{ \App\Filament\Resources\ConfigurationRevisionResource::getUrl() }}">
                <x-filament::icon icon="heroicon-o-clock" /> Historie revizí
            </a>
            <a href="{{ \App\Filament\Resources\ConfigurationImportResource::getUrl() }}">
                <x-filament::icon icon="heroicon-o-arrow-up-tray" /> Historie importů
            </a>
            @if ($isAdmin)
                <a href="{{ \App\Filament\Resources\UserResource::getUrl() }}">
                    <x-filament::icon icon="heroicon-o-users" /> Uživatelé
                </a>
            @endif
        </div>
    </details>
    @if ($projectOptions->count())
        <select class="dz-map-select dz-nav-server-switch" aria-label="Aktivní server" onchange="
            const u = new URL(window.location.href);
            u.searchParams.set('project', this.value);
            window.location.href = u.toString();
        ">
            @foreach ($projectOptions as $id => $name)
                <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
            @endforeach
        </select>
    @endif
    <div class="dz-nav-user">
        <x-filament-panels::user-menu />
    </div>
</div>

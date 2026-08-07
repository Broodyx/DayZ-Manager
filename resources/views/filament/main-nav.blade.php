@php
    $routeRecord = request()->route('record');
    $routeProjectId = $routeRecord instanceof \App\Models\Project ? $routeRecord->id : (is_numeric($routeRecord) ? (int) $routeRecord : null);
    $projectId = request()->integer('project') ?: $routeProjectId;
    $showWorkspace = $projectId && (
        request()->routeIs('filament.admin.resources.projects.configuration')
        || request()->routeIs('filament.admin.resources.projects.edit')
        || request()->routeIs('filament.admin.pages.map-editor')
        || request()->routeIs('filament.admin.pages.configuration-import')
        || request()->routeIs('filament.admin.pages.configuration-wizard')
    );
    $project = $showWorkspace
        ? \App\Models\Project::query()
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->withCount(['imports', 'revisions'])
            ->find($projectId)
        : null;
    $isAdmin = (bool) auth()->user()?->is_admin;
@endphp
<div class="dz-main-nav">
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
</div>

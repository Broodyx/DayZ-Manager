@php
    $routeRecord = request()->route('record');
    $routeProjectId = $routeRecord instanceof \App\Models\Project ? $routeRecord->id : (is_numeric($routeRecord) ? (int) $routeRecord : null);
    $projectId = request()->integer('project') ?: $routeProjectId;
    $showWorkspace = $projectId && (
        request()->routeIs('filament.admin.resources.projects.configuration')
        || request()->routeIs('filament.admin.resources.projects.edit')
        || request()->routeIs('filament.admin.pages.map-editor')
        || request()->routeIs('filament.admin.pages.configuration-import')
    );
    $project = $showWorkspace
        ? \App\Models\Project::query()
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->withCount(['imports', 'revisions'])
            ->find($projectId)
        : null;
@endphp
@if ($project)
    <div class="dz-workspace-nav">
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
            <a href="{{ url('/admin/configuration-wizard?project='.$project->id) }}">
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
    </div>
@endif

<x-filament-widgets::widget>
    <section class="dz-command-center">
        <div class="dz-server-selector" x-data x-init="if (!$wire.selectedProjectId && localStorage.dzSelectedProject) $wire.selectedProjectId = Number(localStorage.dzSelectedProject)">
            @php($selectedProject = $projects->firstWhere('id', $selectedProjectId))
            <label><strong>Aktivní DayZ server</strong>
                <select wire:model.live="selectedProjectId" x-on:change="localStorage.dzSelectedProject = $event.target.value" wire:change="refreshStatus($event.target.value)" wire:poll.45s="refreshStatus({{ $selectedProjectId ?: ($activeProject?->id ?? 0) }})">
                    <option value="">Vyber server…</option>
                    @foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }} · {{ ucfirst($project->platform) }}</option>@endforeach
                </select>
            </label>
            @if ($selectedProjectId && $serverStatus)
                <div class="dz-server-status"><strong class="{{ ($serverStatus['online'] ?? false) ? 'online' : 'offline' }}">{{ ($serverStatus['online'] ?? false) ? 'ONLINE' : 'OFFLINE' }}</strong>
                    @if ($serverStatus['online'] ?? false)<span>{{ $serverStatus['name'] ?? '—' }} · {{ $serverStatus['players'] }}/{{ $serverStatus['max_players'] }} hráčů · {{ $serverStatus['map'] ?? '—' }} · {{ $serverStatus['ping_ms'] ?? '—' }} ms</span>@else<span>{{ $serverStatus['error'] ?? 'Stav není dostupný.' }}</span>@endif
                </div>
            @endif
            @if ($selectedProject)
                <small>{{ $selectedProject->query_host ?: 'Query IP není nastavená' }}:{{ $selectedProject->game_port ?: '—' }} · Query {{ $selectedProject->query_port ?: '—' }} · RCON {{ $selectedProject->rcon_port ?: '—' }}</small>
            @endif
            @if ($selectedProjectId)<button type="button" wire:click="refreshStatus({{ $selectedProjectId }})">Obnovit stav</button>@endif
        </div>
        <div class="dz-command-copy">
            <p class="dz-kicker">DAYZ MANAGER</p>
            <h2>{{ $projectsCount ? 'Pokračujte tam, kde jste skončili' : 'Připravte svůj první DayZ server' }}</h2>
            <p>{{ $projectsCount ? 'Všechny změny se ukládají jako nové revize. Vyberte server, doplňte chybějící soubory a upravujte je bez ručního hledání XML parametrů.' : 'Založte server, vyberte platformu a mapu. Průvodce vám potom ukáže přesně ty konfigurační soubory, které můžete spravovat.' }}</p>
            <div class="dz-command-actions">
                @if ($activeProject)
                    <a class="primary" href="{{ url('/admin/projects/'.$activeProject->id.'/configuration') }}">Otevřít {{ $activeProject->name }}</a>
                    <a href="{{ url('/admin/configuration-wizard?project='.$activeProject->id) }}">Zkontrolovat konfiguraci</a>
                @else
                    <a class="primary" href="{{ url('/admin/projects/create') }}">Založit server</a>
                    <a href="{{ url('/admin/projects') }}">Přejít na servery</a>
                @endif
            </div>
        </div>
        <div class="dz-command-steps">
            <div class="{{ $projectsCount ? 'done' : 'current' }}"><b>01</b><span><strong>Server</strong><small>{{ $projectsCount ? $projectsCount.' vytvořeno' : 'Název, platforma a mapa' }}</small></span></div>
            <div class="{{ $projectsCount && ! $incompleteCount ? 'done' : ($projectsCount ? 'current' : '') }}"><b>02</b><span><strong>Konfigurace</strong><small>{{ $incompleteCount ? $incompleteCount.' serverů čeká na první soubor' : 'Import a bezpečná validace' }}</small></span></div>
            <div><b>03</b><span><strong>Editace a export</strong><small>Vizuální změny, revize a stažení</small></span></div>
        </div>
    </section>
</x-filament-widgets::widget>

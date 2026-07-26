<x-filament-widgets::widget>
    <section class="dz-command-center">
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

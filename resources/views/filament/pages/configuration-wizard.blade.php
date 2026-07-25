<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro"><p class="dz-eyebrow">DAYZ MANAGER</p><h2>Co chcete nastavit?</h2><p class="dz-muted">Vyberte oblast. Editor vám nabídne správné soubory, parametry a bezpečnou validaci.</p></div>
        @foreach ([['server','Server a pravidla','serverDZ.cfg','heroicon-o-server'],['economy','Loot a ekonomika','types.xml · globals.xml · economycore.xml','heroicon-o-cube'],['events','Eventy a vozidla','events.xml · cfgeventspawns.xml','heroicon-o-bolt'],['map','Mapa a spawn body','mapgrouppos.xml · cfglplayerspawnpoints.xml','heroicon-o-map'],['weather','Počasí','cfgweather.xml','heroicon-o-cloud'],['gameplay','Gameplay','cfggameplay.json','heroicon-o-adjustments-horizontal'],['admin','Administrace','ban.txt · whitelist.txt · messages.xml','heroicon-o-shield-check']] as $area)
            <a class="dz-wizard-card" href="{{ url('/admin/configuration-imports') }}?area={{ $area[0] }}&open=1"><x-filament::icon :icon="$area[3]" class="dz-wizard-icon" /><strong>{{ $area[1] }}</strong><span>{{ $area[2] }}</span><em>Nahrát soubor →</em></a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>

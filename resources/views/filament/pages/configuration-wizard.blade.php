<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro"><p class="dz-eyebrow">DAYZ MANAGER</p><h2>Co chcete nastavit?</h2><p class="dz-muted">Vyberte oblast. Editor vám nabídne správné soubory, parametry a bezpečnou validaci.</p></div>
        @foreach ([['server','Server a pravidla','serverDZ.cfg','heroicon-o-server'],['economy','Loot a ekonomika','types.xml · globals.xml · economy.xml · cfgeconomycore.xml · cfglimitsdefinition*.xml','heroicon-o-cube'],['events','Eventy, vozidla a zvířata','events.xml · cfgeventspawns.xml · cfgeventgroups.xml · cfgspawnabletypes.xml · *_territories.xml','heroicon-o-bolt'],['map','Mapa a spawn body','mapgrouppos.xml · cfgplayerspawnpoints.xml · mapgroupcluster*.xml · mapgroupproto.xml · mapclusterproto.xml','heroicon-o-map'],['weather','Počasí a prostředí','cfgweather.xml · cfgenvironment.xml · cfgeffectarea.json · cfgundergroundtriggers.json','heroicon-o-cloud'],['gameplay','Gameplay','cfggameplay.json','heroicon-o-adjustments-horizontal'],['admin','Administrace a zprávy','ban.txt · whitelist.txt · messages.xml · cfgignorelist.xml · cfgrandompresets.xml','heroicon-o-shield-check']] as $area)
            <a class="dz-wizard-card" href="{{ url('/admin/configuration-import') }}?area={{ $area[0] }}"><x-filament::icon :icon="$area[3]" class="dz-wizard-icon" /><strong>{{ $area[1] }}</strong><span>{{ $area[2] }}</span><em>Nahrát soubor →</em></a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>

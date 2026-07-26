<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro"><p class="dz-eyebrow">DAYZ MANAGER</p><h2>Co chcete nastavit?</h2><p class="dz-muted">Vyberte oblast. Editor vám nabídne správné soubory, parametry a bezpečnou validaci.</p></div>
        @foreach ([
            ['server','Server a pravidla','serverDZ.cfg','Hlavní přístup, hesla, hráči, porty, čas, logování a persistence.','heroicon-o-server'],
            ['economy','Loot a ekonomika','types.xml · globals.xml · economy.xml · cfgeconomycore.xml · cfglimitsdefinition*.xml','Loot položky, globální limity, ekonomika a definice kategorií/usage flagů.','heroicon-o-cube'],
            ['events','Eventy, vozidla a zvířata','events.xml · cfgeventspawns.xml · cfgeventgroups.xml · cfgspawnabletypes.xml · *_territories.xml','Dynamické eventy, heli crash, konvoje, obsah kontejnerů, vozidla a teritoria zvířat.','heroicon-o-bolt'],
            ['map','Mapa a spawn body','mapgrouppos.xml · cfgplayerspawnpoints.xml · mapgroupcluster*.xml · mapgroupproto.xml · mapclusterproto.xml','Pozice budov, loot skupin, clusterů a spawnů hráčů na mapě.','heroicon-o-map'],
            ['weather','Počasí a prostředí','cfgweather.xml · cfgenvironment.xml · cfgeffectarea.json · cfgundergroundtriggers.json','Oblačnost, déšť, mlha, teploty, efektové oblasti a podzemní spouštěče.','heroicon-o-cloud'],
            ['gameplay','Gameplay','cfggameplay.json','Stamina, poškození, respawn, stavění, UI, mapa a pohyb hráče.','heroicon-o-adjustments-horizontal'],
            ['admin','Administrace a zprávy','ban.txt · whitelist.txt · messages.xml · cfgignorelist.xml · cfgrandompresets.xml','Zakázaní/povolení hráči, automatické zprávy, ignorované objekty a náhodné presety.','heroicon-o-shield-check'],
        ] as $area)
            <a class="dz-wizard-card" href="{{ url('/admin/configuration-import') }}?area={{ $area[0] }}"><x-filament::icon :icon="$area[4]" class="dz-wizard-icon" /><strong>{{ $area[1] }}</strong><span>{{ $area[2] }}</span><small>{{ $area[3] }}</small><em>Nahrát soubor →</em></a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>

<?php

namespace App\Services\Dayz;

final class ConfigurationCatalog
{
    /** @return array<string, array{label:string,files:string,description:string,icon:string}> */
    public function areas(): array
    {
        return [
            'server' => [
                'label' => 'Server a pravidla',
                'files' => 'serverDZ.cfg · dayzsettings.xml · BEServer_x64.cfg · dayzps-settings-*.json (Nitrado)',
                'description' => 'Přístup, hesla, hráči, porty, čas, logování, CPU job systém, BattlEye RCon a Nitrado export nastavení.',
                'icon' => 'heroicon-o-server',
            ],
            'economy' => [
                'label' => 'Loot a Central Economy',
                'files' => 'types.xml · globals.xml · economy.xml · cfgeconomycore.xml · cfglimitsdefinition*.xml · cfgareaflags.xml',
                'description' => 'Loot položky, globální limity, persistence, zálohy, root třídy a definition flagy.',
                'icon' => 'heroicon-o-cube',
            ],
            'events' => [
                'label' => 'Eventy, vozidla a zvířata',
                'files' => 'events.xml · cfgeventspawns.xml · cfgeventgroups.xml · cfgspawnabletypes.xml · *_territories.xml',
                'description' => 'Dynamické eventy, heli crash, konvoje, vozidla, cargo, nakažení a teritoria zvířat.',
                'icon' => 'heroicon-o-bolt',
            ],
            'map' => [
                'label' => 'Mapa a spawn body',
                'files' => 'cfgplayerspawnpoints.xml · mapgrouppos.xml · mapgroupcluster*.xml · mapgroupproto.xml · mapclusterproto.xml',
                'description' => 'Spawn pravidla hráčů, skupiny, pozice budov, loot clusterů a mapové souřadnice.',
                'icon' => 'heroicon-o-map',
            ],
            'environment' => [
                'label' => 'Počasí, kontaminace a podzemí',
                'files' => 'cfgweather.xml · cfgenvironment.xml · cfgeffectarea.json · cfgundergroundtriggers.json',
                'description' => 'Počasí, stáda, statické kontaminované zóny, částice a podzemní triggery.',
                'icon' => 'heroicon-o-cloud',
            ],
            'gameplay' => [
                'label' => 'Gameplay',
                'files' => 'cfggameplay.json',
                'description' => 'Stamina, damage, respawn, stavění, UI, mapa, pohyb a odkazy na doplňkové JSON soubory.',
                'icon' => 'heroicon-o-adjustments-horizontal',
            ],
            'gear' => [
                'label' => 'Startovní výbava',
                'files' => 'custom/startovni-vybava.json · *spawn-gear*.json · cfggameplay.json',
                'description' => 'Presety postav, oblečení, attachmenty, cargo, množství, kondice a váhy výběru.',
                'icon' => 'heroicon-o-briefcase',
            ],
            'objects' => [
                'label' => 'Object Spawner',
                'files' => '*spawner*.json · cfggameplay.json',
                'description' => 'Vlastní objekty na mapě: třída, X/Y/Z, rotace, měřítko a CE persistence.',
                'icon' => 'heroicon-o-building-office-2',
            ],
            'admin' => [
                'label' => 'Administrace a zprávy',
                'files' => 'messages.xml · whitelist.txt · ban.txt · priority.txt · cfgignorelist.xml · cfgrandompresets.xml',
                'description' => 'Automatické zprávy, povolení, zákazy, prioritní fronta a pomocné seznamy.',
                'icon' => 'heroicon-o-shield-check',
            ],
            'advanced' => [
                'label' => 'Pokročilá mise a PC modding',
                'files' => 'init.c · vlastní XML/JSON/CFG/TXT',
                'description' => 'Inicializace mise, vlastní CE override soubory a pokročilé PC-only konfigurace.',
                'icon' => 'heroicon-o-code-bracket',
            ],
        ];
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return collect($this->areas())
            ->mapWithKeys(fn (array $area, string $key): array => [$key => $area['label'].' · '.$area['files']])
            ->all();
    }

    /** @return array<string, list<array{filename:string,pattern:string,description:string}>> */
    public function filesByArea(): array
    {
        return [
            'server' => [
                ['filename' => 'serverDZ.cfg', 'pattern' => 'serverdz.cfg', 'description' => 'Hlavní pravidla serveru: název, hesla, sloty, whitelist, pohled, čas, porty a síťové volby.'],
                ['filename' => 'dayzsettings.xml', 'pattern' => 'dayzsettings.xml', 'description' => 'Parametry hostingu a procesu DayZ, které poskytovatel zpřístupňuje mimo serverDZ.cfg.'],
                ['filename' => 'BEServer_x64.cfg', 'pattern' => 'beserver*.cfg', 'description' => 'BattlEye RCon: heslo a port pro vzdálenou administraci PC serveru.'],
                ['filename' => 'dayzps-settings-*.json', 'pattern' => 'dayzps-settings-*.json', 'description' => 'Export nastavení z Nitrado ovládacího panelu (PlayStation/Xbox). Vlastní formát Nitrada — část polí odpovídá serverDZ.cfg, část (bans, whitelist, priority, spouštěcí parametry) existuje jen v tomto panelu.'],
            ],
            'economy' => [
                ['filename' => 'types.xml', 'pattern' => 'types.xml', 'description' => 'Každá loot položka: cílové množství, minimum, životnost, restock, kategorie a místa výskytu.'],
                ['filename' => 'globals.xml', 'pattern' => 'globals.xml', 'description' => 'Globální limity Central Economy, cleanup, persistence a chování světa.'],
                ['filename' => 'economy.xml', 'pattern' => 'economy.xml', 'description' => 'Zapnutí a vypnutí částí ekonomiky a persistence jednotlivých typů entit.'],
                ['filename' => 'cfgeconomycore.xml', 'pattern' => 'cfgeconomycore.xml', 'description' => 'Kořenová konfigurace ekonomiky a odkazy na doplňkové soubory nebo modifikace.'],
                ['filename' => 'cfglimitsdefinition.xml', 'pattern' => 'cfglimitsdefinition*.xml', 'description' => 'Definice category, usage a value tagů používaných v types.xml.'],
                ['filename' => 'cfgareaflags.xml', 'pattern' => 'cfgareaflags.xml', 'description' => 'Příznaky oblastí mapy, podle kterých ekonomika vyhodnocuje výskyt lootu.'],
            ],
            'events' => [
                ['filename' => 'events.xml', 'pattern' => 'events.xml', 'description' => 'Pravidla dynamických eventů: počty, minimum, maximum, lifetime, restock a child entity.'],
                ['filename' => 'cfgeventspawns.xml', 'pattern' => 'cfgeventspawns.xml', 'description' => 'Pevné kandidátní pozice a orientace vozidel, heli crashů, vlaků a konvojů.'],
                ['filename' => 'cfgeventgroups.xml', 'pattern' => 'cfgeventgroups.xml', 'description' => 'Složení skupinových eventů a relativní pozice jejich jednotlivých částí.'],
                ['filename' => 'cfgspawnabletypes.xml', 'pattern' => 'cfgspawnabletypes.xml', 'description' => 'Cargo, attachmenty, zásoby a poškození spawnovaných objektů a vozidel.'],
                ['filename' => '*_territories.xml', 'pattern' => '*_territories.xml', 'description' => 'Teritoria konkrétních druhů zvířat: lov, odpočinek, pastva, voda, souřadnice a poloměr.'],
            ],
            'map' => [
                ['filename' => 'cfgplayerspawnpoints.xml', 'pattern' => 'cfgplayerspawnpoints.xml', 'description' => 'Oblasti a bezpečnostní podmínky generátoru spawnů nových, běžných a hopujících hráčů.'],
                ['filename' => 'mapgrouppos.xml', 'pattern' => 'mapgrouppos.xml', 'description' => 'Světové pozice, výška a natočení budov a loot skupin.'],
                ['filename' => 'mapgroupproto.xml', 'pattern' => 'mapgroupproto.xml', 'description' => 'Prototypy loot bodů uvnitř budov a skupin; souřadnice jsou relativní.'],
                ['filename' => 'mapgroupcluster*.xml', 'pattern' => 'mapgroupcluster*.xml', 'description' => 'Rozdělené clustery mapových skupin používané Central Economy.'],
                ['filename' => 'mapclusterproto.xml', 'pattern' => 'mapclusterproto.xml', 'description' => 'Prototypy clusterů s relativními pozicemi jednotlivých objektů.'],
            ],
            'environment' => [
                ['filename' => 'cfgweather.xml', 'pattern' => 'cfgweather.xml', 'description' => 'Oblačnost, déšť, mlha, vítr, bouřky, přechody a délky jednotlivých stavů počasí.'],
                ['filename' => 'cfgenvironment.xml', 'pattern' => 'cfgenvironment.xml', 'description' => 'Ambientní prostředí, teritoria a návaznosti spawnerů zvířat.'],
                ['filename' => 'cfgeffectarea.json', 'pattern' => 'cfgeffectarea.json', 'description' => 'Statické kontaminované oblasti: pozice, poloměr, výška, částice a efekt pro hráče.'],
                ['filename' => 'cfgundergroundtriggers.json', 'pattern' => 'cfgundergroundtriggers.json', 'description' => 'Podzemní triggery, vstupy, teleporty a navázané efekty.'],
            ],
            'gameplay' => [
                ['filename' => 'cfggameplay.json', 'pattern' => 'cfggameplay.json', 'description' => 'Gameplay serveru: stamina, damage, stavění, respawn, UI, mapa a pohyb hráče.'],
            ],
            'gear' => [
                ['filename' => 'custom/startovni-vybava.json', 'pattern' => 'custom/startovni-vybava.json', 'description' => 'Vizuální editor VIP/startovní výbavy: postava, oblečení, quickbar, attachmenty, cargo, množství a kondice. Cesta musí být uvedena v PlayerData.spawnGearPresetFiles v cfggameplay.json.'],
                ['filename' => '*spawn-gear*.json', 'pattern' => '*spawn-gear*.json', 'description' => 'Presety startovní výbavy: oblečení, quickbar, attachmenty, cargo, množství a kondice.'],
            ],
            'objects' => [
                ['filename' => '*spawner*.json', 'pattern' => '*spawner*.json', 'description' => 'Vlastní objekty ve světě: třída, přesná pozice X/Y/Z, rotace, měřítko a persistence.'],
            ],
            'admin' => [
                ['filename' => 'messages.xml', 'pattern' => 'messages.xml', 'description' => 'Automatické zprávy hráčům, jejich zpoždění, opakování, odpočet a životnost.'],
                ['filename' => 'whitelist.txt', 'pattern' => 'whitelist.txt', 'description' => 'Seznam UID hráčů, kteří smějí vstoupit při aktivním whitelistu.'],
                ['filename' => 'ban.txt', 'pattern' => 'ban.txt', 'description' => 'Seznam trvale zablokovaných hráčských UID.'],
                ['filename' => 'priority.txt', 'pattern' => 'priority.txt', 'description' => 'UID hráčů s prioritním přístupem do přihlašovací fronty.'],
                ['filename' => 'cfgignorelist.xml', 'pattern' => 'cfgignorelist.xml', 'description' => 'Třídy ignorované vybranými procesy Central Economy.'],
                ['filename' => 'cfgrandompresets.xml', 'pattern' => 'cfgrandompresets.xml', 'description' => 'Sdílené náhodné presety používané spawnovacími konfiguracemi.'],
            ],
            'advanced' => [
                ['filename' => 'init.c', 'pattern' => 'init.c', 'description' => 'PC mise: inicializační skript, datum, vlastní spawn logika a pokročilé serverové úpravy.'],
            ],
        ];
    }
}

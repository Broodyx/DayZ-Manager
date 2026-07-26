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
                'files' => 'serverDZ.cfg · dayzsettings.xml · BEServer_x64.cfg',
                'description' => 'Přístup, hesla, hráči, porty, čas, logování, CPU job systém a BattlEye RCon.',
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
                'files' => '*spawn-gear*.json · cfggameplay.json',
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
}

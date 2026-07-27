<?php

namespace App\Services\Dayz;

final class ConfigurationFieldMetadata
{
    public function server(string $key): string
    {
        return self::SERVER[$key]
            ?? 'Textová hodnota. Oficiální dokumentace DayZ pro tuto volbu neuvádí povolený rozsah; editor proto kontroluje pouze platnou CFG syntaxi.';
    }

    /** @param array{path?: string, type?: string} $field */
    public function json(array $field): string
    {
        $key = str((string) ($field['path'] ?? ''))->afterLast('.')->toString();

        if (isset(self::GAMEPLAY[$key])) {
            return self::GAMEPLAY[$key];
        }

        return match ($field['type'] ?? 'text') {
            'boolean' => 'Logická hodnota: povoleno je pouze true nebo false. Význam zapnutého stavu plyne z názvu této volby.',
            'number' => 'Číselná hodnota. Oficiální dokumentace pro tento parametr nepublikuje pevné minimum ani maximum; editor proto nevnucuje smyšlený rozsah.',
            'json' => 'JSON seznam nebo objekt. Počet položek ani povolené hodnoty nejsou pro tento parametr v oficiální dokumentaci obecně omezeny.',
            default => 'Textová hodnota. Oficiální dokumentace pro tento parametr nepublikuje pevný seznam povolených hodnot.',
        };
    }

    /** @param array{path?: string, label?: string, type?: string} $field */
    public function xml(string $filename, array $field): string
    {
        $path = strtolower((string) ($field['path'] ?? ''));
        $name = strtolower((string) ($field['label'] ?? ''));
        $filename = strtolower(basename($filename));

        if (str_ends_with($path, '@name')) {
            return match ($filename) {
                'events.xml' => 'Jedinečný název eventu. Propojuje pravidla v events.xml s pozicemi v cfgeventspawns.xml a případně se skupinou v cfgeventgroups.xml.',
                'globals.xml' => 'Název globální proměnné Central Economy. Název neměňte; upravujte pouze její atribut value.',
                'cfgspawnabletypes.xml' => 'Název DayZ třídy předmětu. Určuje, pro který předmět platí následující cargo a attachment pravidla.',
                'cfgeconomycore.xml', 'economycore.xml' => 'Identifikátor kořenové třídy nebo připojeného souboru ekonomiky. Změna názvu může přerušit vazbu na soubor.',
                'cfglimitsdefinition.xml', 'cfglimitsdefinitionuser.xml' => 'Název definice používaný v types.xml. Musí přesně odpovídat hodnotě category, usage, tag nebo value.',
                default => 'Identifikátor tohoto XML záznamu. Změňte jej jen tehdy, když současně upravíte všechny související odkazy.',
            };
        }
        if (str_ends_with($path, '@value')) {
            return $filename === 'globals.xml'
                ? 'Hodnota globální proměnné Central Economy. Jednotka a bezpečný rozsah závisí na konkrétním názvu proměnné.'
                : 'Hodnota XML atributu. Povolený formát závisí na nadřazeném záznamu.';
        }

        foreach (self::XML as $needle => $description) {
            if (str_contains($name, $needle) || str_contains($path, str_replace(' ', '_', $needle))) {
                return $description;
            }
        }

        if (str_ends_with($path, '@x') || str_ends_with($path, '/x[1]/text()')) {
            return 'Světová souřadnice X v metrech. Chernarus používá přibližně 0–15 360; jiná mapa může mít jiný rozsah.';
        }
        if (str_ends_with($path, '@z') || str_ends_with($path, '/z[1]/text()')) {
            return 'Světová souřadnice Z v metrech. Chernarus používá přibližně 0–15 360; jiná mapa může mít jiný rozsah.';
        }
        if (str_ends_with($path, '@y')) {
            return 'Výška Y v metrech. Pevný globální rozsah není publikován; hodnota musí odpovídat terénu dané mapy.';
        }
        if (str_ends_with($path, '@a')) {
            return 'Orientace ve stupních; povolený rozsah 0–360.';
        }

        return match ($field['type'] ?? 'text') {
            'boolean' => 'Logická XML hodnota: povoleno je true nebo false.',
            'number' => 'Číselná XML hodnota. Pro tento parametr souboru '.$filename.' není v oficiální dokumentaci publikován univerzální bezpečný rozsah.',
            default => 'Textová XML hodnota. Pro tento parametr souboru '.$filename.' není publikován uzavřený seznam povolených hodnot.',
        };
    }

    private const SERVER = [
        'hostname' => 'Název serveru v seznamu. Text; oficiální maximální délka není uvedena.',
        'description' => 'Popis v prohlížeči serverů. Text, nejvýše 255 znaků.',
        'password' => 'Heslo pro připojení. Text; prázdná hodnota znamená server bez hesla.',
        'passwordAdmin' => 'Heslo pro administrátorské příkazy. Text; prázdná hodnota administrátorské přihlášení nezabezpečí.',
        'enableWhitelist' => 'Kontrola whitelist.txt: 0 = vypnuta, 1 = zapnuta. Povolené jsou pouze hodnoty 0 a 1.',
        'disableBanlist' => 'Použití ban.txt: false = banlist se používá, true = banlist je vypnutý. Povolené jsou pouze true/false.',
        'disablePrioritylist' => 'Použití priority.txt: false = prioritní seznam se používá, true = je vypnutý. Povolené jsou pouze true/false.',
        'maxPlayers' => 'Maximum současně připojených hráčů. Celé číslo; editor povoluje 1–128, oficiální dokumentace pevné maximum neuvádí.',
        'verifySignatures' => 'Ověření PBO podpisů. Oficiálně podporovaná a bezpečná hodnota je pouze 2.',
        'forceSameBuild' => 'Shodný build klienta: 0 = nevyžadovat, 1 = vyžadovat. Povolené jsou pouze 0/1.',
        'disableVoN' => 'Hlasová komunikace: 0 = povolena, 1 = zakázána. Povolené jsou pouze 0/1.',
        'vonCodecQuality' => 'Kvalita hlasového kodeku. Celé číslo 0–20; vyšší hodnota znamená vyšší kvalitu.',
        'disable3rdPerson' => 'Pohled třetí osoby: 0 = povolen, 1 = zakázán. Povolené jsou pouze 0/1.',
        'disableCrosshair' => 'Zaměřovací kříž: 0 = povolen, 1 = zakázán. Povolené jsou pouze 0/1.',
        'disablePersonalLight' => 'Osobní noční světlo: 0 = povoleno, 1 = zakázáno. Povolené jsou pouze 0/1.',
        'lightingConfig' => 'Profil nočního osvětlení: 0 = světlejší noc, 1 = tmavší noc, 2 = profil Sakhal. Povolené jsou 0–2.',
        'serverTime' => 'Počáteční herní čas: SystemTime nebo datum ve formátu YYYY/MM/DD/HH/MM.',
        'serverTimeAcceleration' => 'Násobič rychlosti dne. Desetinné číslo 0.1–64; 1 = reálný čas.',
        'serverNightTimeAcceleration' => 'Další násobič rychlosti noci. Desetinné číslo 0.1–64; násobí se serverTimeAcceleration.',
        'serverTimePersistent' => 'Persistence času: 0 = po restartu použít serverTime, 1 = načíst uložený čas. Pouze 0/1.',
        'guaranteedUpdates' => 'Síťový protokol garantovaných aktualizací. Oficiálně se má použít pouze hodnota 1.',
        'loginQueueConcurrentPlayers' => 'Počet přihlášení zpracovaných současně. Kladné celé číslo; oficiální minimum ani maximum nejsou publikovány.',
        'loginQueueMaxPlayers' => 'Maximum hráčů čekajících ve frontě. Nezáporné celé číslo; oficiální maximum není publikováno.',
        'instanceId' => 'ID instance oddělující persistence složky na jednom stroji. Nezáporné celé číslo; oficiální maximum není publikováno.',
        'storeHouseStateDisabled' => 'Ukládání stavů domů a dveří: false = ukládání je aktivní, true = ukládání je vypnuté. Povolené jsou pouze true/false.',
        'storageAutoFix' => 'Automatická oprava poškozené persistence: 0 = vypnuta, 1 = zapnuta. Pouze 0/1.',
        'template' => 'Mise ve formátu <název_mise>.<název_terénu>, např. dayzOffline.chernarusplus. Nejde o číselnou hodnotu.',
        'steamport' => 'UDP port herní služby. Celé číslo 1024–65535; musí být volný.',
        'steamqueryport' => 'UDP port Steam dotazů. Celé číslo 1024–65535; musí být volný a odlišný od obsazených portů.',
        'clientPort' => 'Vynucený UDP port klientského připojení. Celé číslo 1024–65535.',
        'respawnTime' => 'Prodleva před vytvořením nové postavy po smrti, v sekundách. Nezáporné číslo; oficiální maximum není publikováno.',
        'motd' => 'Zpráva dne. Text nebo pole textů motd[]; pevný počet řádků není publikován.',
        'motdInterval' => 'Interval mezi řádky MOTD v sekundách. Nezáporné celé číslo; oficiální maximum není publikováno.',
        'timeStampFormat' => 'Formát času v RPT logu. Povolené hodnoty: Short nebo Full.',
        'logAverageFps' => 'Interval logování průměrného FPS v sekundách. Nezáporné číslo; vyžaduje -doLogs.',
        'logMemory' => 'Interval logování paměti v sekundách. Nezáporné číslo; vyžaduje -doLogs.',
        'logPlayers' => 'Interval logování počtu hráčů v sekundách. Nezáporné číslo; vyžaduje -doLogs.',
        'logFile' => 'Název souboru konzolového logu v profiles složce. Textová hodnota.',
        'adminLogPlayerHitsOnly' => 'Rozsah admin logu zásahů: 0 = všechny zásahy, 1 = pouze zásahy hráčů. Pouze 0/1.',
        'adminLogPlacement' => 'Logování umisťování objektů: 0 = vypnuto, 1 = zapnuto. Pouze 0/1.',
        'adminLogBuildActions' => 'Logování stavění, rozebírání a ničení: 0 = vypnuto, 1 = zapnuto. Pouze 0/1.',
        'adminLogPlayerList' => 'Periodický seznam hráčů v admin logu: 0 = vypnuto, 1 = zapnuto. Pouze 0/1.',
        'disableMultiAccountMitigation' => 'Ochrana proti více účtům na konzolích: false = aktivní, true = vypnutá. Pouze true/false.',
        'enableDebugMonitor' => 'Debug monitor: 0 = skrytý, 1 = zobrazený. Pouze 0/1.',
        'allowFilePatching' => 'Klienti s -filePatching: 0 = zakázáni, 1 = povoleni. Pouze 0/1; určeno pro PC vývoj.',
        'simulatedPlayersBatch' => 'Počet simulovaných hráčů zpracovaných za snímek. Nezáporné celé číslo; oficiální maximum není publikováno.',
        'multithreadedReplication' => 'Vícevláknová replikace: 0 = vypnuta, 1 = zapnuta. Pouze 0/1.',
        'defaultVisibility' => 'Nejvyšší dohled terénu v metrech. Kladné číslo; oficiální maximum není publikováno.',
        'defaultObjectViewDistance' => 'Nejvyšší dohled objektů v metrech. Kladné číslo; oficiální maximum není publikováno.',
        'disableBaseDamage' => 'Poškození plotů a věží: 0 = povoleno, 1 = zakázáno. Pouze 0/1.',
        'disableContainerDamage' => 'Poškození stanů, sudů, beden a sea chest: 0 = povoleno, 1 = zakázáno. Pouze 0/1.',
        'disableRespawnDialog' => 'Dialog volby respawnu: 0 = zobrazen, 1 = zakázán. Pouze 0/1.',
        'disableRespawnInUnconsciousness' => 'Respawn v bezvědomí: 0 = povolen, 1 = zakázán. Pouze 0/1.',
        'enableCfgGameplayFile' => 'Načtení cfggameplay.json: 0 = vypnuto, 1 = zapnuto. Pouze 0/1.',
        'pingWarning' => 'Práh žlutého varování pingu v milisekundách. Nezáporné celé číslo.',
        'pingCritical' => 'Práh červeného varování pingu v milisekundách. Nezáporné celé číslo, obvykle vyšší než pingWarning.',
        'MaxPing' => 'Práh odpojení hráče v milisekundách. Nezáporné celé číslo, obvykle vyšší než pingCritical.',
        'serverFpsWarning' => 'Práh varování nízkého server FPS. Celé číslo; oficiální minimum je 11.',
        'shotValidation' => 'Validace střelby: 0 = vypnuta, 1 = zapnuta. Pouze 0/1.',
        'networkObjectBatchSend' => 'Počet síťových objektů odeslaných v dávce. Kladné celé číslo; oficiální limit není publikován.',
        'networkObjectBatchCompute' => 'Počet síťových objektů zpracovaných v dávce. Kladné celé číslo; oficiální limit není publikován.',
    ];

    private const GAMEPLAY = [
        'version' => 'Interní verze formátu souboru. Celé číslo dodané s aktuální verzí DayZ; ruční změna se nedoporučuje.',
        'disableBaseDamage' => 'Ničení základen: true = ničení je zakázáno, false = ničení je povoleno. Pouze true/false.',
        'disableContainerDamage' => 'Ničení kontejnerů: true = poškození je zakázáno, false = povoleno. Pouze true/false.',
        'disableRespawnDialog' => 'Dialog respawnu: true = skrytý, false = zobrazený. Pouze true/false.',
        'disableRespawnInUnconsciousness' => 'Respawn v bezvědomí: true = zakázán, false = povolen. Pouze true/false.',
        'disablePersonalLight' => 'Osobní noční světlo: true = zakázáno, false = povoleno. Pouze true/false.',
        'sprintStaminaModifierErc' => 'Násobič spotřeby staminy při vzpřímeném sprintu. Desetinné číslo; 1 = výchozí, 0 = bez spotřeby, oficiální maximum není publikováno.',
        'sprintStaminaModifierCro' => 'Násobič spotřeby staminy při sprintu skrčmo. Desetinné číslo; 1 = výchozí, 0 = bez spotřeby, oficiální maximum není publikováno.',
        'staminaWeightLimitThreshold' => 'Hmotnostní práh pro postih staminy v gramech. Nezáporné číslo; výchozí 6000, oficiální maximum není publikováno.',
        'staminaMax' => 'Maximální stamina. Kladné číslo; výchozí 100 a hodnota 0 může způsobit neočekávané chování.',
        'staminaKgToStaminaPercentPenalty' => 'Násobič postihu maximální staminy podle nesené hmotnosti. Nezáporné desetinné číslo; výchozí 1.75.',
        'staminaMinCap' => 'Minimální zbývající stamina. Kladné číslo; výchozí 5 a hodnota 0 může způsobit neočekávané chování.',
        'sprintSwimmingStaminaModifier' => 'Násobič spotřeby staminy při rychlém plavání. Nezáporné číslo; 1 = výchozí, oficiální maximum není publikováno.',
        'sprintLadderStaminaModifier' => 'Násobič spotřeby staminy při rychlém lezení. Nezáporné číslo; 1 = výchozí, oficiální maximum není publikováno.',
        'meleeStaminaModifier' => 'Násobič spotřeby staminy při těžkém útoku a úhybu. Nezáporné číslo; 1 = výchozí.',
        'obstacleTraversalStaminaModifier' => 'Násobič spotřeby staminy při skoku, lezení a vaultu. Nezáporné číslo; 1 = výchozí.',
        'holdBreathStaminaModifier' => 'Násobič spotřeby staminy při zadržení dechu. Nezáporné číslo; 1 = výchozí.',
        'shockRefillSpeedConscious' => 'Obnova shocku za sekundu při vědomí. Nezáporné číslo; výchozí 5.',
        'shockRefillSpeedUnconscious' => 'Obnova shocku za sekundu v bezvědomí. Nezáporné číslo; výchozí 1.',
        'allowRefillSpeedModifier' => 'Modifikátor obnovy shocku podle munice: true = povolen, false = ignorován. Pouze true/false.',
        'timeToStrafeJog' => 'Čas přechodu do diagonálního běhu v sekundách. Minimum 0.01; výchozí 0.1.',
        'rotationSpeedJog' => 'Rychlost otáčení při běhu. Minimum 0.01; výchozí 0.15 v oficiálním příkladu.',
        'timeToSprint' => 'Čas přechodu z běhu do sprintu v sekundách. Minimum 0.01; výchozí 0.45.',
        'timeToStrafeSprint' => 'Čas přechodu do diagonálního sprintu v sekundách. Minimum 0.01; výchozí 0.3.',
        'rotationSpeedSprint' => 'Rychlost otáčení při sprintu. Minimum 0.01; výchozí 0.15.',
        'allowStaminaAffectInertia' => 'Vliv staminy na setrvačnost: true = aktivní, false = vypnutý. Pouze true/false.',
        'staminaDepletionSpeed' => 'Stamina odebraná za sekundu při tonutí. Nezáporné číslo; výchozí 10.',
        'healthDepletionSpeed' => 'Zdraví odebrané za sekundu při tonutí. Nezáporné číslo; výchozí 10.',
        'shockDepletionSpeed' => 'Shock odebraný za sekundu při tonutí. Nezáporné číslo; výchozí 10.',
        'staticMode' => 'Kolize zbraně se statickými objekty: 0 = vypnuto, 1 = překážet a zvednout, 2 = vždy překážet bez zvednutí.',
        'dynamicMode' => 'Kolize zbraně s dynamickými objekty: 0 = vypnuto, 1 = překážet a zvednout, 2 = vždy překážet bez zvednutí.',
        'lightingConfig' => 'Profil noci: 0 = světlejší, 1 = tmavší, 2 = profil Sakhal. Povolené hodnoty 0–2.',
        'objectSpawnersArr' => 'Seznam názvů Object Spawner JSON souborů. JSON pole řetězců; prázdné [] znamená žádné soubory.',
        'spawnGearPresetFiles' => 'Seznam JSON souborů presetů startovní výbavy. JSON pole řetězců; prázdné [] funkci vypne.',
        'environmentMinTemps' => 'Minimální měsíční teploty. JSON pole přesně 12 čísel, leden až prosinec; oficiální teplotní limit není publikován.',
        'environmentMaxTemps' => 'Maximální měsíční teploty. JSON pole přesně 12 čísel, leden až prosinec; každá hodnota má být ≥ odpovídajícímu minimu.',
        'wetnessWeightModifiers' => 'Násobiče hmotnosti pro DRY, DAMP, WET, SOAKED a DRENCHED. JSON pole přesně 5 nezáporných čísel.',
        'boatDecayMultiplier' => 'Násobič rychlosti chátrání lodí. Nezáporné číslo; 1 = výchozí rychlost, 0 = bez chátrání.',
        'disableIsCollidingBBoxCheck' => 'Kolize hologramu s okolními objekty: true = kontrola vypnuta, false = kolize umístění blokuje. Pouze true/false.',
        'disableIsCollidingPlayerCheck' => 'Kolize hologramu s hráčem: true = kontrola vypnuta, false = hráč umístění blokuje. Pouze true/false.',
        'disableIsClippingRoofCheck' => 'Průnik hologramu střechou: true = kontrola vypnuta, false = průnik umístění blokuje. Pouze true/false.',
        'disableIsBaseViableCheck' => 'Platnost podkladu: true = kontrola vypnuta, false = vyžadovat vhodný podklad. Pouze true/false.',
        'disableIsCollidingGPlotCheck' => 'Povrch zahrádky: true = kontrola vypnuta, false = vyžadovat kompatibilní povrch. Pouze true/false.',
        'disableIsCollidingAngleCheck' => 'Limity náklonu objektu: true = kontrola vypnuta, false = překročení úhlů umístění blokuje. Pouze true/false.',
        'disableIsPlacementPermittedCheck' => 'Základní oprávnění umístění: true = kontrola vypnuta, false = pravidla hry zůstávají aktivní. Pouze true/false.',
        'disableHeightPlacementCheck' => 'Volný prostor na výšku: true = kontrola vypnuta, false = nedostatek místa umístění blokuje. Pouze true/false.',
        'disableIsUnderwaterCheck' => 'Umístění pod vodou: true = kontrola vypnuta a umístění lze povolit, false = voda umístění blokuje. Pouze true/false.',
        'disableIsInTerrainCheck' => 'Průnik s terénem: true = kontrola vypnuta, false = průnik umístění blokuje. Pouze true/false.',
        'disableColdAreaBuildingCheck' => 'Stavění v chladné oblasti: true = kontrola vypnuta, false = omezení chladem zůstává aktivní. Pouze true/false.',
        'disableColdAreaPlacementCheck' => 'Umístění zahrádky na zmrzlé zemi: true = kontrola vypnuta, false = zmrzlá zem umístění blokuje. Pouze true/false.',
        'disablePerformRoofCheck' => 'Průnik konstrukce střechou: true = kontrola vypnuta, false = průnik stavbu blokuje. Pouze true/false.',
        'disableIsCollidingCheck' => 'Kolize konstrukce s objekty: true = kontrola vypnuta, false = kolize stavbu blokuje. Pouze true/false.',
        'disableDistanceCheck' => 'Vzdálenost hráče při stavbě: true = kontrola vypnuta, false = platí herní limit. Pouze true/false.',
        'disallowedTypesInUnderground' => 'Třídy zakázané v podzemních oblastech. JSON pole názvů tříd; prázdné [] znamená bez dodatečného zákazu.',
        'hitDirectionOverrideEnabled' => 'Vlastní indikátor směru zásahu: true = použít nastavení této sekce, false = výchozí chování hry. Pouze true/false.',
        'hitDirectionBehaviour' => 'Režim chování indikátoru zásahu. Celé číslo; oficiální stránka nepublikuje uzavřený seznam podporovaných hodnot.',
        'hitDirectionStyle' => 'Vizuální styl indikátoru zásahu. Celé číslo; oficiální stránka nepublikuje uzavřený seznam podporovaných hodnot.',
        'hitDirectionIndicatorColorStr' => 'Barva indikátoru v ARGB řetězci 0xAARRGGBB; každá hex složka má rozsah 00–FF.',
        'hitDirectionBreakPointRelative' => 'Část doby zobrazení, po které indikátor začne mizet. Rozsah 0–1.',
        'hitDirectionScatter' => 'Náhodná odchylka směru v každém směru ve stupních. Nezáporné číslo; 10 znamená celkový rozptyl až 20°.',
        'hitDirectionMaxDuration' => 'Maximální doba zobrazení indikátoru zásahu v sekundách. Nezáporné číslo.',
        'hitIndicationPostProcessEnabled' => 'Červený post-process zásahu: true = zapnutý, false = vypnutý. Pouze true/false.',
        'use3DMap' => 'Použít pouze 3D mapu: true = ano, false = standardní 2D mapa. Pouze true/false.',
        'ignoreMapOwnership' => 'Otevření mapy bez mapového předmětu: true = povoleno, false = vyžadovat předmět. Pouze true/false.',
        'ignoreNavItemsOwnership' => 'Navigační pomůcky bez kompasu/GPS: true = povoleno, false = vyžadovat předměty. Pouze true/false.',
        'displayPlayerPosition' => 'Pozice a směr hráče na mapě: true = zobrazit, false = skrýt. Pouze true/false.',
        'displayNavInfo' => 'GPS a kompas v legendě mapy: true = zobrazit, false = skrýt. Pouze true/false.',
        'Radius' => 'Poloměr oblasti v metrech. Kladné číslo; konkrétní maximum závisí na mapě a není globálně publikováno.',
        'scale' => 'Měřítko objektu. Kladné číslo; 1 = původní velikost, oficiální maximum není publikováno.',
        'enableCEPersistency' => 'Persistence objektu v Central Economy: true = povolena, false = vypnuta. Pouze true/false.',
        'spawnWeight' => 'Relativní váha výběru presetu. Kladné celé číslo; minimum 1, oficiální maximum není publikováno.',
        'Objects' => 'Seznam objektů vytvořených při startu mise. JSON pole objektů; každý záznam používá name, pos, ypr, scale a enableCEPersistency.',
        'Areas' => 'Seznam efektových nebo kontaminovaných oblastí. JSON pole objektů; počet oblastí není oficiálně omezen.',
        'Triggers' => 'Spouštěče podzemních oblastí. JSON pole objektů; konkrétní povinná pole určuje formát underground triggeru.',
        'Breadcrumbs' => 'Pomocné body podzemní trasy. JSON pole souřadnicových objektů; počet bodů není oficiálně omezen.',
        'pos' => 'Pozice objektu [X,Y,Z] v metrech. JSON pole přesně 3 čísel; rozsah X/Z závisí na mapě.',
        'Pos' => 'Střed oblasti [X,Y,Z] v metrech. JSON pole přesně 3 čísel; rozsah X/Z závisí na mapě.',
        'ypr' => 'Orientace [yaw,pitch,roll] ve stupních. JSON pole přesně 3 čísel.',
        'characterTypes' => 'Seznam tříd postav pro spawn preset. JSON pole názvů tříd.',
        'attachmentSlotItemSets' => 'Sady výbavy pro attachment sloty postavy. JSON pole objektů; strukturu a váhy musí zachovat preset schema.',
        'discreteUnsortedItemSets' => 'Volné sady položek do inventáře. JSON pole objektů; strukturu a váhy musí zachovat preset schema.',
    ];

    private const XML = [
        'min dist infected' => 'Minimální vzdálenost od infikovaných v metrech. Nezáporné číslo; bližší kandidát je neplatný.',
        'max dist infected' => 'Horní vzdálenost od infikovaných pro hodnocení kandidáta v metrech. Musí být ≥ minDistInfected.',
        'min dist player' => 'Minimální vzdálenost od jiného hráče v metrech. Nezáporné číslo.',
        'max dist player' => 'Horní vzdálenost od hráčů pro hodnocení kandidáta v metrech. Musí být ≥ minDistPlayer.',
        'min dist static' => 'Minimální vzdálenost od statického objektu v metrech. Nezáporné číslo.',
        'max dist static' => 'Horní vzdálenost od statických objektů pro hodnocení kandidáta. Musí být ≥ minDistStatic.',
        'grid density' => 'Hustota vzorkovací mřížky. Kladné celé číslo; oficiální maximum není publikováno.',
        'grid width' => 'Šířka generované spawn oblasti v metrech. Kladné číslo.',
        'grid height' => 'Výška generované spawn oblasti v metrech. Kladné číslo.',
        'min steepness' => 'Minimální sklon terénu ve stupních. Musí být nižší nebo roven maxSteepness.',
        'max steepness' => 'Maximální sklon terénu ve stupních. Musí být vyšší nebo roven minSteepness.',
        'enablegroups' => 'Použití skupin spawnů: true = zapnuto, false = vypnuto. Pouze true/false.',
        'groups as regular' => 'Použití skupin jako běžných bodů: true = povoleno, false = zakázáno. Pouze true/false.',
        'backup period' => 'Interval persistence záloh v minutách. Oficiální minimum 15.',
        'backup count' => 'Počet uchovávaných persistence záloh. Kladné celé číslo.',
        'backup startup' => 'Záloha po inicializaci CE: true = vytvořit, false = nevytvářet. Pouze true/false.',
        'world segments' => 'Počet segmentů světa při ukládání CE. Kladné celé číslo; výchozí Chernarus používá 12.',
        'dyn radius' => 'Výchozí poloměr dynamické infected zóny v metrech. Nezáporné číslo.',
        'report memory lod' => 'Hlášení chybějícího Memory LOD. Povolené hodnoty yes nebo no.',
        'lootmin' => 'Minimální počet aktivních loot pozic. Nezáporné celé číslo a musí být ≤ lootmax.',
        'lootmax' => 'Maximální počet aktivních loot pozic. Nezáporné celé číslo a musí být ≥ lootmin.',
        'deloot' => 'Dynamic Event Loot: 0 = vypnuto, 1 = zapnuto. Pouze 0/1.',
        'nominal' => 'Cílový počet v Central Economy. Nezáporné celé číslo; 0 znamená, že CE položku běžně nedoplňuje.',
        'restock' => 'Prodleva doplnění v sekundách. Nezáporné celé číslo.',
        'quantmin' => 'Minimální naplnění v procentech. Rozsah -1 až 100; -1 používá výchozí chování.',
        'quantmax' => 'Maximální naplnění v procentech. Rozsah -1 až 100 a musí být ≥ quantmin.',
        'lifetime' => 'Životnost záznamu v sekundách. Nezáporné číslo; 3600 znamená jednu hodinu.',
        'saferadius' => 'Minimální bezpečná vzdálenost od hráče při vytvoření eventu v metrech. Nezáporné číslo.',
        'distanceradius' => 'Vzdálenost používaná eventem při kontrole dalších instancí nebo hráčů, v metrech. Nezáporné číslo.',
        'cleanupradius' => 'Poloměr úklidu objektů eventu v metrech. Nezáporné číslo.',
        'deletable' => 'Smí Central Economy záznam odstranit: 0 = ne, 1 = ano. Pouze 0/1.',
        'init random' => 'Náhodná počáteční inicializace počtu: 0 = vypnuta, 1 = zapnuta. Pouze 0/1.',
        'remove damaged' => 'Odstranění poškozených instancí při inicializaci: 0 = ponechat, 1 = odstranit. Pouze 0/1.',
        'active' => 'Aktivace eventu: 0 = vypnutý, 1 = zapnutý. Pouze 0/1.',
        'position' => 'Strategie výběru pozice eventu. Jde o interní hodnotu DayZ; zachovejte hodnotu z oficiální konfigurace, pokud přesně neznáte odpovídající režim.',
        'limit' => 'Způsob aplikace limitu eventu. Povolené názvy závisejí na dané verzi DayZ; bezpečné je zachovat hodnotu z výchozí konfigurace.',
        'chance' => 'Pravděpodobnost výběru záznamu. Desetinná hodnota 0–1; 0 = nikdy, 1 = vždy.',
        'damage' => 'Počáteční poškození předmětu. Desetinná hodnota 0–1; 0 = nepoškozený, 1 = zničený.',
        'cost' => 'Relativní priorita či náklad záznamu v Central Economy. Nezáporné celé číslo; vyšší hodnota znamená vyšší prioritu.',
        'min' => 'Minimální počet instancí. Nezáporné celé číslo a nesmí být vyšší než max.',
        'max' => 'Maximální počet instancí. Nezáporné celé číslo a nesmí být nižší než min.',
    ];
}

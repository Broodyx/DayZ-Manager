<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Dayz\MapConfigurationReader;
use App\Services\Import\ConfigurationImporter;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class MapEditor extends Page
{
    use WithFileUploads;

    protected static string $view = 'filament.pages.map-editor';

    protected static bool $shouldRegisterNavigation = false;

    public string $map = 'Chernarus';

    public ?int $projectId = null;

    public array $projects = [];

    public $mapFile;

    public array $markers = [];

    public array $eventCatalog = [];
    public array $pointTypeCatalog = [];
    public array $mapSources = [];
    public array $markerCounts = [];
    public array $loadedSources = [];
    public array $layerScopes = [];
    public bool $showDenseLayers = false;

    public function mount(): void
    {
        $this->projects = $this->projectQuery()->orderBy('name')->pluck('name', 'id')->all();
        $this->projectId = request()->integer('project') ?: array_key_first($this->projects);
        $this->showDenseLayers = request()->boolean('dense');
        $this->loadMarkers();
        $this->loadMapSources();
        $this->loadEventCatalog();
        $this->loadPointTypeCatalog();
    }

    public function updatedProjectId(): void
    {
        $this->loadMarkers();
        $this->loadEventCatalog();
        $this->loadMapSources();
        $this->loadPointTypeCatalog();
    }

    public function loadMapSources(): void
    {
        $definitions = [
            'cfgeventspawns.xml' => ['Pevné kandidátní pozice eventů a jejich orientace.', true],
            'events.xml' => ['Pravidla dynamických eventů, vozidel a heli crashů; neobsahuje mapové souřadnice.', false],
            'cfgeventgroups.xml' => ['Složení a varianty eventů; neobsahuje mapové souřadnice.', false],
            'cfgplayerspawnpoints.xml' => ['Oblasti generátoru spawnů hráčů. Body nejsou přesná místa zrození.', true],
            'mapgrouppos.xml' => ['Světové pozice loot skupin a budov.', true],
            'mapgroupcluster.xml' => ['Definice clusterů budov; relativní data se do mapy nekreslí.', false],
            'mapgroupcluster01.xml' => ['Definice clusterů budov – část 1.', false],
            'mapgroupcluster02.xml' => ['Definice clusterů budov – část 2.', false],
            'mapgroupcluster03.xml' => ['Definice clusterů budov – část 3.', false],
            'mapgroupcluster04.xml' => ['Definice clusterů budov – část 4.', false],
            'mapgroupproto.xml' => ['Prototypy skupin; souřadnice jsou relativní, nikoli světové.', false],
            'mapclusterproto.xml' => ['Prototypy clusterů; souřadnice jsou relativní, nikoli světové.', false],
            'mapgroupdirt.xml' => ['Doplňková prototypová data bez světových bodů.', false],
            'cfgeffectarea.json' => ['Efektové a kontaminované oblasti.', true],
            'cfgundergroundtriggers.json' => ['Spouštěče podzemních oblastí.', true],
            '*spawner*.json' => ['Object Spawner: vlastní objekty, pozice a orientace.', true],
            '*_territories.xml' => ['Teritoria zvířat podle druhu.', true],
        ];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $this->latestRevisions($project);
        $this->mapSources = [];
        foreach ($definitions as $filename => [$description, $plottable]) {
            $matches = $revisions->filter(function ($item) use ($filename) {
                $name = $this->revisionFilename($item);
                return str_contains($filename, '*') ? Str::is($filename, $name) : $name === $filename;
            });
            if ($matches->isEmpty()) {
                $this->mapSources[] = $this->source($filename, $description, $plottable);
                continue;
            }
            foreach ($matches as $revision) {
                $actualName = $this->revisionFilename($revision);
                $this->mapSources[] = $this->source($actualName, $description, $plottable, $revision);
            }
        }
    }

    public function loadEventCatalog(): void
    {
        $this->eventCatalog = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $this->latestRevisions($project);
        $byName = fn (string $name) => $revisions->first(fn ($item) => $this->revisionFilename($item) === $name);
        $events = $byName('events.xml');
        if ($events && Storage::disk('dayz')->exists($events->storage_path)) {
            $xml = @simplexml_load_string(Storage::disk('dayz')->get($events->storage_path));
            foreach ($xml?->event ?? [] as $event) {
                $children = [];
                foreach ($event->children->child ?? [] as $child) {
                    $children[] = [
                        'type' => (string) ($child['type'] ?? ''),
                        'min' => (int) ($child['min'] ?? 0),
                        'max' => (int) ($child['max'] ?? 0),
                        'lootmin' => (int) ($child['lootmin'] ?? 0),
                        'lootmax' => (int) ($child['lootmax'] ?? 0),
                    ];
                }
                $this->eventCatalog[] = [
                    'name' => (string) $event['name'],
                    'children' => array_values(array_filter($children, fn ($child) => $child['type'] !== '')),
                    'settings' => [
                        'nominal' => (int) ($event->nominal ?? 0),
                        'min' => (int) ($event->min ?? 0),
                        'max' => (int) ($event->max ?? 0),
                        'lifetime' => (int) ($event->lifetime ?? 0),
                        'restock' => (int) ($event->restock ?? 0),
                        'saferadius' => (int) ($event->saferadius ?? 0),
                        'distanceradius' => (int) ($event->distanceradius ?? 0),
                        'cleanupradius' => (int) ($event->cleanupradius ?? 0),
                        'position' => (string) ($event->position ?? ''),
                        'limit' => (string) ($event->limit ?? ''),
                        'active' => (int) ($event->active ?? 0),
                        'deletable' => (int) ($event->flags['deletable'] ?? 0),
                        'init_random' => (int) ($event->flags['init_random'] ?? 0),
                        'remove_damaged' => (int) ($event->flags['remove_damaged'] ?? 0),
                    ],
                ];
            }
        }

        // cfgeventgroups.xml is the authoritative catalogue for compound events
        // (trains, convoys and their individual vehicle/object classes).
        $groups = $byName('cfgeventgroups.xml');
        if ($groups && Storage::disk('dayz')->exists($groups->storage_path)) {
            $xml = @simplexml_load_string(Storage::disk('dayz')->get($groups->storage_path));
            foreach ($xml?->group ?? [] as $group) {
                $children = [];
                foreach ($group->child ?? [] as $child) {
                    $type = (string) ($child['type'] ?? '');
                    if ($type !== '') {
                        $children[] = $type;
                    }
                }
                $name = (string) ($group['name'] ?? '');
                if ($name === '') continue;
                $index = collect($this->eventCatalog)->search(fn ($item) => $item['name'] === $name);
                if ($index !== false) {
                    $known = collect($this->eventCatalog[$index]['children'])->pluck('type')->all();
                    foreach ($children as $type) {
                        if (! in_array($type, $known, true)) {
                            $this->eventCatalog[$index]['children'][] = ['type' => $type, 'min' => 0, 'max' => 0, 'lootmin' => 0, 'lootmax' => 0];
                        }
                    }
                }
            }
        }
    }

    public function loadPointTypeCatalog(): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $this->latestRevisions($project);
        $uploaded = $revisions->mapWithKeys(fn ($revision) => [
            $this->revisionFilename($revision) => [
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
            ],
        ])->all();
        $uploadUrl = fn (string $filename) => url('/admin/configuration-import?area=map&project='.$this->projectId.'&expected='.urlencode($filename));
        $exact = function (string $filename, array $options, string $help, array $recommended = []) use ($uploaded, $uploadUrl): array {
            $missing = array_values(array_filter([$filename, ...$recommended], fn ($name) => ! isset($uploaded[$name])));

            return [
                'target' => $filename,
                'target_label' => $filename,
                'available' => isset($uploaded[$filename]) && $missing === [],
                'missing' => $missing,
                'upload_url' => $uploadUrl($missing[0] ?? $filename),
                'options' => $options,
                'help' => $help,
            ];
        };
        $eventOptions = fn ($events, string $target) => collect($events)->map(fn ($event) => [
            'value' => $event['name'],
            'label' => $event['name'],
            'target' => $target,
            'available' => isset($uploaded[$target]),
            'event_settings' => $event['settings'] ?? [],
            'children' => $event['children'] ?? [],
        ])->values()->all();
        $options = fn ($names, string $target) => collect($names)->map(fn ($name) => [
            'value' => $name, 'label' => $name, 'target' => $target, 'available' => isset($uploaded[$target]),
        ])->values()->all();
        $animalTargets = [
            ['AnimalBear', 'Medvěd', 'bear_territories.xml'],
            ['AnimalCow', 'Skot', 'cattle_territories.xml'],
            ['AnimalDeer', 'Jelen', 'red_deer_territories.xml'],
            ['AnimalRoeDeer', 'Srnec', 'roe_deer_territories.xml'],
            ['AnimalWolf', 'Vlk', 'wolf_territories.xml'],
            ['AnimalWildBoar', 'Divočák', 'wild_boar_territories.xml'],
            ['AnimalSheep', 'Ovce / koza', 'sheep_goat_territories.xml'],
            ['AnimalPig', 'Prase', 'pig_territories.xml'],
            ['AnimalFox', 'Liška', 'fox_territories.xml'],
            ['AnimalHare', 'Zajíc', 'hare_territories.xml'],
            ['AnimalHen', 'Slepice', 'hen_territories.xml'],
            ['AnimalDomestic', 'Domácí zvířata', 'domestic_animals_territories.xml'],
        ];
        $animalOptions = collect($animalTargets)->map(fn ($item) => [
            'value' => $item[0],
            'label' => $item[1].' · '.$item[2],
            'target' => $item[2],
            'available' => isset($uploaded[$item[2]]),
            'upload_url' => $uploadUrl($item[2]),
        ])->all();
        $territoryOptions = collect(array_keys($uploaded))
            ->filter(fn ($name) => Str::is('*_territories.xml', $name))
            ->map(fn ($name) => ['value' => 'HuntingGround', 'label' => $name, 'target' => $name, 'available' => true])
            ->values()->all();
        $lootNames = [];
        if (isset($uploaded['mapgroupproto.xml'])) {
            $revision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'mapgroupproto.xml');
            if ($revision && Storage::disk('dayz')->exists($revision->storage_path)) {
                $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
                foreach ($xml?->group ?? [] as $group) {
                    $name = (string) ($group['name'] ?? '');
                    if ($name !== '') $lootNames[] = $name;
                }
            }
        }

        $eventsBy = fn ($callback) => collect($this->eventCatalog)->filter($callback)->values();
        $eventFields = [
            ['name' => 'orientation', 'label' => 'Natočení objektu (°)', 'type' => 'number', 'min' => 0, 'max' => 359.999, 'step' => 0.001, 'default' => 0, 'help' => 'Atribut a v cfgeventspawns.xml. 0° míří na sever, hodnota určuje natočení kandidátní pozice.'],
        ];
        $playerFields = [
            ['name' => 'spawn_mode', 'section' => 'Bod a skupina', 'label' => 'Režim spawnu', 'type' => 'select', 'default' => 'fresh', 'options' => [
                ['value' => 'fresh', 'label' => 'fresh · nová postava'],
                ['value' => 'hop', 'label' => 'hop · změna serveru'],
                ['value' => 'travel', 'label' => 'travel · cestovní přesun'],
            ], 'help' => 'Sekce XML. Parametry režimu níže platí pro všechny jeho skupiny a body.'],
            ['name' => 'group_name', 'section' => 'Bod a skupina', 'label' => 'Název skupiny oblastí', 'type' => 'text', 'default' => 'Vlastni oblast', 'help' => 'Více pozic se stejným názvem tvoří jednu skupinu generátoru.'],
            ['name' => 'group_lifetime_override', 'section' => 'Bod a skupina', 'label' => 'Lifetime skupiny – přepis (s)', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => '', 'help' => 'Volitelný atribut skupiny. Prázdné = použít hodnotu režimu; -1 = bez časového vypršení.'],
            ['name' => 'group_counter_override', 'section' => 'Bod a skupina', 'label' => 'Counter skupiny – přepis', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => '', 'help' => 'Volitelný limit použití skupiny. Prázdné = hodnota režimu; -1 = bez limitu.'],
            ['name' => 'min_dist_infected', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od nakažených (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 30, 'help' => 'Pod touto vzdáleností je kandidát neplatný. Musí být ≤ maximu.'],
            ['name' => 'max_dist_infected', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od nakažených (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 70, 'help' => 'Od minima do maxima získává kandidát lepší hodnocení; nad maximem už bez další výhody.'],
            ['name' => 'min_dist_player', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od hráčů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 25, 'help' => 'Pod touto vzdáleností od jiného hráče se kandidát nepoužije.'],
            ['name' => 'max_dist_player', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od hráčů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 70, 'help' => 'Horní mez hodnocení bezpečné vzdálenosti od ostatních hráčů.'],
            ['name' => 'min_dist_static', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od statických objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 0, 'help' => 'Minimální odstup kandidáta od statické geometrie při vyhodnocení spawnu.'],
            ['name' => 'max_dist_static', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od statických objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 2, 'help' => 'Horní mez hodnocení odstupu od statické geometrie.'],
            ['name' => 'grid_density', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Hustota mřížky', 'type' => 'number', 'min' => 1, 'max' => 1000, 'step' => 1, 'default' => 4, 'help' => 'Počet testovaných kandidátů v mřížce. Vyšší hodnota zpřesňuje hledání, ale zvyšuje práci serveru.'],
            ['name' => 'grid_width', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Šířka oblasti (m)', 'type' => 'number', 'min' => 1, 'max' => 15360, 'step' => 1, 'default' => 200, 'help' => 'Celková šířka prohledávané oblasti kolem každého bodu; není to poloměr.'],
            ['name' => 'grid_height', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Výška oblasti (m)', 'type' => 'number', 'min' => 1, 'max' => 15360, 'step' => 1, 'default' => 200, 'help' => 'Celková výška prohledávané oblasti kolem každého bodu; nejde o nadmořskou výšku.'],
            ['name' => 'generator_min_dist_static', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Generátor: min. odstup od objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 0, 'help' => 'Kandidátní buňky blíže statické geometrii jsou odmítnuty.'],
            ['name' => 'generator_max_dist_static', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Generátor: max. odstup od objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 2, 'help' => 'Horní mez hodnocení odstupu kandidátní buňky od statické geometrie.'],
            ['name' => 'min_steepness', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Minimální sklon (°)', 'type' => 'number', 'min' => -90, 'max' => 90, 'step' => 0.1, 'default' => -45, 'help' => 'Dolní povolená mez sklonu povrchu v rozsahu -90 až 90°.'],
            ['name' => 'max_steepness', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Maximální sklon (°)', 'type' => 'number', 'min' => -90, 'max' => 90, 'step' => 0.1, 'default' => 45, 'help' => 'Horní povolená mez sklonu; musí být ≥ minimálnímu sklonu.'],
            ['name' => 'enablegroups', 'section' => 'Chování skupin · group_params', 'label' => 'Používat skupiny', 'type' => 'select', 'default' => 'true', 'options' => [
                ['value' => 'true', 'label' => 'true · skupiny zapnuté'],
                ['value' => 'false', 'label' => 'false · skupiny vypnuté'],
            ], 'help' => 'Určuje, zda generátor používá pojmenované skupiny z generator_posbubbles.'],
            ['name' => 'groups_as_regular', 'section' => 'Chování skupin · group_params', 'label' => 'Skupiny jako běžné oblasti', 'type' => 'select', 'default' => 'true', 'options' => [
                ['value' => 'true', 'label' => 'true · použít jako běžné oblasti'],
                ['value' => 'false', 'label' => 'false · zvláštní skupinové chování'],
            ], 'help' => 'Řídí, zda se skupinové oblasti vyhodnocují stejným způsobem jako pravidelné kandidátní oblasti.'],
            ['name' => 'lifetime', 'section' => 'Chování skupin · group_params', 'label' => 'Lifetime režimu (s)', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => 120, 'help' => 'Jak dlouho zůstává volba skupiny aktivní. -1 vypíná časové vypršení.'],
            ['name' => 'counter', 'section' => 'Chování skupin · group_params', 'label' => 'Counter režimu', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => 2, 'help' => 'Kolikrát lze skupinu použít před dalším výběrem. -1 vypíná limit počtu.'],
        ];
        $playerModeDefaults = [];
        $playerRevision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'cfgplayerspawnpoints.xml');
        if ($playerRevision && Storage::disk('dayz')->exists($playerRevision->storage_path)) {
            $playerXml = @simplexml_load_string(Storage::disk('dayz')->get($playerRevision->storage_path));
            foreach (['fresh', 'hop', 'travel'] as $mode) {
                $modeNode = $playerXml?->{$mode};
                if (! $modeNode) {
                    continue;
                }
                $playerModeDefaults[$mode] = [
                    'min_dist_infected' => (string) ($modeNode->spawn_params->min_dist_infected ?? ''),
                    'max_dist_infected' => (string) ($modeNode->spawn_params->max_dist_infected ?? ''),
                    'min_dist_player' => (string) ($modeNode->spawn_params->min_dist_player ?? ''),
                    'max_dist_player' => (string) ($modeNode->spawn_params->max_dist_player ?? ''),
                    'min_dist_static' => (string) ($modeNode->spawn_params->min_dist_static ?? ''),
                    'max_dist_static' => (string) ($modeNode->spawn_params->max_dist_static ?? ''),
                    'grid_density' => (string) ($modeNode->generator_params->grid_density ?? ''),
                    'grid_width' => (string) ($modeNode->generator_params->grid_width ?? ''),
                    'grid_height' => (string) ($modeNode->generator_params->grid_height ?? ''),
                    'generator_min_dist_static' => (string) ($modeNode->generator_params->min_dist_static ?? ''),
                    'generator_max_dist_static' => (string) ($modeNode->generator_params->max_dist_static ?? ''),
                    'min_steepness' => (string) ($modeNode->generator_params->min_steepness ?? ''),
                    'max_steepness' => (string) ($modeNode->generator_params->max_steepness ?? ''),
                    'enablegroups' => (string) ($modeNode->group_params->enablegroups ?? ''),
                    'groups_as_regular' => (string) ($modeNode->group_params->groups_as_regular ?? ''),
                    'lifetime' => (string) ($modeNode->group_params->lifetime ?? ''),
                    'counter' => (string) ($modeNode->group_params->counter ?? ''),
                ];
            }
        }
        $territoryFields = [
            ['name' => 'zone_type', 'label' => 'Úloha zóny', 'type' => 'select', 'default' => 'HuntingGround', 'options' => [
                ['value' => 'HuntingGround', 'label' => 'HuntingGround · hlavní oblast výskytu'],
                ['value' => 'Rest', 'label' => 'Rest · klidová oblast'],
                ['value' => 'Graze', 'label' => 'Graze · pastva'],
                ['value' => 'Water', 'label' => 'Water · zdroj vody'],
            ]],
            ['name' => 'radius', 'label' => 'Poloměr (m)', 'type' => 'number', 'min' => 1, 'max' => 5000, 'step' => 0.5, 'default' => 150, 'help' => 'Atribut r: dosah zóny od středu v metrech.'],
            ['name' => 'smin', 'label' => 'Statický spawn minimum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut smin. Minimální počet statických výskytů pro zónu.'],
            ['name' => 'smax', 'label' => 'Statický spawn maximum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut smax. Maximální počet statických výskytů pro zónu.'],
            ['name' => 'dmin', 'label' => 'Dynamický spawn minimum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut dmin. Minimální počet dynamických výskytů.'],
            ['name' => 'dmax', 'label' => 'Dynamický spawn maximum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut dmax. Maximální počet dynamických výskytů.'],
        ];

        $this->pointTypeCatalog = [
            'vehicle' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::startsWith($event['name'], 'Vehicle')), 'cfgeventspawns.xml'), 'Tento formulář ukládá kandidátní pozici a natočení. Počet vozidel a jejich životnost řídí events.xml; attachmenty a náklad cfgspawnabletypes.xml.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, limity, životnost a aktivace', 'cfgspawnabletypes.xml' => 'attachmenty, cargo a poškození']]),
            'heli' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), 'heli')), 'cfgeventspawns.xml'), 'Kandidátní pozice a natočení heli eventu. Jeho pravidla zůstávají v events.xml.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, životnost, vzdálenosti a varianty']]),
            'convoy' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), ['convoy', 'train'])), 'cfgeventspawns.xml'), 'Pozice a natočení konvoje nebo vlaku. Složení objektů řídí cfgeventgroups.xml.', ['events.xml', 'cfgeventgroups.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'chování eventu', 'cfgeventgroups.xml' => 'objekty, relativní pozice, loot a natočení']]),
            'dynamic' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn () => true), 'cfgeventspawns.xml'), 'Kandidátní pozice a natočení vybraného eventu.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'všechny parametry chování eventu']]),
            'aerial' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), ['air', 'heli', 'plane'])), 'cfgeventspawns.xml'), 'Pozice a natočení leteckého eventu.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, životnost, limity a varianty']]),
            'player' => array_merge($exact('cfgplayerspawnpoints.xml', [['value' => 'Nová spawn oblast', 'label' => 'Nová oblast generátoru hráče', 'target' => 'cfgplayerspawnpoints.xml', 'available' => isset($uploaded['cfgplayerspawnpoints.xml'])]], 'Přidává centrum oblasti generátoru, nikoli garantovaný přesný spawn. Formulář obsahuje všechny parametry spawn_params, generator_params i group_params pro zvolený režim.'), ['fields' => $playerFields, 'mode_defaults' => $playerModeDefaults, 'related' => ['cfgplayerspawnpoints.xml' => 'bod, skupina i kompletní nastavení zvoleného režimu fresh/hop/travel']]),
            'contaminated' => array_merge($exact('cfgeffectarea.json', [['value' => 'ContaminatedArea_Static', 'label' => 'Statická kontaminovaná zóna', 'target' => 'cfgeffectarea.json', 'available' => isset($uploaded['cfgeffectarea.json'])]], 'Zóna se uloží do pole Areas v cfgeffectarea.json včetně vertikálního rozsahu a částic.'), ['fields' => [
                ['name' => 'area_name', 'label' => 'Jedinečný název oblasti', 'type' => 'text', 'default' => 'Vlastni kontaminovana zona'],
                ['name' => 'radius', 'label' => 'Poloměr (m)', 'type' => 'number', 'min' => 1, 'max' => 5000, 'default' => 100],
                ['name' => 'pos_y', 'label' => 'Výška středu Y (m)', 'type' => 'number', 'min' => -1000, 'max' => 5000, 'step' => 0.1, 'default' => 0],
                ['name' => 'pos_height', 'label' => 'Dosah nad střed (m)', 'type' => 'number', 'min' => 0, 'max' => 5000, 'default' => 20],
                ['name' => 'neg_height', 'label' => 'Dosah pod střed (m)', 'type' => 'number', 'min' => 0, 'max' => 5000, 'default' => 3],
                ['name' => 'inner_part_dist', 'label' => 'Rozestup částic uvnitř (m)', 'type' => 'number', 'min' => 1, 'max' => 1000, 'default' => 80],
                ['name' => 'outer_offset', 'label' => 'Přesah vnějšího prstence (m)', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 30],
                ['name' => 'particle_name', 'label' => 'Hlavní částice', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_bigass'],
                ['name' => 'around_particle', 'label' => 'Částice kolem hráče', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_around'],
                ['name' => 'tiny_particle', 'label' => 'Jemná částice kolem hráče', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_around_tiny'],
                ['name' => 'ppe_type', 'label' => 'PPE vizuální efekt', 'type' => 'text', 'default' => 'PPERequester_ContaminatedAreaTint'],
            ]]),
            'loot' => array_merge($exact('mapgrouppos.xml', $options($lootNames, 'mapgrouppos.xml'), 'Světová pozice existujícího prototypu skupiny se zapíše do mapgrouppos.xml.', ['mapgroupproto.xml']), ['fields' => [
                ['name' => 'pos_y', 'label' => 'Výška Y (m)', 'type' => 'number', 'min' => -1000, 'max' => 5000, 'step' => 0.001, 'default' => 0],
                ['name' => 'pitch', 'label' => 'Náklon pitch (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'yaw', 'label' => 'Natočení yaw (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'roll', 'label' => 'Náklon roll (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'orientation', 'label' => 'Atribut a (°)', 'type' => 'number', 'min' => 0, 'max' => 359.999, 'step' => 0.001, 'default' => 0],
            ]]),
            'animal' => [
                'target' => null, 'target_label' => 'odpovídající *_territories.xml', 'available' => true,
                'missing' => [], 'upload_url' => $uploadUrl('wolf_territories.xml'), 'options' => $animalOptions,
                'help' => 'Vyberte druh. Nová zóna se uloží pouze do jeho vlastního *_territories.xml; events.xml se tím nemění.',
                'fields' => $territoryFields,
                'related' => ['events.xml' => 'počet stád, velikost stáda, lifetime a restock'],
            ],
            'territory' => [
                'target' => null, 'target_label' => '*_territories.xml', 'available' => count($territoryOptions) > 0,
                'missing' => count($territoryOptions) ? [] : ['*_territories.xml'], 'upload_url' => $uploadUrl('*_territories.xml'),
                'options' => $territoryOptions, 'help' => 'Obecná oblast se zapíše do konkrétního nahraného souboru teritorií.',
                'fields' => $territoryFields,
            ],
            'infected' => [
                'target' => null, 'target_label' => '*infected*_territories.xml', 'available' => false,
                'missing' => ['příslušný infected territory XML'], 'upload_url' => $uploadUrl('*infected*_territories.xml'),
                'options' => [], 'help' => 'Zóny nakažených vyžadují export příslušného territory XML z aktuální mise. Bez něj editor zápis z bezpečnostních důvodů nepovolí.',
            ],
            'custom' => [
                'target' => null, 'target_label' => 'Object Spawner JSON', 'available' => false,
                'missing' => ['Object Spawner JSON'], 'upload_url' => $uploadUrl('*spawner*.json'),
                'options' => [], 'help' => 'Vlastní objekt nelze bezpečně zapsat bez třídy objektu, orientace a aktuálního Object Spawner JSON.',
            ],
        ];
    }

    public function loadMarkers(): void
    {
        $this->markers = [];
        $this->markerCounts = [];
        $this->loadedSources = [];
        $this->layerScopes = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }

        $reader = app(MapConfigurationReader::class);
        foreach ($this->latestRevisions($project) as $revision) {
            $filename = $this->revisionFilename($revision);
            if (! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            $content = Storage::disk('dayz')->get($revision->storage_path);
            $this->layerScopes[$filename] = $this->scopesFor($filename, $content);
            if ($filename === 'mapgrouppos.xml' && ! $this->showDenseLayers) {
                $this->markerCounts[$filename] = substr_count($content, '<group ');
                continue;
            }
            foreach ($reader->markers($filename, $content) as $marker) {
                $marker['revision_id'] = $revision->id;
                $marker['color'] = $this->colorFor($filename);
                $this->markers[] = $marker;
                $this->markerCounts[$filename] = ($this->markerCounts[$filename] ?? 0) + 1;
                $this->loadedSources[$filename] = true;
            }
        }
    }

    /** @return list<array{value:string,label:string,count:int}> */
    private function scopesFor(string $filename, string $content): array
    {
        $xml = @simplexml_load_string($content);
        if (! $xml) {
            return [];
        }
        $scopes = [];
        if ($filename === 'cfgeventspawns.xml') {
            foreach ($xml->event ?? [] as $event) {
                $count = count($event->pos ?? []);
                if ($count > 0) {
                    $name = (string) ($event['name'] ?? '');
                    $scopes[] = ['value' => 'event:'.$name, 'label' => $name, 'count' => $count];
                }
            }
        }
        if ($filename === 'cfgplayerspawnpoints.xml') {
            foreach (['fresh', 'hop', 'travel'] as $mode) {
                $modeNode = $xml->{$mode};
                if (! $modeNode) {
                    continue;
                }
                $modeCount = 0;
                foreach ($modeNode->generator_posbubbles->group ?? [] as $group) {
                    $count = count($group->pos ?? []);
                    if ($count === 0) {
                        continue;
                    }
                    $name = (string) ($group['name'] ?? 'Bez názvu');
                    $modeCount += $count;
                    $scopes[] = ['value' => "group:{$mode}|{$name}", 'label' => "{$mode} · {$name}", 'count' => $count];
                }
                if ($modeCount > 0) {
                    array_unshift($scopes, ['value' => 'mode:'.$mode, 'label' => strtoupper($mode).' · celý režim', 'count' => $modeCount]);
                }
            }
        }

        return $scopes;
    }

    private function projectQuery()
    {
        return Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()));
    }

    private function latestRevisions(?Project $project)
    {
        return $project?->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->unique(fn ($revision) => $this->revisionFilename($revision))
            ->values() ?? collect();
    }

    private function revisionFilename($revision): string
    {
        return strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path)));
    }

    private function source(string $filename, string $description, bool $plottable, $revision = null): array
    {
        return [
            'filename' => $filename,
            'description' => $description,
            'plottable' => $plottable,
            'uploaded' => $revision !== null,
            'revision_id' => $revision?->id,
            'revision_number' => $revision?->revision_number,
            'marker_count' => $this->markerCounts[$filename] ?? 0,
            'loaded' => (bool) ($this->loadedSources[$filename] ?? false),
            'color' => $this->colorFor($filename),
        ];
    }

    private function colorFor(string $filename): string
    {
        $colors = ['#b8ed55', '#80b8ff', '#f1b44c', '#e96a5f', '#d58cff', '#55e0c1', '#ff82b2', '#f6d365'];

        return $colors[abs(crc32(strtolower($filename))) % count($colors)];
    }

    public function importMapConfiguration(ConfigurationImporter $importer): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project || ! $this->mapFile) {
            return;
        }
        $importer->import($project, $this->mapFile, auth()->user());
        $this->reset('mapFile');
        $this->loadMarkers();
        $this->loadMapSources();
    }

    public function getTitle(): string
    {
        return 'Mapa · '.$this->map;
    }

    public function getSubheading(): ?string
    {
        return 'Souřadnice, vrstvy a bezpečné revize mapových konfiguračních souborů.';
    }
}

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
                    $children[] = (string) ($child['type'] ?? '');
                }
                $this->eventCatalog[] = ['name' => (string) $event['name'], 'children' => array_values(array_filter($children))];
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
                    $this->eventCatalog[$index]['children'] = array_values(array_unique(array_merge($this->eventCatalog[$index]['children'], $children)));
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
        $eventNames = collect($this->eventCatalog)->pluck('name')->filter()->unique()->sort()->values();
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
        $options = fn ($names, string $target) => collect($names)->map(fn ($name) => [
            'value' => $name,
            'label' => $name,
            'target' => $target,
            'available' => isset($uploaded[$target]),
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

        $this->pointTypeCatalog = [
            'vehicle' => $exact('cfgeventspawns.xml', $options($eventNames->filter(fn ($name) => Str::startsWith($name, 'Vehicle')), 'cfgeventspawns.xml'), 'Pozice vozidla se zapíše do cfgeventspawns.xml. Parametry eventu zůstávají v events.xml.', ['events.xml']),
            'heli' => $exact('cfgeventspawns.xml', $options($eventNames->filter(fn ($name) => Str::contains(Str::lower($name), 'heli')), 'cfgeventspawns.xml'), 'Kandidátní pozice heli eventu patří do cfgeventspawns.xml; pravidla eventu jsou v events.xml.', ['events.xml']),
            'convoy' => $exact('cfgeventspawns.xml', $options($eventNames->filter(fn ($name) => Str::contains(Str::lower($name), ['convoy', 'train'])), 'cfgeventspawns.xml'), 'Konvoj nebo vlak se umístí do cfgeventspawns.xml; jeho složení definuje cfgeventgroups.xml.', ['events.xml', 'cfgeventgroups.xml']),
            'dynamic' => $exact('cfgeventspawns.xml', $options($eventNames, 'cfgeventspawns.xml'), 'Vybranému eventu z events.xml se přidá kandidátní pozice do cfgeventspawns.xml.', ['events.xml']),
            'aerial' => $exact('cfgeventspawns.xml', $options($eventNames->filter(fn ($name) => Str::contains(Str::lower($name), ['air', 'heli', 'plane'])), 'cfgeventspawns.xml'), 'Letecký event používá pravidla z events.xml a pozice z cfgeventspawns.xml.', ['events.xml']),
            'player' => $exact('cfgplayerspawnpoints.xml', [['value' => 'Nová spawn oblast', 'label' => 'Nová spawn oblast hráče', 'target' => 'cfgplayerspawnpoints.xml', 'available' => isset($uploaded['cfgplayerspawnpoints.xml'])]], 'Přidá bod do generátoru oblastí v cfgplayerspawnpoints.xml. Server následně hledá bezpečný povrch v okolí.'),
            'contaminated' => $exact('cfgeffectarea.json', [['value' => 'ContaminatedArea_Static', 'label' => 'Statická kontaminovaná zóna', 'target' => 'cfgeffectarea.json', 'available' => isset($uploaded['cfgeffectarea.json'])]], 'Zóna včetně poloměru se zapíše do pole Areas v cfgeffectarea.json.'),
            'loot' => $exact('mapgrouppos.xml', $options($lootNames, 'mapgrouppos.xml'), 'Světová pozice existujícího prototypu skupiny se zapíše do mapgrouppos.xml.', ['mapgroupproto.xml']),
            'animal' => [
                'target' => null, 'target_label' => 'odpovídající *_territories.xml', 'available' => true,
                'missing' => [], 'upload_url' => $uploadUrl('wolf_territories.xml'), 'options' => $animalOptions,
                'help' => 'Vyberte druh. Nová zóna se uloží pouze do jeho vlastního *_territories.xml; events.xml se tím nemění.',
            ],
            'territory' => [
                'target' => null, 'target_label' => '*_territories.xml', 'available' => count($territoryOptions) > 0,
                'missing' => count($territoryOptions) ? [] : ['*_territories.xml'], 'upload_url' => $uploadUrl('*_territories.xml'),
                'options' => $territoryOptions, 'help' => 'Obecná oblast se zapíše do konkrétního nahraného souboru teritorií.',
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
}

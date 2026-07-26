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
    }

    public function updatedProjectId(): void
    {
        $this->loadMarkers();
        $this->loadEventCatalog();
        $this->loadMapSources();
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
                if ($index === false) {
                    $this->eventCatalog[] = ['name' => $name, 'children' => array_values(array_unique($children))];
                } else {
                    $this->eventCatalog[$index]['children'] = array_values(array_unique(array_merge($this->eventCatalog[$index]['children'], $children)));
                }
            }
        }
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

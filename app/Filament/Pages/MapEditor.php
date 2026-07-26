<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Import\ConfigurationImporter;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
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

    public function mount(): void
    {
        $this->projects = Project::query()->where('user_id', auth()->id())->orderBy('name')->pluck('name', 'id')->all();
        $this->projectId = request()->integer('project') ?: array_key_first($this->projects);
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
            'cfgeventspawns.xml' => 'Pevné pozice eventů a jejich orientace.',
            'events.xml' => 'Dynamické eventy, vozidla a heli crash.',
            'cfgeventgroups.xml' => 'Skupiny a varianty eventů.',
            'cfgplayerspawnpoints.xml' => 'Spawnovací body hráčů.',
            'mapgrouppos.xml' => 'Pozice loot skupin a budov.',
            'mapgroupcluster.xml' => 'Clustery budov – hlavní část.',
            'mapgroupcluster01.xml' => 'Clustery budov – část 1.',
            'mapgroupcluster02.xml' => 'Clustery budov – část 2.',
            'mapgroupcluster03.xml' => 'Clustery budov – část 3.',
            'mapgroupcluster04.xml' => 'Clustery budov – část 4.',
            'mapgroupproto.xml' => 'Prototypy skupin budov.',
            'mapclusterproto.xml' => 'Prototypy mapových clusterů.',
            'mapgroupdirt.xml' => 'Doplňková data mapových skupin.',
            'cfgeffectarea.json' => 'Efektové a kontaminované oblasti.',
            'cfgundergroundtriggers.json' => 'Spouštěče podzemních oblastí.',
            '*_territories.xml' => 'Teritoria zvířat podle druhu.',
        ];
        $project = $this->projectId ? Project::query()->where('user_id', auth()->id())->find($this->projectId) : null;
        $revisions = $project?->revisions()->with('configurationImport')->latest('revision_number')->get() ?? collect();
        $this->mapSources = [];
        foreach ($definitions as $filename => $description) {
            $revision = $revisions->first(function ($item) use ($filename) {
                $name = strtolower($item->configurationImport?->original_filename ?? basename($item->storage_path));
                return $filename === '*_territories.xml' ? str_ends_with($name, '_territories.xml') : $name === $filename;
            });
            $this->mapSources[] = [
                'filename' => $filename,
                'description' => $description,
                'uploaded' => $revision !== null,
                'revision_id' => $revision?->id,
                'revision_number' => $revision?->revision_number,
            ];
        }
    }

    public function loadEventCatalog(): void
    {
        $this->eventCatalog = [];
        $project = $this->projectId ? Project::query()->where('user_id', auth()->id())->find($this->projectId) : null;
        $revision = $project?->revisions()->with('configurationImport')->latest()->get()->first(fn ($item) => strtolower($item->configurationImport?->original_filename ?? '') === 'events.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            return;
        }
        $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
        foreach ($xml?->event ?? [] as $event) {
            $children = [];
            foreach ($event->children->child ?? [] as $child) {
                $children[] = (string) ($child['type'] ?? '');
            }
            $this->eventCatalog[] = ['name' => (string) $event['name'], 'children' => array_values(array_filter($children))];
        }
    }

    public function loadMarkers(): void
    {
        $this->markers = [];
        $project = $this->projectId ? Project::query()->where('user_id', auth()->id())->find($this->projectId) : null;
        if (! $project) {
            return;
        }

        foreach ($project->revisions()->with('configurationImport')->latest()->get() as $revision) {
            $filename = strtolower($revision->configurationImport?->original_filename ?? basename($revision->storage_path));
            $isMapSource = in_array($filename, [
                'cfgeventspawns.xml', 'mapgrouppos.xml', 'events.xml', 'cfgeventgroups.xml',
                'cfgplayerspawnpoints.xml', 'mapclusterproto.xml', 'mapgroupproto.xml',
                'mapgroupcluster.xml', 'mapgroupcluster01.xml', 'mapgroupcluster02.xml',
                'mapgroupcluster03.xml', 'mapgroupcluster04.xml', 'mapgroupdirt.xml',
            ], true) || str_ends_with($filename, '_territories.xml');
            if (! $isMapSource || ! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
            if (! $xml) {
                continue;
            }
            foreach ($xml->xpath('//*[@x and (@z or @y)]') ?: [] as $node) {
                $x = (float) $node['x'];
                $z = isset($node['z']) ? (float) $node['z'] : (float) $node['y'];
                if ($x < 0 || $z < 0 || $x > 15360 || $z > 15360) {
                    continue;
                }
                $this->markers[] = [
                    'type' => str_contains($filename, 'event') ? 'event' : (str_contains($filename, 'territor') ? 'animal' : 'spawn'),
                    'label' => $filename.' · '.number_format($x, 0).' / '.number_format($z, 0),
                    'x' => round(($x / 15360) * 100, 3),
                    'y' => round((1 - ($z / 15360)) * 100, 3),
                    'worldX' => $x,
                    'worldZ' => $z,
                    'filename' => $filename,
                ];
            }
        }
    }

    public function importMapConfiguration(ConfigurationImporter $importer): void
    {
        $project = $this->projectId ? Project::query()->where('user_id', auth()->id())->find($this->projectId) : null;
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

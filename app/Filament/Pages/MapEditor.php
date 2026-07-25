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

    public function mount(): void
    {
        $this->projects = Project::query()->where('user_id', auth()->id())->orderBy('name')->pluck('name', 'id')->all();
        $this->projectId = request()->integer('project') ?: array_key_first($this->projects);
        $this->loadMarkers();
    }

    public function updatedProjectId(): void
    {
        $this->loadMarkers();
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
            if (! in_array($filename, ['cfgeventspawns.xml', 'mapgrouppos.xml', 'events.xml'], true) || ! Storage::disk('dayz')->exists($revision->storage_path)) {
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
                    'type' => str_contains($filename, 'event') ? 'event' : 'spawn',
                    'label' => $filename.' · '.number_format($x, 0).' / '.number_format($z, 0),
                    'x' => round(($x / 15360) * 100, 3),
                    'y' => round((1 - ($z / 15360)) * 100, 3),
                    'worldX' => $x,
                    'worldZ' => $z,
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
    }

    public function getTitle(): string
    {
        return 'Mapa · '.$this->map;
    }
}

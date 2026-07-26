<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Dayz\ConfigurationCatalog;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class ConfigurationWizard extends Page
{
    protected static string $view = 'filament.pages.configuration-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public array $areas = [];

    public array $projects = [];

    public ?int $projectId = null;

    public function mount(ConfigurationCatalog $catalog): void
    {
        $query = Project::query()
            ->when(! auth()->user()?->is_admin, fn ($builder) => $builder->where('user_id', auth()->id()))
            ->orderBy('name');
        $this->projects = $query->pluck('name', 'id')->all();
        $requestedProject = request()->integer('project');
        $this->projectId = array_key_exists($requestedProject, $this->projects)
            ? $requestedProject
            : array_key_first($this->projects);
        $this->areas = $this->buildAreas($catalog);
    }

    private function buildAreas(ConfigurationCatalog $catalog): array
    {
        $areas = $catalog->areas();
        $project = $this->projectId
            ? Project::query()
                ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
                ->find($this->projectId)
            : null;
        $revisions = $project?->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get() ?? collect();

        foreach ($areas as $key => &$area) {
            $matches = $revisions->filter(function ($revision) use ($key) {
                $filename = strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path)));

                return collect($this->patternsFor($key))->contains(fn ($pattern) => Str::is($pattern, $filename));
            })->values();
            $latest = $matches->first();
            $area['uploaded_count'] = $matches
                ->unique(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
                ->count();
            $area['latest_filename'] = $latest
                ? basename(str_replace('\\', '/', $latest->configurationImport?->original_filename ?? $latest->storage_path))
                : null;
            $area['latest_revision'] = $latest?->revision_number;
            $area['url'] = ! $project
                ? url('/admin/projects/create')
                : ($latest
                    ? ($key === 'map'
                        ? url('/admin/map-editor?project='.$project->id)
                        : url('/admin/projects/'.$project->id.'/configuration?revision='.$latest->id))
                    : url('/admin/configuration-import?area='.$key.'&project='.$project->id));
        }

        return $areas;
    }

    /** @return list<string> */
    private function patternsFor(string $area): array
    {
        return match ($area) {
            'server' => ['serverdz.cfg', 'dayzsettings.xml', 'beserver*.cfg'],
            'economy' => ['types.xml', 'globals.xml', 'economy.xml', 'cfgeconomycore.xml', 'cfglimitsdefinition*.xml', 'cfgareaflags.xml'],
            'events' => ['events.xml', 'cfgeventspawns.xml', 'cfgeventgroups.xml', 'cfgspawnabletypes.xml', '*_territories.xml'],
            'map' => ['cfgplayerspawnpoints.xml', 'mapgrouppos.xml', 'mapgroupcluster*.xml', 'mapgroupproto.xml', 'mapclusterproto.xml', 'cfgeffectarea.json', '*_territories.xml'],
            'environment' => ['cfgweather.xml', 'cfgenvironment.xml', 'cfgeffectarea.json', 'cfgundergroundtriggers.json'],
            'gameplay' => ['cfggameplay.json'],
            'gear' => ['*spawn-gear*.json'],
            'objects' => ['*spawner*.json'],
            'admin' => ['messages.xml', 'whitelist.txt', 'ban.txt', 'priority.txt', 'cfgignorelist.xml', 'cfgrandompresets.xml'],
            'advanced' => ['init.c'],
            default => [],
        };
    }

    public function getTitle(): string
    {
        return 'Průvodce konfigurací';
    }
}

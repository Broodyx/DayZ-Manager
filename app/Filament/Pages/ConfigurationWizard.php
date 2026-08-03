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
            $entries = collect($catalog->filesByArea()[$key] ?? [])->map(function (array $entry) use ($revisions, $project, $key) {
                $matches = $revisions->filter(function ($revision) use ($entry) {
                    $filename = strtolower(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path));
                    $basename = basename($filename);

                    return Str::is($entry['pattern'], $filename) || Str::is($entry['pattern'], $basename);
                })->values();
                $latest = $matches->first();
                $entry['uploaded_count'] = $matches
                    ->unique(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
                    ->count();
                $entry['actual_filename'] = $latest
                    ? basename(str_replace('\\', '/', $latest->configurationImport?->original_filename ?? $latest->storage_path))
                    : null;
                $entry['revision_number'] = $latest?->revision_number;
                $entry['url'] = ! $project
                    ? url('/admin/projects/create')
                    : ($latest
                        ? url('/admin/projects/'.$project->id.'/configuration?revision='.$latest->id)
                        : url('/admin/configuration-import?area='.$key.'&project='.$project->id.'&expected='.urlencode($entry['filename'])));

                return $entry;
            })->all();
            $matches = $revisions->filter(function ($revision) use ($key, $catalog) {
                $filename = strtolower(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path));
                $basename = basename($filename);

                return collect($catalog->filesByArea()[$key] ?? [])->contains(fn ($entry) => Str::is($entry['pattern'], $filename) || Str::is($entry['pattern'], $basename));
            })->values();
            $latest = $matches->first();
            $area['uploaded_count'] = $matches
                ->unique(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
                ->count();
            $area['latest_filename'] = $latest
                ? basename(str_replace('\\', '/', $latest->configurationImport?->original_filename ?? $latest->storage_path))
                : null;
            $area['latest_revision'] = $latest?->revision_number;
            $area['entries'] = $entries;
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

    public function getTitle(): string
    {
        return 'Nastavit server';
    }

    public function getSubheading(): ?string
    {
        return 'Checklist všech podporovaných oblastí a souborů pro vybraný DayZ server.';
    }
}
